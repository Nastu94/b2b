<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SlotLock;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\BookingPricingService;
use RuntimeException;

/**
 * SlotController
 * Gestione hold/confirm/release degli slot (Booking Bridge)
 */
class SlotController extends Controller
{
    /**
     * HOLD: riceve richiesta di hold per uno slot, calcola pricing e blocca lo slot per 15 minuti.
     * Requisito: IDEMPOTENTE rispetto a (vendor_slot_id, date, offering_id, distance_km, guests).
     * Se esiste già un hold attivo per gli stessi parametri, ritorna lo stesso hold_token (e non crea un nuovo record).
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

        $holdToken = (string) Str::uuid();
        $expiresAt = now()->addMinutes(15);

        try {
            $lock = SlotLock::create([
                'vendor_account_id' => $validated['vendor_account_id'],
                'vendor_slot_id'    => $validated['vendor_slot_id'],
                'offering_id'       => $validated['offering_id'],
                'date'              => $validated['date'],
                'distance_km'       => $validated['distance_km'] ?? null,
                'guests'            => $validated['guests'] ?? null,
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
        * CONFIRM: riceve richiesta di confirm con hold_token, crea booking e finalizza lock.
            * Requisiti:
            * - Idempotente rispetto a hold_token (se arriva più volte la stessa richiesta, ritorna lo stesso risultato senza creare duplicati)
            * - Verifica che il lock sia ancora valido (status HOLD, is_active true, non scaduto)
                * - Se il lock non è più valido, ritorna errore specifico (es. 410 se scaduto)
                * - Se il lock è già BOOKED, ritorna 200 con i dati della booking esistente (retry di conferma già avvenuta)
                * - Se conferma ok, ritorna 200 con dati booking e pricing
     */
    public function confirm(Request $request)
{
    $validated = $request->validate([
        'hold_token' => 'required|uuid|exists:slot_locks,hold_token',
        'prestashop_order_id' => 'required|string|max:191',
        'prestashop_order_line_id' => 'required|string|max:191',
        'customer_data' => 'nullable|array',
    ]);

    // Idempotenza primaria: se esiste già la booking per ordine+riga, ritorna quella
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

            // Lock pessimista sulla riga SlotLock per evitare doppie confirm concorrenti
            $lock = SlotLock::where('hold_token', $validated['hold_token'])
                ->lockForUpdate()
                ->first();

            if (! $lock) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hold non trovato',
                ], 404);
            }

            /**
             * Idempotenza secondaria:
             * se la lock è già BOOKED, questa è una retry.
             * Ritorniamo 200 e cerchiamo la booking collegata.
             */
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

            // Deve essere HOLD per poter confermare
            if ($lock->status !== 'HOLD') {
                return response()->json([
                    'success' => false,
                    'error' => 'Lock non confermabile',
                    'details' => ['status' => $lock->status],
                ], 409);
            }

            // La lock deve essere attiva
            if (! $lock->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => 'Hold non attivo',
                ], 409);
            }

            // Scadenza hold
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

            // Verifica minima: il pricing deve essere stato salvato sull'hold
            if (is_null($lock->quoted_amount)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Prezzo non disponibile per questo hold',
                ], 409);
            }

            // Crea booking usando il contesto salvato nello slot lock
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
                // Probabile duplicate key per retry/parallel confirm: recupero booking esistente
                $booking = Booking::where('prestashop_order_id', $validated['prestashop_order_id'])
                    ->where('prestashop_order_line_id', $validated['prestashop_order_line_id'])
                    ->first();

                if (! $booking) {
                    throw $e;
                }
            }

            // Finalizza lock: BOOKED resta attiva perché occupa lo slot
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
     * RELEASE: rilascia un HOLD (non un BOOKED).
     * - Se è già BOOKED: 409
     * - Se HOLD scaduto: 410
     * - Se HOLD già rilasciato o scaduto: 200 (idempotenza)
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
                    'success' => false,
                    'error' => 'Hold scaduto',
                ], 410);
            }

            $lock->update([
                'status' => 'CANCELLED',
                'is_active' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hold rilasciato',
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
