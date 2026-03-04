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
     * Cerca vendor con filtri categoria e posizione geografica.
     * 
     * Supporta 2 modalità per filtro geografico:
     * 1) Coordinate dirette (latitude + longitude)
     * 2) Indirizzo completo (address_line1, address_city, etc.) - Laravel fa geocoding automatico
     * 
     * Se geocoding dell'indirizzo fallisce, continua senza filtro geografico.
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
            
            // Opzione 1: Coordinate dirette
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
            // Opzione 2: Indirizzo (Laravel fa geocoding automatico)
            'address_line1' => 'nullable|string',
            'address_line2' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'address_city' => 'nullable|string',
            'region' => 'nullable|string',
            
            'radius' => 'nullable|integer|min:1|max:200',
        ]);

        // Query base: solo vendor attivi
        $query = VendorAccount::query()
            ->where('status', 'ACTIVE')
            ->with([
                'category:id,name,slug,prestashop_category_id',
                'vendorOfferingProfiles' => function ($q) {
                    // Solo offering pubblicati
                    $q->where('is_published', true)
                        ->with('offering:id,name,slug');
                }
            ])
            ->withCount('offerings');

        // Filtra per categoria PrestaShop (ID mappato)
        if (isset($validated['prestashop_category_id'])) {
            $query->whereHas('category', function ($q) use ($validated) {
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

        // Gestione coordinate per calcolo distanza
        $userLat = null;
        $userLng = null;

        // Opzione 1: Coordinate dirette fornite dal cliente
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $userLat = $validated['latitude'];
            $userLng = $validated['longitude'];
        }
        // Opzione 2: Indirizzo fornito - Laravel fa geocoding automatico
        elseif (isset($validated['address_line1']) || isset($validated['address_city'])) {
            // Prepara dati indirizzo per geocoding service
            $addressData = [
                'address_line1' => $validated['address_line1'] ?? null,
                'address_line2' => $validated['address_line2'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'city' => $validated['address_city'] ?? null,
                'region' => $validated['region'] ?? null,
            ];

            // Chiama geocoding service con fallback progressivo
            $coords = $this->geocodingService->geocodeItaly($addressData);

            if ($coords) {
                $userLat = $coords['lat'];
                $userLng = $coords['lng'];
            }
            // Se geocoding fallisce, $userLat e $userLng restano null
        }

        // Se abbiamo coordinate (dirette O ottenute da geocoding), calcola distanze
        if ($userLat && $userLng) {
            $radius = $validated['radius'] ?? 50; // Default 50km

            // Calcola distanza per ogni vendor
            $vendors = $vendors->map(function ($vendor) use ($userLat, $userLng) {

                // Usa coordinate operative se disponibili, altrimenti legali
                $lat = $vendor->operational_lat ?? $vendor->legal_lat;
                $lng = $vendor->operational_lng ?? $vendor->legal_lng;

                // Se le coordinate sono valide, calcola la distanza
                if ($lat && $lng) {
                    $distance = $this->geocodingService->calculateDistance($userLat, $userLng, $lat, $lng);
                    $vendor->distance_km = round($distance, 1);
                } else {
                    // Vendor senza coordinate: distanza infinita per escluderlo
                    $vendor->distance_km = 999999;
                }

                return $vendor;
            });

            // Filtra: solo vendor entro il raggio e con coordinate valide
            $vendors = $vendors->filter(function ($vendor) use ($radius) {
                return $vendor->distance_km !== 999999 && $vendor->distance_km <= $radius;
            });

            // Ordina per distanza crescente (più vicini prima)
            $vendors = $vendors->sortBy('distance_km')->values();
        }

        // Formatta la risposta JSON
        return response()->json([
            'success' => true,
            'data' => $vendors->map(function ($vendor) {
                $result = [
                    'id' => $vendor->id,
                    'company_name' => $vendor->company_name,
                    'category' => $vendor->category,
                    'city' => $vendor->legal_city,
                    'phone' => $vendor->phone,
                    'offerings_count' => $vendor->offerings_count,
                    'offerings' => $vendor->vendorOfferingProfiles->map(function ($profile) {
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