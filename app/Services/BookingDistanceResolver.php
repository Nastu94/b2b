<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use Illuminate\Support\Str;
use RuntimeException;

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

    /**
     * Applica la strategia di rollout alla distanza usata dal pricing.
     *
     * legacy: conserva il valore client senza chiamare il geocoder;
     * shadow: confronta con il server ma conserva il client per il prezzo;
     * enforce: usa esclusivamente il valore calcolato dal server.
     */
    public function resolveForHold(
        VendorAccount $vendor,
        VendorOfferingProfile $profile,
        ?string $eventCity,
        ?string $eventRegion,
        ?float $clientDistanceKm,
        ?string $mode = null
    ): array {
        $mode = strtolower(trim((string) ($mode ?? config('booking_bridge.distance_mode', 'legacy'))));

        if (! in_array($mode, ['legacy', 'shadow', 'enforce'], true)) {
            throw new RuntimeException('Modalità di verifica distanza non valida.');
        }

        $eventCity = $this->cleanForStorage($eventCity);
        $eventRegion = $this->cleanForStorage($eventRegion);
        $clientDistanceKm = $clientDistanceKm !== null
            ? round($clientDistanceKm, 2)
            : null;

        if ($mode === 'legacy') {
            return $this->decision(
                mode: $mode,
                eventCity: $eventCity,
                eventRegion: $eventRegion,
                clientDistanceKm: $clientDistanceKm,
                serverDistanceKm: null,
                serverSource: 'not_checked',
                effectiveDistanceKm: $clientDistanceKm,
                effectiveSource: $clientDistanceKm !== null ? 'client_legacy' : 'unresolved'
            );
        }

        $server = $this->resolve($vendor, $profile, $eventCity, $eventRegion);
        $serverDistanceKm = $server['distance_km'];

        if ($mode === 'enforce') {
            if ($serverDistanceKm === null) {
                throw new RuntimeException('Impossibile verificare la distanza dell\'evento. Controllare città e regione.');
            }

            return $this->decision(
                mode: $mode,
                eventCity: $eventCity,
                eventRegion: $eventRegion,
                clientDistanceKm: $clientDistanceKm,
                serverDistanceKm: (float) $serverDistanceKm,
                serverSource: (string) $server['distance_source'],
                effectiveDistanceKm: (float) $serverDistanceKm,
                effectiveSource: (string) $server['distance_source']
            );
        }

        $effectiveDistanceKm = $clientDistanceKm ?? $serverDistanceKm;
        $effectiveSource = $clientDistanceKm !== null
            ? 'client_shadow'
            : ($serverDistanceKm !== null ? 'server_shadow' : 'unresolved');

        return $this->decision(
            mode: $mode,
            eventCity: $eventCity,
            eventRegion: $eventRegion,
            clientDistanceKm: $clientDistanceKm,
            serverDistanceKm: $serverDistanceKm !== null ? (float) $serverDistanceKm : null,
            serverSource: (string) $server['distance_source'],
            effectiveDistanceKm: $effectiveDistanceKm !== null ? (float) $effectiveDistanceKm : null,
            effectiveSource: $effectiveSource
        );
    }

    private function decision(
        string $mode,
        ?string $eventCity,
        ?string $eventRegion,
        ?float $clientDistanceKm,
        ?float $serverDistanceKm,
        string $serverSource,
        ?float $effectiveDistanceKm,
        string $effectiveSource
    ): array {
        return [
            'mode' => $mode,
            'event_city' => $eventCity,
            'event_region' => $eventRegion,
            'client_distance_km' => $clientDistanceKm,
            'server_distance_km' => $serverDistanceKm,
            'server_distance_source' => $serverSource,
            'distance_km' => $effectiveDistanceKm,
            'distance_source' => $effectiveSource,
            'delta_km' => $clientDistanceKm !== null && $serverDistanceKm !== null
                ? round(abs($clientDistanceKm - $serverDistanceKm), 2)
                : null,
        ];
    }

    private function cleanForStorage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) preg_replace('/\s+/', ' ', $value));

        return $value !== '' ? $value : null;
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
