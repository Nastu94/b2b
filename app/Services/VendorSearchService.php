<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VendorSearchService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected GeocodingService $geocodingService
    ) {
    }

    // Ricerca vendor e offerings validi per città, data e guests.
    public function search(array $params): array
    {
        $city = $this->normalizeText($params['city'] ?? null);
        $date = $params['date'] ?? null;
        $guests = isset($params['guests']) && $params['guests'] !== null
            ? (int) $params['guests']
            : null;
        $limit = max(1, (int) ($params['limit'] ?? 50));

        if ($city === null || $date === null) {
            return $this->emptyResult($params['city'] ?? null, $date);
        }

        $cityCoordinates = $this->geocodingService->geocodeCity($params['city']);

        $query = $this->buildBaseQuery($params);

        // Filtro spaziale a database per scartare i vendor fuori raggio prima dell'idratazione dei modelli.
        if ($cityCoordinates) {
            $lng = sprintf('%F', (float) $cityCoordinates['lng']);
            $lat = sprintf('%F', (float) $cityCoordinates['lat']);
            $distSql = "(ST_Distance_Sphere(point(COALESCE(vendor_accounts.operational_lng, vendor_accounts.legal_lng), COALESCE(vendor_accounts.operational_lat, vendor_accounts.legal_lat)), point($lng, $lat)) / 1000)";

            $query->selectRaw("vendor_accounts.*, $distSql as distance_km, (LOWER(legal_city) = ? OR LOWER(operational_city) = ?) as is_city_match", [$city, $city])
                  ->where(function (Builder $q) use ($city, $distSql) {
                      $q->whereRaw('LOWER(legal_city) = ?', [$city])
                        ->orWhereRaw('LOWER(operational_city) = ?', [$city])
                        ->orWhereHas('vendorOfferingProfiles', function ($profQ) use ($distSql) {
                            $profQ->where('is_published', true)
                                  ->where(function ($modeQ) use ($distSql) {
                                      $modeQ->where(function ($radiusQ) use ($distSql) {
                                          $radiusQ->whereNotNull('service_radius_km')
                                                  ->whereRaw("$distSql <= service_radius_km");
                                      })->orWhere('service_mode', 'FIXED_LOCATION');
                                  });
                        });
                  })
                  ->orderByDesc('is_city_match')
                  ->orderBy('distance_km')
                  ->orderBy('vendor_accounts.id');
        } else {
            // Fallback nel caso in cui il geocoding non restituisca coordinate valide
            $query->selectRaw("vendor_accounts.*, 1 as is_city_match, 0 as distance_km")
                  ->where(function (Builder $q) use ($city) {
                      $q->whereRaw('LOWER(legal_city) = ?', [$city])
                        ->orWhereRaw('LOWER(operational_city) = ?', [$city])
                        ->orWhereHas('vendorOfferingProfiles', function ($profQ) {
                            $profQ->where('is_published', true)
                                  ->where('service_mode', 'FIXED_LOCATION');
                        });
                  })
                  ->orderBy('vendor_accounts.id');
        }

        $matchedVendors = collect();

        // Utilizza lazy() per caricare i modelli a blocchi (chunk), ottimizzando l'uso della memoria.
        foreach ($query->lazy(50) as $vendor) {
            // Assicura il corretto cast dei dati raw estratti tramite selectRaw
            $vendor->is_city_match = (bool) $vendor->is_city_match;
            $vendor->distance_km = $vendor->distance_km !== null ? round((float) $vendor->distance_km, 1) : null;

            // Filtra i profili del vendor mantenendo solo quelli validi per la ricerca
            $validProfiles = $vendor->vendorOfferingProfiles->filter(function ($profile) use ($vendor, $guests) {
                return $this->profileIsValidForSearch($profile, $vendor, $guests);
            })->values();

            if ($validProfiles->isEmpty()) {
                continue;
            }

            // Assegna i profili validati alla relazione per la formattazione JSON successiva
            $vendor->setRelation('vendorOfferingProfiles', $validProfiles);

            // Verifica la disponibilità effettiva del vendor per i parametri richiesti
            if (! $this->vendorHasAnyAvailableSlot($vendor, $date, $guests)) {
                continue;
            }

            $matchedVendors->push($vendor);

            // Interrompe l'iterazione non appena viene raggiunto il numero di risultati richiesto ($limit)
            if ($matchedVendors->count() >= $limit) {
                break;
            }
        }

        if ($matchedVendors->isEmpty()) {
            return $this->emptyResult($params['city'] ?? null, $date);
        }

        $hasOutsideCityResults = $matchedVendors->contains(function (VendorAccount $vendor) {
            return $vendor->is_city_match === false;
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

    // ... Precedente funzione prepareVendorForSearch eliminata ...

   private function buildBaseQuery(array $params)
{
    $query = VendorAccount::query()
        ->where('status', 'ACTIVE')
        ->whereHas('vendorOfferingProfiles', function ($query) {
            $query->where('is_published', true);
        })
        ->with([
            'category:id,name,slug,prestashop_category_id',
            'vendorOfferingProfiles' => function ($query) {
                $query->where('is_published', true)
                    ->with(['offering:id,name,slug', 'images']);
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

    if (isset($params['event_type_id'])) {
        $query->whereHas('eventTypes', function ($query) use ($params) {
            $query->where('event_types.id', $params['event_type_id']);
        });
    }

    return $query;
}

    // Il vendor entra nei risultati solo se almeno un profilo valido ha uno slot disponibile.
    private function vendorHasAnyAvailableSlot(VendorAccount $vendor, string $date, ?int $guests = null): bool
    {
        /** @var Collection<int, VendorOfferingProfile> $profiles */
        $profiles = $vendor->vendorOfferingProfiles
            ->filter(fn (VendorOfferingProfile $profile) => $profile->offering_id !== null)
            ->values();

        if ($profiles->isEmpty()) {
            return false;
        }

        foreach ($profiles as $profile) {
            $availability = $this->availabilityService->getAvailability(
                vendorAccountId: (int) $vendor->id,
                from: $date,
                to: $date,
                maxDays: 90,
                offeringId: (int) $profile->offering_id,
                guests: $guests
            );

            foreach (($availability[$date] ?? []) as $slot) {
                if (($slot['status'] ?? null) === 'AVAILABLE') {
                    return true;
                }
            }
        }

        return false;
    }

    private function vendorMatchesCity(VendorAccount $vendor, string $searchedCity): bool
    {
        $vendorCity = $this->normalizeText($vendor->effectiveCity());

        if ($vendorCity === null || $searchedCity === null) {
            return false;
        }

        return $vendorCity === $searchedCity;
    }

    private function profileIsSearchable(VendorOfferingProfile $profile): bool
    {
        return $profile->offering !== null && (bool) $profile->is_published;
    }

    private function profileIsValidForSearch(
        VendorOfferingProfile $profile,
        VendorAccount $vendor,
        ?int $guests = null
    ): bool {
        if (! $this->profileIsSearchable($profile)) {
            return false;
        }

        if (! $profile->supportsGuests($guests)) {
            return false;
        }

        if (($vendor->is_city_match ?? false) === true) {
            return true;
        }

        if ($profile->isMobileService()) {
            if (! $profile->hasServiceRadius()) {
                return false;
            }

            if ($vendor->distance_km === null) {
                return false;
            }

            return (float) $vendor->distance_km <= (float) $profile->service_radius_km;
        }

        return true;
    }

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

    private function buildEffectiveAddress(VendorAccount $vendor): array
    {
        $line1 = $vendor->operational_address_line1 ?: $vendor->legal_address_line1;
        $line2 = $vendor->operational_address_line2 ?: $vendor->legal_address_line2;
        $postalCode = $vendor->operational_postal_code ?: $vendor->legal_postal_code;
        $city = $vendor->operational_city ?: $vendor->legal_city;
        $region = $vendor->operational_region ?: $vendor->legal_region;

        $addressParts = array_values(array_filter([
            $line1,
            $line2,
        ], fn ($value) => ! empty($value)));

        $fullAddressParts = array_values(array_filter([
            $line1,
            $line2,
            trim(implode(' ', array_filter([$postalCode, $city], fn ($value) => ! empty($value)))),
            $region,
        ], fn ($value) => ! empty($value)));

        return [
            'address' => ! empty($addressParts) ? implode(', ', $addressParts) : null,
            'full_address' => ! empty($fullAddressParts) ? implode(', ', $fullAddressParts) : null,
        ];
    }

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
                        $offerings = $vendor->vendorOfferingProfiles
                            ->map(function (VendorOfferingProfile $profile) {   // Mappa le immagini del profilo in un formato più adatto alla risposta JSON
                                $images = collect($profile->images ?? [])
                                    ->map(function ($image) {
                                        $path = (string) ($image->path ?? '');

                                        if ($path === '') {
                                            return null;
                                        }

                                        return [
                                            'id' => (int) $image->id,
                                            'url' => route('media.public', [
                                                'path' => ltrim($path, '/'),
                                            ]),
                                            'path' => $path,
                                            'sort_order' => isset($image->sort_order) ? (int) $image->sort_order : null,
                                        ];
                                    })
                                    ->filter()
                                    ->values();

                                return [
                                    'vendor_offering_profile_id' => $profile->id,
                                    'offering_id' => $profile->offering_id,
                                    'offering_name' => $profile->offering->name ?? null,
                                    'offering_slug' => $profile->offering->slug ?? null,
                                    'title' => $profile->title,
                                    'short_description' => $profile->short_description,
                                    'description' => $profile->description,
                                    'service_mode' => $profile->service_mode ?? null,
                                    'service_radius_km' => $profile->service_radius_km ?? null,
                                    'max_guests' => $profile->max_guests ?? null,
                                    'is_published' => (bool) $profile->is_published,
                                    'cover_image_url' => $profile->cover_image_url,
                                    'cover_image_path' => $profile->cover_image_path,
                                    'images' => $images,
                                ];
                            })
                            ->values();

                        $effectiveAddress = $this->buildEffectiveAddress($vendor);

                        $fallbackImage = null;
                        if (!empty($vendor->profile_image_path)) {
                            $fallbackImage = route('media.public', [
                                'path' => ltrim($vendor->profile_image_path, '/'),
                            ]);
                        }

                        $result = [
                            'id' => $vendor->id,
                            'company_name' => $vendor->company_name,
                            'profile_image_url' => $fallbackImage,
                            'city' => $vendor->effectiveCity(),
                            'phone' => $vendor->phone,
                            'address' => $effectiveAddress['address'],
                            'full_address' => $effectiveAddress['full_address'],
                            'offerings_count' => $offerings->count(),
                            'offerings' => $offerings,
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

    private function emptyResult(?string $city, ?string $date): array
    {
        return [
            'fallback_used' => false,
            'search_mode' => 'city',
            'city' => $city,
            'date' => $date,
            'total' => 0,
            'data' => [],
        ];
    }
}