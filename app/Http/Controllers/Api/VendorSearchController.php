<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VendorSearchService;
use Illuminate\Http\Request;

class VendorSearchController extends Controller
{
    public function search(Request $request, VendorSearchService $service)
    {
        $validated = $request->validate([
            'offering_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'slot_slug' => 'required|string|max:80',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius_km' => 'nullable|numeric|min:1|max:200',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $results = $service->search($validated);

        return response()->json([
            'results' => $results
        ]);
    }
}