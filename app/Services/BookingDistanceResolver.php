<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use Illuminate\Support\Str;

class BookingDistanceResolver
{
    public function __construct(protected GeocodingService $geocodingService)
    {
    }

    public function resolve(VendorAccount $vendor, VendorOfferingProfile $profile, ?string $eventCity, ?string $eventRegion = null): array
    {
        $normalizedCity = $this->normalizeText($eventCity);
        $normalizedRegion = $this->normalizeText($eventRegion);
        $vendorCity = $this->normalizeText($vendor->effectiveCity());

        if (empty($normalizedCity)) {
            return [
                'distance_km' => null,
                'distance_source' => 'unresolved',
                'normalized_city' => null,
                'normalized_region' => $normalizedRegion,
            ];
        }

        if ($normalizedCity === $vendorCity) {
            return [
                'distance_km' => 0.00,
                'distance_source' => 'same_city',
                'normalized_city' => $normalizedCity,
                'normalized_region' => $normalizedRegion,
            ];
        }

        $cityCoords = $this->geocodingService->geocodeCity($normalizedCity, $normalizedRegion);
        $vendorLat = $vendor->effectiveLat();
        $vendorLng = $vendor->effectiveLng();

        if ($cityCoords && $vendorLat !== null && $vendorLng !== null) {
            $distance = $this->geocodingService->calculateDistance(
                (float) $vendorLat,
                (float) $vendorLng,
                (float) $cityCoords['lat'],
                (float) $cityCoords['lng']
            );

            return [
                'distance_km' => round($distance, 2),
                'distance_source' => 'server',
                'normalized_city' => $normalizedCity,
                'normalized_region' => $normalizedRegion,
            ];
        }

        return [
            'distance_km' => null,
            'distance_source' => 'unresolved',
            'normalized_city' => $normalizedCity,
            'normalized_region' => $normalizedRegion,
        ];
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
}
