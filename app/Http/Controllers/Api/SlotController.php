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
            'vendor_account_id'      => 'required|integer|exists:vendor_accounts,id',
            'vendor_slot_id'         => 'required|integer|exists:vendor_slots,id',
            'offering_id'            => 'required|integer|exists:offerings,id',
            'date'                   => 'required|date_format:Y-m-d|after_or_equal:today',
            'distance_km'            => 'nullable|numeric|min:0',
            'guests'                 => 'nullable|integer|min:1',
            'prestashop_shop_id'     => 'nullable|integer',
            'prestashop_cart_id'     => 'nullable|integer',
            'prestashop_customer_id' => 'nullable|integer',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (empty($idempotencyKey)) {
            throw new \App\Exceptions\BookingBridge\MissingIdempotencyKeyException();
        }
        $idempotencyKey = strtolower(trim($idempotencyKey));
        if (!preg_match('/^[a-f0-9]{32}$/', $idempotencyKey)) {
            throw new \App\Exceptions\BookingBridge\InvalidIdempotencyKeyException();
        }

        $vendorAccountId = (int) $validated['vendor_account_id'];
        $vendorSlotId = (int) $validated['vendor_slot_id'];
        $offeringId = (int) $validated['offering_id'];
        $date = $validated['date'];
        $distanceKm = array_key_exists('distance_km', $validated) && $validated['distance_km'] !== null ? round((float) $validated['distance_km'], 2) : null;
        $guests = array_key_exists('guests', $validated) && $validated['guests'] !== null ? (int) $validated['guests'] : null;
        
        $shopId = isset($validated['prestashop_shop_id']) ? (int) $validated['prestashop_shop_id'] : null;
        $cartId = isset($validated['prestashop_cart_id']) ? (int) $validated['prestashop_cart_id'] : null;
        $customerId = isset($validated['prestashop_customer_id']) ? (int) $validated['prestashop_customer_id'] : null;

        try {
            $vendorAccount = \App\Models\VendorAccount::findOrFail($vendorAccountId);
            $bookingCapacityMode = $vendorAccount->bookingCapacityMode();

            if ($bookingCapacityMode === \App\Models\VendorAccount::BOOKING_MULTIPLE_BY_OFFERING && empty($offeringId)) {
                return $this->unprocessable('L\'offering_id è obbligatorio per questa modalità di prenotazione');
            }

            $this->resolveVendorSlot($vendorAccountId, $vendorSlotId);
            $profile = $this->resolveOfferingProfile($vendorAccountId, $offeringId);

            if ($guests !== null && $profile->exceedsCapacity($guests)) {
                return $this->unprocessable('Numero ospiti non supportato');
            }

            $now = CarbonImmutable::now();
            
            $activeSlotKey = SlotLock::makeActiveSlotKey($vendorAccountId, $vendorSlotId, $date, $bookingCapacityMode, $offeringId);

            $existingIdempotentLock = null;
            if ($idempotencyKey) {
                $existingIdempotentLock = SlotLock::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
            }

            if (! $existingIdempotentLock) {
                $availabilityService->assertSlotBookable(
                    vendorAccountId: $vendorAccountId,
                    vendorSlotId: $vendorSlotId,
                    date: $date,
                    offeringId: $offeringId,
                    guests: $guests
                );
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\App\Exceptions\SlotUnavailableException $e) {
            return response()->json([
                'success' => false,
                'code' => 'SLOT_UNAVAILABLE',
                'error' => 'Slot non disponibile'
            ], 409);
        } catch (RuntimeException $e) {
            return $this->unprocessable($e->getMessage());
        } catch (\App\Exceptions\BookingBridge\BookingBridgeApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Errore imprevisto in preliminari Hold: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
                $shopId,
                $cartId,
                $customerId,
                $activeSlotKey,
                $idempotencyKey,
                $bookingPricingService
            ): JsonResponse {
                $now = CarbonImmutable::now();
                $expiresAt = $now->addMinutes(self::HOLD_TTL_MINUTES);

                if ($idempotencyKey) {
                    $idempotentLock = SlotLock::query()
                        ->where('idempotency_key', $idempotencyKey)
                        ->lockForUpdate()
                        ->first();

                    if ($idempotentLock) {
                        return $this->handleIdempotentLockOnHold(
                            $idempotentLock,
                            $vendorAccountId,
                            $vendorSlotId,
                            $date,
                            $offeringId,
                            $distanceKm,
                            $guests,
                            $shopId,
                            $cartId,
                            $customerId,
                            $now
                        );
                    }
                }

                $activeLock = SlotLock::query()
                    ->where('active_slot_key', $activeSlotKey)
                    ->active()
                    ->lockForUpdate()
                    ->first();

                if ($activeLock && $activeLock->isExpiredHold($now)) {
                    $activeLock->markExpired();
                    $activeLock = null;
                }

                if ($activeLock) {
                    throw new \App\Exceptions\BookingBridge\SlotUnavailableException();
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
                        'idempotency_key'   => $idempotencyKey,
                        'vendor_account_id' => $vendorAccountId,
                        'vendor_slot_id'    => $vendorSlotId,
                        'offering_id'       => $offeringId,
                        'date'              => $date,
                        'distance_km'       => $distanceKm,
                        'guests'            => $guests,
                        'prestashop_shop_id'=> $shopId,
                        'prestashop_cart_id'=> $cartId,
                        'prestashop_customer_id'=> $customerId,
                        'quoted_amount'     => $pricing['final_price'],
                        'currency'          => $pricing['currency'],
                        'pricing_breakdown' => $this->normalizePricingBreakdown($pricing),
                        'status'            => SlotLock::STATUS_HOLD,
                        'hold_token'        => (string) Str::uuid(),
                        'expires_at'        => $expiresAt,
                        'is_active'         => true,
                        'active_slot_key'   => $activeSlotKey,
                    ]);
                } catch (QueryException $e) {
                    if ($this->isUniqueConstraintViolation($e)) {
                        // Rileggi per idempotency_key
                        if ($idempotencyKey) {
                            $idempotentLock = SlotLock::query()
                                ->where('idempotency_key', $idempotencyKey)
                                ->first();

                            if ($idempotentLock) {
                                return $this->handleIdempotentLockOnHold(
                                    $idempotentLock,
                                    $vendorAccountId,
                                    $vendorSlotId,
                                    $date,
                                    $offeringId,
                                    $distanceKm,
                                    $guests,
                                    $shopId,
                                    $cartId,
                                    $customerId,
                                    $now
                                );
                            }
                        }

                        // Rileggi per logica di occupazione slot
                        $activeLock = SlotLock::query()
                            ->where('active_slot_key', $activeSlotKey)
                            ->active()
                            ->first();

                        if ($activeLock && !$activeLock->isExpiredHold($now)) {
                            throw new \App\Exceptions\BookingBridge\SlotUnavailableException();
                        }
                    }

                    throw $e;
                }

                return response()->json([
                    'success' => true,
                    'data' => $this->buildHoldResponseData($lock, $now),
                ], 201);
            });
        } catch (\App\Exceptions\BookingBridge\BookingBridgeApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Errore nascosto in transazione Hold: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->serverError('Errore hold');
        }
    }

    // Conferma un hold dopo il pagamento.
    public function confirm(Request $request, \App\Services\CommissionResolver $commissionResolver): JsonResponse
    {
        $validated = $request->validate([
            'hold_token'               => 'required|uuid',
            'prestashop_order_id'      => 'required|string|max:191',
            'prestashop_order_line_id' => 'required|string|max:191',
            'customer_data'            => 'nullable|array',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (!empty($idempotencyKey)) {
            $idempotencyKey = strtolower(trim($idempotencyKey));
            if (!preg_match('/^[a-f0-9]{32}$/', $idempotencyKey)) {
                throw new \App\Exceptions\BookingBridge\InvalidIdempotencyKeyException();
            }
        }

        try {
            return DB::transaction(function () use ($validated, $idempotencyKey, $commissionResolver): JsonResponse {
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
                        return $this->conflict('INCOHERENT_BOOKING', 'Incoerenza booking/lock');
                    }

                    if ($lock->hold_token !== $holdToken) {
                        throw new \App\Exceptions\BookingBridge\IdempotencyMismatchException('Ordine già associato a un hold diverso');
                    }

                    $this->checkIdempotencyKey($lock, $idempotencyKey);

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
                    throw new \App\Exceptions\BookingBridge\LockTerminatedException('Hold non trovato', null, 404);
                }

                $this->checkIdempotencyKey($lock, $idempotencyKey);

                if ($lock->isBooked()) {
                    $booking = Booking::query()
                        ->where('slot_lock_id', $lock->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $booking) {
                        return $this->conflict('INCOHERENT_BOOKING', 'Lock BOOKED senza booking associata');
                    }

                    if (
                        (string) $booking->prestashop_order_id !== (string) $orderId ||
                        (string) $booking->prestashop_order_line_id !== (string) $lineId
                    ) {
                        return $this->conflict('IDEMPOTENCY_MISMATCH', 'Hold già confermato per un altro ordine');
                    }

                    return $this->confirmSuccess($lock, $booking);
                }

                if (! $lock->canBeConfirmed($now)) {
                    if ($lock->isExpiredHold($now)) {
                        $lock->markExpired();

                        throw new \App\Exceptions\BookingBridge\LockTerminatedException('Hold scaduto');
                    }

                    throw new \App\Exceptions\BookingBridge\LockTerminatedException('Lock non confermabile');
                }

                if ($lock->quoted_amount === null || $lock->currency === null) {
                    return $this->conflict('INCOHERENT_BOOKING', 'Prezzo non disponibile per questo hold');
                }

                $booking = Booking::query()
                    ->where('slot_lock_id', $lock->id)
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    try {
                        $vendor = \App\Models\VendorAccount::with('category')->find($lock->vendor_account_id);
                        if (!$vendor) {
                            throw new \App\Exceptions\BookingBridge\ConfigurationErrorException("Vendor account non trovato.");
                        }

                        $commissionResult = $commissionResolver->resolve($vendor);
                        $isCommissionBased = $commissionResult['is_commission_based'];
                        $commissionRate = $commissionResult['commission_rate'];
                        $commissionAmount = $isCommissionBased ? round(($lock->quoted_amount * $commissionRate) / 100, 2) : 0;

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
                            throw new \App\Exceptions\BookingBridge\IdempotencyMismatchException('Ordine già associato a un hold diverso');
                        }
                    }
                } else {
                    if (
                        (string) $booking->prestashop_order_id !== (string) $orderId ||
                        (string) $booking->prestashop_order_line_id !== (string) $lineId
                    ) {
                        throw new \App\Exceptions\BookingBridge\IdempotencyMismatchException('Hold già associato a un altro ordine');
                    }
                }

                $lock->markBooked($booking->id);

                return $this->confirmSuccess($lock, $booking);
            });
        } catch (\App\Exceptions\BookingBridge\BookingBridgeApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Errore nascosto in Confirm: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->serverError('Impossibile confermare la prenotazione.');
        }
    }

    // Rilascia un hold non ancora confermato.
    public function release(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hold_token' => 'required|uuid',
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (!empty($idempotencyKey)) {
            $idempotencyKey = strtolower(trim($idempotencyKey));
            if (!preg_match('/^[a-f0-9]{32}$/', $idempotencyKey)) {
                throw new \App\Exceptions\BookingBridge\InvalidIdempotencyKeyException();
            }
        }

        try {
            return DB::transaction(function () use ($validated, $idempotencyKey): JsonResponse {
                $lock = SlotLock::query()
                    ->where('hold_token', $validated['hold_token'])
                    ->lockForUpdate()
                    ->first();

                if (! $lock) {
                    throw new \App\Exceptions\BookingBridge\LockTerminatedException('Lock non trovato', null, 404);
                }

                $this->checkIdempotencyKey($lock, $idempotencyKey);

                if ($lock->isBooked()) {
                    throw new \App\Exceptions\BookingBridge\LockTerminatedException('Lock già BOOKED', null, 409);
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
        } catch (\App\Exceptions\BookingBridge\BookingBridgeApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Errore nascosto in transazione Release: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->serverError('Errore release');
        }
    }

    private function checkIdempotencyKey(SlotLock $lock, ?string $requestKey): void
    {
        if ($lock->idempotency_key !== null) {
            if (empty($requestKey)) {
                throw new \App\Exceptions\BookingBridge\MissingIdempotencyKeyException('Idempotency-Key header mancante ma richiesto');
            }
            if ($lock->idempotency_key !== $requestKey) {
                throw new \App\Exceptions\BookingBridge\IdempotencyMismatchException('Chiave idempotente non corrispondente');
            }
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
            ->bookable()
            ->first();

        if (! $profile) {
            throw ValidationException::withMessages([
                'offering_id' => ['Offering non valida o non prenotabile'],
            ]);
        }

        return $profile;
    }

    private function handleIdempotentLockOnHold(
        SlotLock $lock,
        int $vendorAccountId,
        int $vendorSlotId,
        string $date,
        int $offeringId,
        ?float $distanceKm,
        ?int $guests,
        ?int $shopId,
        ?int $cartId,
        ?int $customerId,
        CarbonImmutable $now
    ): JsonResponse {
        if (! $this->isSameData($lock, $vendorAccountId, $vendorSlotId, $date, $offeringId, $distanceKm, $guests, $shopId, $cartId, $customerId)) {
            throw new \App\Exceptions\BookingBridge\IdempotencyMismatchException('Chiave idempotente utilizzata con dati differenti');
        }

        if ($lock->isExpiredHold($now) || $lock->isCancelled() || $lock->isBooked() || $lock->status === 'EXPIRED') {
            throw new \App\Exceptions\BookingBridge\LockTerminatedException('Chiave idempotente utilizzata per un lock terminale. Si prega di generarne una nuova.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildHoldResponseData($lock, $now),
        ], 200);
    }

    private function isSameData(
        SlotLock $lock,
        int $vendorAccountId,
        int $vendorSlotId,
        string $date,
        int $offeringId,
        ?float $distanceKm,
        ?int $guests,
        ?int $shopId,
        ?int $cartId,
        ?int $customerId
    ): bool {
        return (int) $lock->vendor_account_id === $vendorAccountId
            && (int) $lock->vendor_slot_id === $vendorSlotId
            && $lock->date->format('Y-m-d') === $date
            && (int) $lock->offering_id === $offeringId
            && $this->sameNullableFloat($lock->distance_km, $distanceKm)
            && $this->sameNullableInt($lock->guests, $guests)
            && $this->sameNullableInt($lock->prestashop_shop_id, $shopId)
            && $this->sameNullableInt($lock->prestashop_cart_id, $cartId)
            && $this->sameNullableInt($lock->prestashop_customer_id, $customerId);
    }

    private function sameNullableFloat(mixed $left, mixed $right, int $decimals = 2): bool
    {
        if ($left === null && $right === null) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        $scaledLeft = (int) round((float) $left * (10 ** $decimals));
        $scaledRight = (int) round((float) $right * (10 ** $decimals));

        return $scaledLeft === $scaledRight;
    }

    private function sameNullableInt(mixed $left, mixed $right): bool
    {
        if ($left === null && $right === null) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        return (int) $left === (int) $right;
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

    private function conflict(string $code, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
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