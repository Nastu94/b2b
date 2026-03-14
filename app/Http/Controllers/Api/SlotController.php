<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SlotLock;
use App\Services\BookingPricingService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SlotController extends Controller
{
    /**
     * HOLD idempotente:
     * - stessa richiesta attiva => ritorna stesso hold_token
     * - se esiste lock storico CANCELLED/EXPIRED, ne crea uno nuovo
     * - se lo slot è occupato da altro hold attivo o BOOKED => 409
     */
    public function hold(Request $request, BookingPricingService $bookingPricingService)
    {
        $validated = $request->validate([
            'vendor_account_id' => 'required|integer|exists:vendor_accounts,id',
            'vendor_slot_id'    => 'required|integer|exists:vendor_slots,id',
            'offering_id'       => 'required|integer|exists:offerings,id',
            'date'              => 'required|date_format:Y-m-d|after_or_equal:today',
            'distance_km'       => 'nullable|numeric|min:0',
            'guests'            => 'nullable|integer|min:1',
        ]);

        try {
            $pricing = $bookingPricingService->resolveForBooking(
                vendorAccountId: (int) $validated['vendor_account_id'],
                offeringId: (int) $validated['offering_id'],
                eventDate: $validated['date'],
                distanceKm: array_key_exists('distance_km', $validated) ? (float) $validated['distance_km'] : null,
                guests: array_key_exists('guests', $validated) ? (int) $validated['guests'] : null,
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Errore calcolo pricing',
            ], 500);
        }

        try {
            return DB::transaction(function () use ($validated, $pricing) {
                $distanceKm = array_key_exists('distance_km', $validated) ? (float) $validated['distance_km'] : null;
                $guests = array_key_exists('guests', $validated) ? (int) $validated['guests'] : null;
                $expiresAt = now()->addMinutes(15);

                $matchingLockQuery = SlotLock::where('vendor_account_id', $validated['vendor_account_id'])
                    ->where('vendor_slot_id', $validated['vendor_slot_id'])
                    ->where('offering_id', $validated['offering_id'])
                    ->where('date', $validated['date'])
                    ->where(function ($q) use ($distanceKm) {
                        if ($distanceKm === null) {
                            $q->whereNull('distance_km');
                        } else {
                            $q->where('distance_km', $distanceKm);
                        }
                    })
                    ->where(function ($q) use ($guests) {
                        if ($guests === null) {
                            $q->whereNull('guests');
                        } else {
                            $q->where('guests', $guests);
                        }
                    })
                    ->lockForUpdate();

                $existingMatchingLocks = $matchingLockQuery
                    ->orderByDesc('id')
                    ->get();

                foreach ($existingMatchingLocks as $existingLock) {
                    if ($existingLock->status === 'BOOKED' && $existingLock->is_active) {
                        return response()->json([
                            'success' => false,
                            'error'   => 'Slot non disponibile',
                        ], 409);
                    }

                    if (
                        $existingLock->status === 'HOLD' &&
                        $existingLock->is_active &&
                        $existingLock->expires_at &&
                        Carbon::parse($existingLock->expires_at)->isFuture()
                    ) {
                        return response()->json([
                            'success' => true,
                            'data' => [
                                'hold_token'  => $existingLock->hold_token,
                                'expires_at'  => Carbon::parse($existingLock->expires_at)->toIso8601String(),
                                'lock_id'     => $existingLock->id,
                                'ttl_seconds' => now()->diffInSeconds(Carbon::parse($existingLock->expires_at), false),
                                'pricing' => [
                                    'offering_id' => $validated['offering_id'],
                                    'base_price' => $pricing['base_price'],
                                    'final_price' => $pricing['final_price'],
                                    'currency' => $pricing['currency'],
                                    'breakdown' => $pricing['breakdown'],
                                    'notes' => $pricing['notes'],
                                ],
                            ],
                        ], 200);
                    }

                    if (
                        $existingLock->status === 'HOLD' &&
                        $existingLock->is_active &&
                        $existingLock->expires_at &&
                        Carbon::parse($existingLock->expires_at)->isPast()
                    ) {
                        $existingLock->update([
                            'status' => 'EXPIRED',
                            'is_active' => false,
                        ]);
                    }
                }

                $holdToken = (string) Str::uuid();

                $lock = SlotLock::create([
                    'vendor_account_id' => $validated['vendor_account_id'],
                    'vendor_slot_id'    => $validated['vendor_slot_id'],
                    'offering_id'       => $validated['offering_id'],
                    'date'              => $validated['date'],
                    'distance_km'       => $distanceKm,
                    'guests'            => $guests,
                    'quoted_amount'     => $pricing['final_price'],
                    'currency'          => $pricing['currency'],
                    'pricing_breakdown' => [
                        'pricing_id' => $pricing['pricing_id'],
                        'base_price' => $pricing['base_price'],
                        'final_price' => $pricing['final_price'],
                        'currency' => $pricing['currency'],
                        'matched_rule_ids' => $pricing['matched_rule_ids'],
                        'notes' => $pricing['notes'],
                        'ignored_rules' => $pricing['ignored_rules'],
                        'breakdown' => $pricing['breakdown'],
                    ],
                    'status'            => 'HOLD',
                    'hold_token'        => $holdToken,
                    'expires_at'        => $expiresAt,
                    'is_active'         => true,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'hold_token'  => $holdToken,
                        'expires_at'  => $expiresAt->toIso8601String(),
                        'lock_id'     => $lock->id,
                        'ttl_seconds' => 900,
                        'pricing' => [
                            'offering_id' => $validated['offering_id'],
                            'base_price' => $pricing['base_price'],
                            'final_price' => $pricing['final_price'],
                            'currency' => $pricing['currency'],
                            'breakdown' => $pricing['breakdown'],
                            'notes' => $pricing['notes'],
                        ],
                    ],
                ], 201);
            });
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Slot non disponibile',
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Errore hold',
            ], 500);
        }
    }

    /**
     * CONFIRM idempotente.
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'hold_token' => 'required|uuid|exists:slot_locks,hold_token',
            'prestashop_order_id' => 'required|string|max:191',
            'prestashop_order_line_id' => 'required|string|max:191',
            'customer_data' => 'nullable|array',
        ]);

        $existing = Booking::where('prestashop_order_id', $validated['prestashop_order_id'])
            ->where('prestashop_order_line_id', $validated['prestashop_order_line_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => [
                    'lock_id' => $existing->slot_lock_id,
                    'booking_id' => $existing->id,
                    'status' => 'BOOKED',
                    'prestashop_order_id' => $existing->prestashop_order_id,
                ],
            ], 200);
        }

        try {
            return DB::transaction(function () use ($validated) {
                $lock = SlotLock::where('hold_token', $validated['hold_token'])
                    ->lockForUpdate()
                    ->first();

                if (! $lock) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Hold non trovato',
                    ], 404);
                }

                if ($lock->status === 'BOOKED') {
                    $booking = Booking::where('slot_lock_id', $lock->id)->first();

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'lock_id' => $lock->id,
                            'booking_id' => $booking?->id,
                            'status' => 'BOOKED',
                            'prestashop_order_id' => $validated['prestashop_order_id'],
                        ],
                    ], 200);
                }

                if ($lock->status !== 'HOLD') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Lock non confermabile',
                        'details' => ['status' => $lock->status],
                    ], 409);
                }

                if (! $lock->is_active) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Hold non attivo',
                    ], 409);
                }

                if ($lock->expires_at && Carbon::parse($lock->expires_at)->isPast()) {
                    $lock->update([
                        'status' => 'EXPIRED',
                        'is_active' => false,
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Hold scaduto',
                    ], 410);
                }

                if (is_null($lock->quoted_amount)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Prezzo non disponibile per questo hold',
                    ], 409);
                }

                try {
                    $booking = Booking::create([
                        'slot_lock_id' => $lock->id,
                        'vendor_account_id' => $lock->vendor_account_id,
                        'offering_id' => $lock->offering_id,
                        'vendor_slot_id' => $lock->vendor_slot_id,
                        'event_date' => $lock->date,
                        'distance_km' => $lock->distance_km,
                        'guests' => $lock->guests,
                        'prestashop_order_id' => $validated['prestashop_order_id'],
                        'prestashop_order_line_id' => $validated['prestashop_order_line_id'],
                        'customer_data' => $validated['customer_data'] ?? null,
                        'total_amount' => $lock->quoted_amount,
                        'currency' => $lock->currency,
                        'pricing_breakdown' => $lock->pricing_breakdown,
                        'status' => 'PENDING_VENDOR_CONFIRMATION',
                    ]);
                } catch (QueryException $e) {
                    $booking = Booking::where('prestashop_order_id', $validated['prestashop_order_id'])
                        ->where('prestashop_order_line_id', $validated['prestashop_order_line_id'])
                        ->first();

                    if (! $booking) {
                        throw $e;
                    }
                }

                $lock->update([
                    'status' => 'BOOKED',
                    'is_active' => true,
                    'expires_at' => null,
                    'booking_id' => $booking->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'lock_id' => $lock->id,
                        'booking_id' => $booking->id,
                        'status' => 'BOOKED',
                        'prestashop_order_id' => $validated['prestashop_order_id'],
                        'pricing' => [
                            'total_amount' => $booking->total_amount,
                            'currency' => $booking->currency,
                            'breakdown' => $booking->pricing_breakdown,
                        ],
                    ],
                ], 200);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore confirm',
            ], 500);
        }
    }

    /**
     * RELEASE idempotente.
     */
    public function release(Request $request)
    {
        $validated = $request->validate([
            'hold_token' => 'required|uuid',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $lock = SlotLock::where('hold_token', $validated['hold_token'])
                    ->lockForUpdate()
                    ->first();

                if (! $lock) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Lock non trovato',
                    ], 404);
                }

                if ($lock->status === 'BOOKED') {
                    return response()->json([
                        'success' => false,
                        'error' => 'Lock già BOOKED (non rilasciabile con release)',
                    ], 409);
                }

                if ($lock->status === 'CANCELLED') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Hold già rilasciato',
                    ], 200);
                }

                if ($lock->status === 'EXPIRED') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Hold già scaduto',
                    ], 200);
                }

                if ($lock->status === 'HOLD' && $lock->expires_at && Carbon::parse($lock->expires_at)->isPast()) {
                    $lock->update([
                        'status' => 'EXPIRED',
                        'is_active' => false,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Hold scaduto e chiuso correttamente',
                        'data' => [
                            'status' => 'EXPIRED',
                        ],
                    ], 200);
                }

                $lock->update([
                    'status' => 'CANCELLED',
                    'is_active' => false,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Hold rilasciato',
                    'data' => [
                        'status' => 'CANCELLED',
                    ],
                ], 200);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore release',
            ], 500);
        }
    }
}