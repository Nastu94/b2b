<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VendorSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorSearchController extends Controller
{
    // Il controller valida l'input e delega la logica di ricerca
    // al service dedicato.
    public function __construct(
        private VendorSearchService $vendorSearchService
    ) {
    }

    // Ricerca vendor basata su città e data.
    //
    // Parametri obbligatori:
    // - city
    // - date
    //
    // Parametri opzionali:
    // - guests
    // - prestashop_category_id
    // - category_id
    // - limit
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],

            // Il numero di ospiti è opzionale ma se fornito deve essere un intero positivo.
            'guests' => ['nullable', 'integer', 'min:1'],

            'prestashop_category_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->vendorSearchService->search($validated);

        return response()->json([
            'success' => true,
            'fallback_used' => $result['fallback_used'],
            'search_mode' => $result['search_mode'],
            'city' => $result['city'],
            'date' => $result['date'],
            'total' => $result['total'],
            'data' => $result['data'],
        ], 200);
    }
}