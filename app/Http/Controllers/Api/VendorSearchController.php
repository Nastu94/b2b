<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorAccount;
use App\Services\GeocodingService;
use Illuminate\Http\Request;

class VendorSearchController extends Controller
{
    /**
     * Dependency injection del servizio geocoding
     */
    public function __construct(
        private GeocodingService $geocodingService
    ) {}

    /**
     * Cerca vendor con filtri opzionali per categoria, città e posizione geografica.
     * 
     * Supporta filtri geografici: se fornite coordinate e raggio,
     * calcola la distanza di ogni vendor e filtra per raggio,
     * ordinando i risultati dal più vicino al più lontano.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Validazione parametri request
        $validated = $request->validate([
            'prestashop_category_id' => 'nullable|integer',
            'category_id' => 'nullable|integer|exists:categories,id',
            'city' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:200',
        ]);

        // Query base: solo vendor attivi
        $query = VendorAccount::query()
            ->where('status', 'ACTIVE')
            ->with([
                'category:id,name,slug,prestashop_category_id',
                'vendorOfferingProfiles' => function($q) {
                    // Solo offering pubblicati
                    $q->where('is_published', true)
                      ->with('offering:id,name,slug');
                }
            ])
            ->withCount('offerings');

        // Filtra per categoria PrestaShop (ID mappato)
        if (isset($validated['prestashop_category_id'])) {
            $query->whereHas('category', function($q) use ($validated) {
                $q->where('prestashop_category_id', $validated['prestashop_category_id']);
            });
        }

        // Filtra per categoria Laravel (ID interno)
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // Filtra per città (ricerca parziale case-insensitive)
        if (isset($validated['city'])) {
            $query->where('legal_city', 'LIKE', '%' . $validated['city'] . '%');
        }

        // Esegue la query
        $vendors = $query->get();

        // Se fornite coordinate, calcola distanze e filtra per raggio
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $userLat = $validated['latitude'];
            $userLng = $validated['longitude'];
            $radius = $validated['radius'] ?? 50; // Default 50km se non specificato

            // Calcola distanza per ogni vendor
            $vendors = $vendors->map(function($vendor) use ($userLat, $userLng) {
                // Calcola distanza solo se vendor ha coordinate valide
                if ($vendor->legal_lat && $vendor->legal_lng) {
                    $distance = $this->geocodingService->calculateDistance(
                        $userLat,
                        $userLng,
                        $vendor->legal_lat,
                        $vendor->legal_lng
                    );
                    // Arrotonda a 1 decimale
                    $vendor->distance_km = round($distance, 1);
                } else {
                    // Vendor senza coordinate: distanza infinita per escluderlo
                    $vendor->distance_km = 999999;
                }
                
                return $vendor;
            });

            // Filtra: solo vendor entro il raggio e con coordinate valide
            $vendors = $vendors->filter(function($vendor) use ($radius) {
                return $vendor->distance_km !== 999999 && $vendor->distance_km <= $radius;
            });

            // Ordina per distanza crescente (più vicini prima)
            $vendors = $vendors->sortBy('distance_km')->values();
        }

        // Formatta la risposta JSON
        return response()->json([
            'success' => true,
            'data' => $vendors->map(function($vendor) {
                $result = [
                    'id' => $vendor->id,
                    'company_name' => $vendor->company_name,
                    'category' => $vendor->category,
                    'city' => $vendor->legal_city,
                    'phone' => $vendor->phone,
                    'offerings_count' => $vendor->offerings_count,
                    'offerings' => $vendor->vendorOfferingProfiles->map(function($profile) {
                        return [
                            'id' => $profile->id,
                            'offering_id' => $profile->offering_id,
                            'offering_name' => $profile->offering->name ?? null,
                            'title' => $profile->title,
                            'short_description' => $profile->short_description,
                            'description' => $profile->description,
                            'is_published' => $profile->is_published,
                        ];
                    }),
                ];

                // Aggiungi campo distance_km solo se calcolato
                if (isset($vendor->distance_km) && $vendor->distance_km !== 999999) {
                    $result['distance_km'] = $vendor->distance_km;
                }

                return $result;
            }),
            'total' => $vendors->count(),
        ], 200);
    }
}