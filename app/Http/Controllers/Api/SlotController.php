<?php

// Namespace del controller API per la gestione degli slot
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlotLock;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * SlotController
 * Controller per la gestione del blocco e prenotazione degli slot
 */
class SlotController extends Controller
{
    /**
     * Metodo hold: mette in hold uno slot per 15 minuti
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hold(Request $request)
    {
        // Valida i dati della richiesta
        $validated = $request->validate([
            'vendor_account_id' => 'required|integer|exists:vendor_accounts,id',
            'vendor_slot_id' => 'required|integer|exists:vendor_slots,id',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        try {
            // Verifica se esiste già un lock attivo per lo slot
            $existingLock = SlotLock::where('vendor_account_id', $validated['vendor_account_id'])
                ->where('vendor_slot_id', $validated['vendor_slot_id'])
                ->where('date', $validated['date'])
                ->where('is_active', true)
                // Controlla se lo slot è già BOOKED oppure se è in HOLD e non è scaduto
                ->where(function ($q) {
                    $q->where('status', 'BOOKED')
                        ->orWhere(function ($sub) {
                            $sub->where('status', 'HOLD')
                                ->where('expires_at', '>', now());
                        });
                })
                ->exists();

            // Se esiste un lock, lo slot non è disponibile
            if ($existingLock) {
                return response()->json([
                    'success' => false,
                    'error' => 'Slot non disponibile',
                ], 409);
            }

            // Genera un token UUID univoco per l'hold
            $holdToken = Str::uuid()->toString();
            // Imposta la scadenza a 15 minuti da adesso
            $expiresAt = now()->addMinutes(15);

            // Crea un nuovo record di lock con stato HOLD
            $lock = SlotLock::create([
                'vendor_account_id' => $validated['vendor_account_id'],
                'vendor_slot_id' => $validated['vendor_slot_id'],
                'date' => $validated['date'],
                'status' => 'HOLD',
                'hold_token' => $holdToken,
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]);

            // Ritorna la risposta con il token e i dettagli dell'hold
            return response()->json([
                'success' => true,
                'data' => [
                    'hold_token' => $holdToken,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'lock_id' => $lock->id,
                    'ttl_seconds' => 900, // Time To Live: 15 minuti in secondi
                ],
            ], 201);
        } catch (\Exception $e) {
            // Gestisce gli errori generici
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conferma blocco dopo pagamento completato.
     * 
     * Converte hold temporaneo in booking definitivo,
     * crea record booking per tracciabilità.
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'hold_token' => 'required|uuid',
            'prestashop_order_id' => 'required|string',
            'paid_at' => 'required|date',
            'customer_data' => 'nullable|array',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        // Trova hold attivo
        $lock = SlotLock::where('hold_token', $validated['hold_token'])
            ->where('status', 'HOLD')
            ->where('is_active', true)
            ->first();

        if (!$lock) {
            return response()->json([
                'success' => false,
                'error' => 'Hold non trovato',
            ], 404);
        }

        // Verifica scadenza (15 minuti)
        if (Carbon::parse($lock->expires_at)->isPast()) {
            $lock->update(['status' => 'EXPIRED', 'is_active' => false]);

            return response()->json([
                'success' => false,
                'error' => 'Hold scaduto',
            ], 410);
        }

        // Converte HOLD in BOOKED (lock definitivo)
        $lock->update([
            'status' => 'BOOKED',
            'expires_at' => null, // Lock definitivo non scade
        ]);

        // Crea booking per tracciabilità completa
        $booking = Booking::create([
            'vendor_account_id' => $lock->vendor_account_id,
            'slot_lock_id' => $lock->id,
            'prestashop_order_id' => $validated['prestashop_order_id'],
            'event_date' => $lock->date,
            'vendor_slot_id' => $lock->vendor_slot_id,
            'customer_data' => $validated['customer_data'] ?? null,
            'total_amount' => $validated['total_amount'] ?? null,
            'status' => 'PENDING_VENDOR_CONFIRMATION',
            'paid_at' => $validated['paid_at'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'lock_id' => $lock->id,
                'booking_id' => $booking->id,
                'status' => 'BOOKED',
                'prestashop_order_id' => $validated['prestashop_order_id'],
            ],
        ], 200);
    }

    /**
     * Metodo release: rilascia un hold cancellando la prenotazione
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function release(Request $request)
    {
        // Valida il token della richiesta
        $validated = $request->validate([
            'hold_token' => 'required|uuid',
        ]);

        // Cerca il lock attivo con il token fornito
        $lock = SlotLock::where('hold_token', $validated['hold_token'])
            ->where('is_active', true)
            ->first();

        // Se il lock non esiste, ritorna errore 404
        if (!$lock) {
            return response()->json([
                'success' => false,
                'error' => 'Lock non trovato',
            ], 404);
        }

        // Cambia lo stato a CANCELLED e disattiva il lock
        $lock->update([
            'status' => 'CANCELLED',
            'is_active' => false,
        ]);

        // Ritorna la conferma del rilascio
        return response()->json([
            'success' => true,
            'message' => 'Hold rilasciato',
        ], 200);
    }
}
