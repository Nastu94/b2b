<?php

// Namespace del controller API per la gestione degli slot
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlotLock;
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
     * Metodo confirm: conferma l'hold e trasforma lo stato in BOOKED
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request)
    {
        // Valida i dati della richiesta
        $validated = $request->validate([
            'hold_token' => 'required|uuid',
            'order_id' => 'required|string',
            'paid_at' => 'required|date',
        ]);

        // Cerca il lock con il token e stato HOLD attivo
        $lock = SlotLock::where('hold_token', $validated['hold_token'])
            ->where('status', 'HOLD')
            ->where('is_active', true)
            ->first();

        // Se il lock non esiste, ritorna errore 404
        if (!$lock) {
            return response()->json([
                'success' => false,
                'error' => 'Hold non trovato',
            ], 404);
        }

        // Verifica se l'hold è scaduto
        if (Carbon::parse($lock->expires_at)->isPast()) {
            // Aggiorna lo stato a EXPIRED e disattiva il lock
            $lock->update(['status' => 'EXPIRED', 'is_active' => false]);

            return response()->json([
                'success' => false,
                'error' => 'Hold scaduto',
            ], 410);
        }

        // Cambia lo stato da HOLD a BOOKED e azzera la data di scadenza
        $lock->update([
            'status' => 'BOOKED',
            'expires_at' => null,
        ]);

        // Ritorna la conferma della prenotazione
        return response()->json([
            'success' => true,
            'data' => [
                'lock_id' => $lock->id,
                'status' => 'BOOKED',
                'order_id' => $validated['order_id'],
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
