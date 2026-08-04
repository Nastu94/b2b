<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VendorSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        if ($request->filled('service_mode')) {
            $request->merge([
                'service_mode' => strtoupper(trim((string) $request->input('service_mode'))),
            ]);
        }

        $validated = $request->validate([
            'city' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],

            // Il numero di ospiti è opzionale ma se fornito deve essere un intero positivo.
            'guests' => ['nullable', 'integer', 'min:1'],

            'prestashop_category_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1', 'exists:categories,id'],
            'event_type_id' => ['nullable', 'integer', 'min:1', 'exists:event_types,id'],
            'offering_id' => ['nullable', 'integer', 'min:1', 'exists:offerings,id'],
            'service_mode' => ['nullable', Rule::in(['MOBILE', 'FIXED_LOCATION'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->vendorSearchService->search($validated);

        return response()->json([
            'success' => true,
            'fallback_used' => $result['fallback_used'],
            'search_mode' => $result['search_mode'],
            'city' => $result['city'],
            'region' => $result['region'],
            'date' => $result['date'],
            'filters' => $result['filters'],
            'total' => $result['total'],
            'data' => $result['data'],
        ], 200);
    }
}
