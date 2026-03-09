<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VendorSearchService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected GeocodingService $geocodingService
    ) {
    }

    // Esegue la ricerca vendor in base a città e data.
    //
    // Nuova regola:
    // - includi solo vendor attivi e disponibili nella data richiesta
    // - considera solo i vendorOfferingProfiles pubblicati
    // - includi sempre i servizi dei vendor della città richiesta
    // - includi i servizi MOBILE fuori città solo se il loro raggio copre la città richiesta
    // - includi i servizi FIXED_LOCATION fuori città, ordinati per distanza
    // - escludi i vendor che non hanno servizi validi per la città richiesta
    // - ordina dando priorità ai vendor della città richiesta, poi per distanza crescente
    public function search(array $params): array
    {
        $city = $this->normalizeText($params['city'] ?? null);
        $date = $params['date'] ?? null;
        $limit = (int) ($params['limit'] ?? 50);

        if ($city === null || $date === null) {
            return [
                'fallback_used' => false,
                'search_mode' => 'city',
                'city' => $params['city'] ?? null,
                'date' => $date,
                'total' => 0,
                'data' => [],
            ];
        }

        $vendors = $this->buildBaseQuery($params)->get();

        // Consideriamo solo i vendor realmente disponibili nella data richiesta.
        $availableVendors = $this->filterAvailableVendorsByDate($vendors, $date);

        // Se non ci sono vendor disponibili in assoluto, ritorniamo vuoto.
        if ($availableVendors->isEmpty()) {
            return [
                'fallback_used' => false,
                'search_mode' => 'city',
                'city' => $params['city'] ?? null,
                'date' => $date,
                'total' => 0,
                'data' => [],
            ];
        }

        // Geocodifichiamo sempre la città richiesta per poter:
        // - calcolare le distanze
        // - verificare il raggio dei servizi MOBILE
        $cityCoordinates = $this->geocodingService->geocodeCity($params['city']);

        // Se il geocoding fallisce, possiamo comunque restituire i vendor della città
        // che matchano per nome città, ma senza risultati fuori città.
        if (!$cityCoordinates) {
            $cityOnlyVendors = $availableVendors
                ->map(function (VendorAccount $vendor) use ($city) {
                    if (!$this->vendorMatchesCity($vendor, $city)) {
                        return null;
                    }

                    $vendor->distance_km = 0.0;
                    $vendor->is_city_match = true;

                    $validProfiles = $vendor->vendorOfferingProfiles
                        ->filter(fn (VendorOfferingProfile $profile) => $this->profileIsSearchable($profile))
                        ->values();

                    if ($validProfiles->isEmpty()) {
                        return null;
                    }

                    $vendor->setRelation('vendorOfferingProfiles', $validProfiles);

                    return $vendor;
                })
                ->filter()
                ->take($limit)
                ->values();

            return [
                'fallback_used' => false,
                'search_mode' => 'city',
                'city' => $params['city'] ?? null,
                'date' => $date,
                'total' => $cityOnlyVendors->count(),
                'data' => $this->groupVendorsByCategory($cityOnlyVendors),
            ];
        }

        $matchedVendors = $availableVendors
            ->map(function (VendorAccount $vendor) use ($city, $cityCoordinates) {
                $lat = $vendor->effectiveLat();
                $lng = $vendor->effectiveLng();

                // I vendor senza coordinate non possono essere ordinati per distanza
                // e non possono essere valutati correttamente fuori città.
                if ($lat === null || $lng === null) {
                    // Se il vendor matcha esattamente la città richiesta, lo teniamo comunque.
                    if ($this->vendorMatchesCity($vendor, $city)) {
                        $vendor->distance_km = 0.0;
                        $vendor->is_city_match = true;

                        $validProfiles = $vendor->vendorOfferingProfiles
                            ->filter(fn (VendorOfferingProfile $profile) => $this->profileIsSearchable($profile))
                            ->values();

                        if ($validProfiles->isEmpty()) {
                            return null;
                        }

                        $vendor->setRelation('vendorOfferingProfiles', $validProfiles);

                        return $vendor;
                    }

                    return null;
                }

                $distance = $this->geocodingService->calculateDistance(
                    (float) $cityCoordinates['lat'],
                    (float) $cityCoordinates['lng'],
                    (float) $lat,
                    (float) $lng
                );

                $vendor->distance_km = round($distance, 1);
                $vendor->is_city_match = $this->vendorMatchesCity($vendor, $city);

                $validProfiles = $vendor->vendorOfferingProfiles
                    ->filter(function (VendorOfferingProfile $profile) use ($vendor) {
                        return $this->profileIsValidForVendorDistance($profile, $vendor);
                    })
                    ->values();

                if ($validProfiles->isEmpty()) {
                    return null;
                }

                $vendor->setRelation('vendorOfferingProfiles', $validProfiles);

                return $vendor;
            })
            ->filter()
            ->sort(function (VendorAccount $a, VendorAccount $b) {
                // Prima i vendor della città richiesta.
                $aPriority = ($a->is_city_match ?? false) ? 0 : 1;
                $bPriority = ($b->is_city_match ?? false) ? 0 : 1;

                if ($aPriority !== $bPriority) {
                    return $aPriority <=> $bPriority;
                }

                // Poi ordina per distanza crescente.
                return ((float) ($a->distance_km ?? 999999)) <=> ((float) ($b->distance_km ?? 999999));
            })
            ->take($limit)
            ->values();

        $hasOutsideCityResults = $matchedVendors->contains(function (VendorAccount $vendor) {
            return ($vendor->is_city_match ?? false) === false;
        });

        return [
            'fallback_used' => $hasOutsideCityResults,
            'search_mode' => $hasOutsideCityResults ? 'mixed' : 'city',
            'city' => $params['city'] ?? null,
            'date' => $date,
            'total' => $matchedVendors->count(),
            'data' => $this->groupVendorsByCategory($matchedVendors),
        ];
    }

    // Costruisce la query base dei vendor attivi.
    private function buildBaseQuery(array $params)
    {
        $query = VendorAccount::query()
            ->where('status', 'ACTIVE')
            ->with([
                'category:id,name,slug,prestashop_category_id',
                'vendorOfferingProfiles' => function ($query) {
                    $query->where('is_published', true)
                        ->with('offering:id,name,slug');
                },
            ]);

        if (isset($params['prestashop_category_id'])) {
            $query->whereHas('category', function ($query) use ($params) {
                $query->where('prestashop_category_id', $params['prestashop_category_id']);
            });
        }

        if (isset($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        return $query;
    }

    // Tiene solo i vendor che hanno almeno uno slot disponibile nella data richiesta.
    private function filterAvailableVendorsByDate(Collection $vendors, string $date): Collection
    {
        return $vendors
            ->filter(function (VendorAccount $vendor) use ($date) {
                $availability = $this->availabilityService->getAvailability($vendor->id, $date, $date);
                $dayAvailability = $availability[$date] ?? [];

                foreach ($dayAvailability as $slot) {
                    if (($slot['status'] ?? null) === 'AVAILABLE') {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    // Verifica se il vendor appartiene alla città richiesta.
    private function vendorMatchesCity(VendorAccount $vendor, string $searchedCity): bool
    {
        $vendorCity = $this->normalizeText($vendor->effectiveCity());

        if ($vendorCity === null || $searchedCity === null) {
            return false;
        }

        return $vendorCity === $searchedCity;
    }

    // Verifica se il profilo servizio è ricercabile.
    private function profileIsSearchable(VendorOfferingProfile $profile): bool
    {
        return $profile->offering !== null;
    }

    // Applica le regole di ricerca al singolo profilo servizio.
    private function profileIsValidForVendorDistance(VendorOfferingProfile $profile, VendorAccount $vendor): bool
    {
        if (!$this->profileIsSearchable($profile)) {
            return false;
        }

        // Regola 1: i servizi dei vendor della città richiesta vanno sempre inclusi.
        if (($vendor->is_city_match ?? false) === true) {
            return true;
        }

        // Regola 2: i servizi MOBILE fuori città devono rispettare il raggio.
        if ($profile->isMobileService()) {
            if (!$profile->hasServiceRadius()) {
                return false;
            }

            return (float) $vendor->distance_km <= (float) $profile->service_radius_km;
        }

        // Regola 3: i servizi FIXED_LOCATION fuori città possono comunque essere mostrati.
        return true;
    }

    // Normalizza una stringa per confronti case-insensitive.
    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return Str::lower(preg_replace('/\s+/', ' ', $value));
    }

    // Raggruppa i vendor per categoria per semplificare il rendering lato frontend.
    private function groupVendorsByCategory(Collection $vendors): array
    {
        return $vendors
            ->groupBy(fn (VendorAccount $vendor) => $vendor->category?->id ?? 'uncategorized')
            ->map(function (Collection $group) {
                $firstVendor = $group->first();

                return [
                    'category' => [
                        'id' => $firstVendor?->category?->id,
                        'name' => $firstVendor?->category?->name,
                        'slug' => $firstVendor?->category?->slug,
                        'prestashop_category_id' => $firstVendor?->category?->prestashop_category_id,
                    ],
                    'vendors' => $group->map(function (VendorAccount $vendor) {
                        $filteredOfferings = $vendor->vendorOfferingProfiles->map(function (VendorOfferingProfile $profile) {
                            return [
                                'id' => $profile->id,
                                'offering_id' => $profile->offering_id,
                                'offering_name' => $profile->offering->name ?? null,
                                'offering_slug' => $profile->offering->slug ?? null,
                                'title' => $profile->title,
                                'short_description' => $profile->short_description,
                                'description' => $profile->description,
                                'service_mode' => $profile->service_mode ?? null,
                                'service_radius_km' => $profile->service_radius_km ?? null,
                                'is_published' => (bool) $profile->is_published,
                                'cover_image_url' => $profile->cover_image_url,
                                'cover_image_path' => $profile->cover_image_path,
                            ];
                        })->values();

                        $result = [
                            'id' => $vendor->id,
                            'company_name' => $vendor->company_name,
                            'city' => $vendor->effectiveCity(),
                            'phone' => $vendor->phone,
                            'offerings_count' => $filteredOfferings->count(),
                            'offerings' => $filteredOfferings,
                        ];

                        if (isset($vendor->distance_km)) {
                            $result['distance_km'] = $vendor->distance_km;
                        }

                        return $result;
                    })->values(),
                ];
            })
            ->values()
            ->all();
    }
}