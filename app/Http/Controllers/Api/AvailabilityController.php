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
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d',
        ]);

        try {
            $availability = $this->availabilityService->getAvailability(
                $validated['vendor_account_id'],
                $validated['from'],
                $validated['to']
            );

            return response()->json([
                'success' => true,
                'data' => $availability,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}