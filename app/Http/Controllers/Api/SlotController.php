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

/**
 * SlotController
 * Gestione hold/confirm/release degli slot (Booking Bridge)
 */
class SlotController extends Controller
{
    /**
     * HOLD: mette in hold uno slot per 15 minuti (TTL).
     * Ritorna 409 se lo slot è già occupato (HOLD non scaduto o BOOKED).
     */
    public function hold(Request $request)
    {
        $validated = $request->validate([
            'vendor_account_id' => 'required|integer|exists:vendor_accounts,id',
            'vendor_slot_id'    => 'required|integer|exists:vendor_slots,id',
            'date'              => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        $holdToken = (string) Str::uuid();
        $expiresAt = now()->addMinutes(15);

        try {
            // Tentativo diretto: il DB dovrebbe impedire duplicati con vincoli (consigliato).
            $lock = SlotLock::create([
                'vendor_account_id' => $validated['vendor_account_id'],
                'vendor_slot_id'    => $validated['vendor_slot_id'],
                'date'              => $validated['date'],
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
                ],
            ], 201);
        } catch (QueryException $e) {
            // Collisione su vincolo unico o altra condizione DB: slot occupato
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
     * CONFIRM: dopo pagamento accettato converte l'HOLD in BOOKED e crea la Booking.
     * Requisito: IDEMPOTENTE rispetto a (prestashop_order_id, prestashop_order_line_id).
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'hold_token'               => 'required|uuid',
            'prestashop_order_id'      => 'required|string',
            'prestashop_order_line_id' => 'required|string',
            'paid_at'                  => 'required|date',
            'customer_data'            => 'nullable|array',
            'total_amount'             => 'nullable|numeric|min:0',
        ]);

        // Idempotenza primaria: se esiste già la booking per ordine+riga, ritorna quella
        $existing = Booking::where('prestashop_order_id', $validated['prestashop_order_id'])
            ->where('prestashop_order_line_id', $validated['prestashop_order_line_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => [
                    'lock_id'            => $existing->slot_lock_id,
                    'booking_id'         => $existing->id,
                    'status'             => 'BOOKED',
                    'prestashop_order_id'=> $existing->prestashop_order_id,
                ],
            ], 200);
        }

        try {
            return DB::transaction(function () use ($validated) {

                // Lock pessimista sulla riga SlotLock per evitare doppie confirm concorrenti
                $lock = SlotLock::where('hold_token', $validated['hold_token'])
                    ->lockForUpdate()
                    ->first();

                if (!$lock) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Hold non trovato',
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
                            'lock_id'            => $lock->id,
                            'booking_id'          => $booking?->id,
                            'status'              => 'BOOKED',
                            'prestashop_order_id' => $validated['prestashop_order_id'],
                        ],
                    ], 200);
                }

                // Deve essere HOLD per poter confermare
                if ($lock->status !== 'HOLD') {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Lock non confermabile',
                        'details' => ['status' => $lock->status],
                    ], 409);
                }

                // Scadenza hold
                if ($lock->expires_at && Carbon::parse($lock->expires_at)->isPast()) {
                    $lock->update([
                        'status'    => 'EXPIRED',
                        'is_active' => false,
                    ]);

                    return response()->json([
                        'success' => false,
                        'error'   => 'Hold scaduto',
                    ], 410);
                }

                // Crea booking (se vincolo UNIQUE su (prestashop_order_id, prestashop_order_line_id),
                // eventuali race concorrenti verranno gestite sotto come "già creata").
                try {
                    $booking = Booking::create([
                        'slot_lock_id'             => $lock->id,
                        'vendor_account_id'        => $lock->vendor_account_id,
                        'vendor_slot_id'           => $lock->vendor_slot_id,
                        'event_date'               => $lock->date,

                        'prestashop_order_id'       => $validated['prestashop_order_id'],
                        'prestashop_order_line_id'  => $validated['prestashop_order_line_id'],
                        'paid_at'                   => $validated['paid_at'],

                        'customer_data'             => $validated['customer_data'] ?? null,
                        'total_amount'              => $validated['total_amount'] ?? null,

                        'status' => 'PENDING_VENDOR_CONFIRMATION',
                    ]);
                } catch (QueryException $e) {
                    // Probabile duplicate key per retry/parallel confirm: recupero booking esistente
                    $booking = Booking::where('prestashop_order_id', $validated['prestashop_order_id'])
                        ->where('prestashop_order_line_id', $validated['prestashop_order_line_id'])
                        ->first();

                    if (!$booking) {
                        throw $e;
                    }
                }

                // Finalizza lock: BOOKED deve restare "attiva" perché occupa lo slot
                $lock->update([
                    'status'     => 'BOOKED',
                    'is_active'  => true,
                    // Se la colonna expires_at non è nullable, NON mettere null:
                    // in quel caso usa una data molto futura, o (meglio) rendi nullable via migration.
                    'expires_at' => null,

                    // Se la colonna booking_id esiste in slot_locks, collega.
                    // Se non esiste, questa riga va rimossa per evitare errori SQL.
                    'booking_id' => $booking->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'lock_id'            => $lock->id,
                        'booking_id'          => $booking->id,
                        'status'              => 'BOOKED',
                        'prestashop_order_id' => $validated['prestashop_order_id'],
                    ],
                ], 200);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Errore confirm',
            ], 500);
        }
    }

    /**
     * RELEASE: rilascia un HOLD (non un BOOKED).
     * - Se è già BOOKED: 409
     * - Se HOLD scaduto: 410
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

                if (!$lock) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Lock non trovato',
                    ], 404);
                }

                if ($lock->status === 'BOOKED') {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Lock già BOOKED (non rilasciabile con release)',
                    ], 409);
                }

                if ($lock->status === 'HOLD' && $lock->expires_at && Carbon::parse($lock->expires_at)->isPast()) {
                    $lock->update([
                        'status'    => 'EXPIRED',
                        'is_active' => false,
                    ]);

                    return response()->json([
                        'success' => false,
                        'error'   => 'Hold scaduto',
                    ], 410);
                }

                // Rilascio esplicito
                $lock->update([
                    'status'    => 'CANCELLED',
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
                'error'   => 'Errore release',
            ], 500);
        }
    }
}