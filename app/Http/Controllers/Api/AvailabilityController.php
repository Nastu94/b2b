<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'vendor_account_id' => 'required|integer|exists:vendor_accounts,id',
            'from'              => 'required|date_format:Y-m-d',
            'to'                => 'required|date_format:Y-m-d',
            // offering_id e guests sono opzionali ma necessari per il controllo capacita'
            'offering_id'       => 'nullable|integer|exists:offerings,id',
            'guests'            => 'nullable|integer|min:1',
        ]);

        try {
            $availability = $this->availabilityService->getAvailability(
                vendorAccountId: (int) $validated['vendor_account_id'],
                from:            $validated['from'],
                to:              $validated['to'],
                offeringId:      isset($validated['offering_id']) ? (int) $validated['offering_id'] : null,
                guests:          isset($validated['guests']) ? (int) $validated['guests'] : null,
            );

            return response()->json([
                'success' => true,
                'data'    => $availability,
            ], 200);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'error'   => 'Errore interno del server.',
            ], 500);
        }
    }
}