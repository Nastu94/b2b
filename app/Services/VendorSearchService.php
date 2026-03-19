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

        $vendors = $this->buildBaseQuery($params)->get();

        if ($vendors->isEmpty()) {
            return $this->emptyResult($params['city'] ?? null, $date);
        }

        $cityCoordinates = $this->geocodingService->geocodeCity($params['city']);

        $matchedVendors = $vendors
            ->map(function (VendorAccount $vendor) use ($city, $cityCoordinates, $guests, $date) {
                return $this->prepareVendorForSearch(
                    vendor: $vendor,
                    searchedCity: $city,
                    cityCoordinates: $cityCoordinates,
                    guests: $guests,
                    date: $date
                );
            })
            ->filter()
            ->sort(function (VendorAccount $a, VendorAccount $b) {
                $aPriority = ($a->is_city_match ?? false) ? 0 : 1;
                $bPriority = ($b->is_city_match ?? false) ? 0 : 1;

                if ($aPriority !== $bPriority) {
                    return $aPriority <=> $bPriority;
                }

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

    private function prepareVendorForSearch(
        VendorAccount $vendor,
        string $searchedCity,
        ?array $cityCoordinates,
        ?int $guests,
        string $date
    ): ?VendorAccount {
        $vendor->is_city_match = $this->vendorMatchesCity($vendor, $searchedCity);
        $vendor->distance_km = null;

        $lat = $vendor->effectiveLat();
        $lng = $vendor->effectiveLng();

        if ($vendor->is_city_match) {
            $vendor->distance_km = 0.0;
        } elseif ($cityCoordinates && $lat !== null && $lng !== null) {
            $distance = $this->geocodingService->calculateDistance(
                (float) $cityCoordinates['lat'],
                (float) $cityCoordinates['lng'],
                (float) $lat,
                (float) $lng
            );

            $vendor->distance_km = round($distance, 1);
        } else {
            return null;
        }

        $validProfiles = $vendor->vendorOfferingProfiles
            ->filter(function (VendorOfferingProfile $profile) use ($vendor, $guests) {
                return $this->profileIsValidForSearch($profile, $vendor, $guests);
            })
            ->values();

        if ($validProfiles->isEmpty()) {
            return null;
        }

        $vendor->setRelation('vendorOfferingProfiles', $validProfiles);

        if (! $this->vendorHasAnyAvailableSlot($vendor, $date, $guests)) {
            return null;
        }

        return $vendor;
    }

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
                            ->map(function (VendorOfferingProfile $profile) {
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
                                ];
                            })
                            ->values();

                        $effectiveAddress = $this->buildEffectiveAddress($vendor);

                        $result = [
                            'id' => $vendor->id,
                            'company_name' => $vendor->company_name,
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