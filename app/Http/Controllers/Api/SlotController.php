<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SlotLock;
use App\Models\VendorOfferingProfile;
use App\Models\VendorSlot;
use App\Services\AvailabilityService;
use App\Services\BookingPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SlotController extends Controller
{
    private const HOLD_TTL_MINUTES = 15; // tempo di validità dell'hold in minuti 

    // Crea un hold temporaneo sullo slot.
    public function hold(
        Request $request,
        BookingPricingService $bookingPricingService,
        AvailabilityService $availabilityService
    ): JsonResponse {
        $validated = $request->validate([
            'vendor_account_id' => 'required|integer|exists:vendor_accounts,id',
            'vendor_slot_id'    => 'required|integer|exists:vendor_slots,id',
            'offering_id'       => 'required|integer|exists:offerings,id',
            'date'              => 'required|date_format:Y-m-d|after_or_equal:today',
            'distance_km'       => 'nullable|numeric|min:0',
            'guests'            => 'nullable|integer|min:1',
        ]);

        $vendorAccountId = (int) $validated['vendor_account_id'];
        
        $vendorSlotId = (int) $validated['vendor_slot_id'];
        $offeringId = (int) $validated['offering_id'];
        $date = $validated['date'];
        $distanceKm = array_key_exists('distance_km', $validated) ? (float) $validated['distance_km'] : null;
        $guests = array_key_exists('guests', $validated) ? (int) $validated['guests'] : null;

        try {
            $this->resolveVendorSlot($vendorAccountId, $vendorSlotId);
            $profile = $this->resolveOfferingProfile($vendorAccountId, $offeringId);

            if ($guests !== null && $profile->exceedsCapacity($guests)) {
                return $this->unprocessable('Numero ospiti non supportato');
            }

            $availabilityService->assertSlotBookable(
                vendorAccountId: $vendorAccountId,
                vendorSlotId: $vendorSlotId,
                date: $date,
                offeringId: $offeringId,
                guests: $guests
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return $this->unprocessable($e->getMessage());
        } catch (Throwable $e) {
            return $this->serverError('Errore calcolo hold');
        }

        try {
            return DB::transaction(function () use (
                $vendorAccountId,
                $vendorSlotId,
                $offeringId,
                $date,
                $distanceKm,
                $guests,
                $bookingPricingService
            ): JsonResponse {
                $now = CarbonImmutable::now();
                $expiresAt = $now->addMinutes(self::HOLD_TTL_MINUTES);

                $activeLock = SlotLock::query()
                    ->forSlot($vendorAccountId, $vendorSlotId, $date)
                    ->active()
                    ->lockForUpdate()
                    ->first();

                if ($activeLock && $activeLock->isExpiredHold($now)) {
                    $activeLock->markExpired();
                    $activeLock = null;
                }

                // Riutilizza il pricing salvato nel database se esiste già un lock attivo,
                // mantenendo la consistenza dei prezzi durante il checkout.
                if ($activeLock) {
                    return $this->handleExistingActiveLockOnHold(
                        $activeLock,
                        $offeringId,
                        $distanceKm,
                        $guests,
                        $now
                    );
                }

                // Calcola il pricing dinamicamente solo se è necessario creare un nuovo lock,
                // ottimizzando i tempi di esecuzione e riducendo il carico sul servizio.
                try {
                    $pricing = $bookingPricingService->resolveForBooking(
                        vendorAccountId: $vendorAccountId,
                        offeringId: $offeringId,
                        eventDate: $date,
                        distanceKm: $distanceKm,
                        guests: $guests,
                    );
                } catch (RuntimeException $e) {
                    return response()->json([
                        'success' => false,
                        'error' => $e->getMessage(),
                    ], 422);
                }

                try {
                    $lock = SlotLock::create([
                        'vendor_account_id' => $vendorAccountId,
                        'vendor_slot_id'    => $vendorSlotId,
                        'offering_id'       => $offeringId,
                        'date'              => $date,
                        'distance_km'       => $distanceKm,
                        'guests'            => $guests,
                        'quoted_amount'     => $pricing['final_price'],
                        'currency'          => $pricing['currency'],
                        'pricing_breakdown' => $this->normalizePricingBreakdown($pricing),
                        'status'            => SlotLock::STATUS_HOLD,
                        'hold_token'        => (string) Str::uuid(),
                        'expires_at'        => $expiresAt,
                        'is_active'         => true,
                        'active_slot_key'   => SlotLock::makeActiveSlotKey(
                            $vendorAccountId,
                            $vendorSlotId,
                            $date
                        ),
                    ]);
                } catch (QueryException $e) {
                    if ($this->isUniqueConstraintViolation($e)) {
                        return $this->conflict('Slot non disponibile');
                    }

                    throw $e;
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->buildHoldResponseData($lock, $now),
                ], 201);
            });
        } catch (Throwable $e) {
            return $this->serverError('Errore hold');
        }
    }

    // Conferma un hold dopo il pagamento.
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hold_token'               => 'required|uuid',
            'prestashop_order_id'      => 'required|string|max:191',
            'prestashop_order_line_id' => 'required|string|max:191',
            'customer_data'            => 'nullable|array',
        ]);

        try {
            return DB::transaction(function () use ($validated): JsonResponse {
                $holdToken = $validated['hold_token'];
                $orderId = $validated['prestashop_order_id'];
                $lineId = $validated['prestashop_order_line_id'];
                $customerData = $validated['customer_data'] ?? null;
                $now = CarbonImmutable::now();

                $existingBooking = Booking::query()
                    ->where('prestashop_order_id', $orderId)
                    ->where('prestashop_order_line_id', $lineId)
                    ->lockForUpdate()
                    ->first();

                if ($existingBooking) {
                    $lock = SlotLock::query()
                        ->whereKey($existingBooking->slot_lock_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $lock) {
                        return $this->conflict('Incoerenza booking/lock');
                    }

                    if ($lock->hold_token !== $holdToken) {
                        return $this->conflict('Ordine già associato a un hold diverso');
                    }

                    if (! $lock->isBooked()) {
                        $lock->markBooked($existingBooking->id);
                    }

                    return $this->confirmSuccess($lock, $existingBooking);
                }

                $lock = SlotLock::query()
                    ->where('hold_token', $holdToken)
                    ->lockForUpdate()
                    ->first();

                if (! $lock) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Hold non trovato',
                    ], 404);
                }

                if ($lock->isBooked()) {
                    $booking = Booking::query()
                        ->where('slot_lock_id', $lock->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $booking) {
                        return $this->conflict('Lock BOOKED senza booking associata');
                    }

                    if (
                        (string) $booking->prestashop_order_id !== (string) $orderId ||
                        (string) $booking->prestashop_order_line_id !== (string) $lineId
                    ) {
                        return $this->conflict('Hold già confermato per un altro ordine');
                    }

                    return $this->confirmSuccess($lock, $booking);
                }

                if (! $lock->canBeConfirmed($now)) {
                    if ($lock->isExpiredHold($now)) {
                        $lock->markExpired();

                        return response()->json([
                            'success' => false,
                            'error' => 'Hold scaduto',
                        ], 410);
                    }

                    return $this->conflict('Lock non confermabile');
                }

                if ($lock->quoted_amount === null || $lock->currency === null) {
                    return $this->conflict('Prezzo non disponibile per questo hold');
                }

                $booking = Booking::query()
                    ->where('slot_lock_id', $lock->id)
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    try {
                        $vendor = \App\Models\VendorAccount::with('category')->find($lock->vendor_account_id);
                        
                        $isCommissionBased = true;
                        
                        // Gerarchia Commissione: Override Speciale > Standard Categoria > 20% Fallback Universale
                        $commissionRate = $vendor->custom_commission_rate 
                            ?? $vendor?->category?->commission_rate 
                            ?? 20.00;
                            
                        $commissionAmount = round(($lock->quoted_amount * $commissionRate) / 100, 2);

                        if ($vendor && $vendor->subscribed('default') && $vendor->payment_model === 'SUBSCRIPTION') {
                            $isCommissionBased = false;
                            $commissionRate = 0;
                            $commissionAmount = 0;
                        }

                        $booking = Booking::create([
                            'slot_lock_id'             => $lock->id,
                            'vendor_account_id'        => $lock->vendor_account_id,
                            'offering_id'              => $lock->offering_id,
                            'vendor_slot_id'           => $lock->vendor_slot_id,
                            'event_date'               => $lock->date,
                            'distance_km'              => $lock->distance_km,
                            'guests'                   => $lock->guests,
                            'prestashop_order_id'      => $orderId,
                            'prestashop_order_line_id' => $lineId,
                            'customer_data'            => $customerData,
                            'total_amount'             => $lock->quoted_amount,
                            'currency'                 => $lock->currency,
                            'pricing_breakdown'        => $lock->pricing_breakdown,
                            'status'                   => Booking::STATUS_PENDING_VENDOR_CONFIRMATION,
                            'paid_at'                  => $now,
                            'is_commission_based'      => $isCommissionBased,
                            'commission_rate'          => $commissionRate,
                            'commission_amount'        => $commissionAmount,
                        ]);
                    } catch (QueryException $e) {
                        if (! $this->isUniqueConstraintViolation($e)) {
                            throw $e;
                        }

                        $booking = Booking::query()
                            ->where('prestashop_order_id', $orderId)
                            ->where('prestashop_order_line_id', $lineId)
                            ->lockForUpdate()
                            ->first();

                        if (! $booking) {
                            throw $e;
                        }

                        if ((int) $booking->slot_lock_id !== (int) $lock->id) {
                            return $this->conflict('Ordine già associato a un hold diverso');
                        }
                    }
                } else {
                    if (
                        (string) $booking->prestashop_order_id !== (string) $orderId ||
                        (string) $booking->prestashop_order_line_id !== (string) $lineId
                    ) {
                        return $this->conflict('Hold già associato a un altro ordine');
                    }
                }

                $lock->markBooked($booking->id);

                return $this->confirmSuccess($lock, $booking);
            });
        } catch (Throwable $e) {
            return $this->serverError('Errore confirm');
        }
    }

    // Rilascia un hold non ancora confermato.
    public function release(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hold_token' => 'required|uuid',
        ]);

        try {
            return DB::transaction(function () use ($validated): JsonResponse {
                $lock = SlotLock::query()
                    ->where('hold_token', $validated['hold_token'])
                    ->lockForUpdate()
                    ->first();

                if (! $lock) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Lock non trovato',
                    ], 404);
                }

                if ($lock->isBooked()) {
                    return $this->conflict('Lock già BOOKED');
                }

                if ($lock->isCancelled() || $lock->isExpired()) {
                    return response()->json([
                        'success' => true,
                        'data' => ['status' => $lock->status],
                    ], 200);
                }

                if ($lock->isExpiredHold()) {
                    $lock->markExpired();

                    return response()->json([
                        'success' => true,
                        'data' => ['status' => SlotLock::STATUS_EXPIRED],
                    ], 200);
                }

                $lock->markCancelled();

                return response()->json([
                    'success' => true,
                    'data' => ['status' => SlotLock::STATUS_CANCELLED],
                ], 200);
            });
        } catch (Throwable $e) {
            return $this->serverError('Errore release');
        }
    }

    private function resolveVendorSlot(int $vendorAccountId, int $vendorSlotId): VendorSlot
    {
        $slot = VendorSlot::query()
            ->where('id', $vendorSlotId)
            ->where('vendor_account_id', $vendorAccountId)
            ->first();

        if (! $slot || (isset($slot->is_active) && ! $slot->is_active)) {
            throw ValidationException::withMessages([
                'vendor_slot_id' => ['Slot non valido'],
            ]);
        }

        return $slot;
    }

    private function resolveOfferingProfile(int $vendorAccountId, int $offeringId): VendorOfferingProfile
    {
        $profile = VendorOfferingProfile::query()
            ->where('vendor_account_id', $vendorAccountId)
            ->where('offering_id', $offeringId)
            ->first();

        if (! $profile || (isset($profile->is_published) && ! $profile->is_published)) {
            throw ValidationException::withMessages([
                'offering_id' => ['Offering non valida'],
            ]);
        }

        return $profile;
    }

    private function handleExistingActiveLockOnHold(
        SlotLock $lock,
        int $offeringId,
        ?float $distanceKm,
        ?int $guests,
        CarbonImmutable $now
    ): JsonResponse {
        if (! $lock->isHold()) {
            return $this->conflict('Slot non disponibile');
        }

        if (! $this->isSameFingerprint($lock, $offeringId, $distanceKm, $guests)) {
            return $this->conflict('Slot non disponibile');
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildHoldResponseData($lock, $now),
        ], 200);
    }

    private function isSameFingerprint(
        SlotLock $lock,
        int $offeringId,
        ?float $distanceKm,
        ?int $guests
    ): bool {
        return (int) $lock->offering_id === $offeringId
            && $this->sameNullableNumber($lock->distance_km, $distanceKm)
            && $this->sameNullableNumber($lock->guests, $guests);
    }

    private function sameNullableNumber(mixed $left, mixed $right): bool
    {
        if ($left === null && $right === null) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        return (string) $left === (string) $right;
    }

    private function buildHoldResponseData(SlotLock $lock, CarbonImmutable $now): array
    {
        $expiresAt = $lock->expires_at ? CarbonImmutable::parse($lock->expires_at) : null;
        $breakdown = $lock->pricing_breakdown ?? [];

        return [
            'hold_token' => $lock->hold_token,
            'expires_at' => $expiresAt?->toIso8601String(),
            'lock_id' => $lock->id,
            'ttl_seconds' => $expiresAt ? max(0, $now->diffInSeconds($expiresAt, false)) : 0,
            'pricing' => [
                'offering_id' => $lock->offering_id,
                'base_price' => $breakdown['base_price'] ?? null,
                'final_price' => $lock->quoted_amount,
                'currency' => $lock->currency,
                'breakdown' => $breakdown['breakdown'] ?? [],
            ],
        ];
    }

    private function normalizePricingBreakdown(array $pricing): array
    {
        return [
            'pricing_id' => $pricing['pricing_id'] ?? null,
            'base_price' => $pricing['base_price'] ?? null,
            'final_price' => $pricing['final_price'] ?? null,
            'currency' => $pricing['currency'] ?? null,
            'matched_rule_ids' => $pricing['matched_rule_ids'] ?? [],
            'notes' => $pricing['notes'] ?? [],
            'ignored_rules' => $pricing['ignored_rules'] ?? [],
            'breakdown' => $pricing['breakdown'] ?? [],
        ];
    }

    private function confirmSuccess(SlotLock $lock, Booking $booking): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'lock_id' => $lock->id,
                'booking_id' => $booking->id,
                'status' => SlotLock::STATUS_BOOKED,
                'prestashop_order_id' => $booking->prestashop_order_id,
                'pricing' => [
                    'total_amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                    'breakdown' => $booking->pricing_breakdown,
                ],
            ],
        ], 200);
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driver = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' || $sqlState === '23505' || $driver === 1062;
    }

    private function conflict(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
        ], 409);
    }

    private function unprocessable(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
        ], 422);
    }

    private function serverError(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
        ], 500);
    }
}