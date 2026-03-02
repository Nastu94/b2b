<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocoding Italia con fallback:
     * 1) address + cap + city + region
     * 2) address + city + region (senza CAP)
     * 3) city + region (+ CAP se presente)
     * 4) solo city + region
     *
     * Ritorna ['lat' => float, 'lng' => float] oppure null.
     */
    public function geocodeItaly(array $addr): ?array
    {
        $address1 = $this->clean($addr['address_line1'] ?? null);
        $address2 = $this->clean($addr['address_line2'] ?? null);
        $postal   = $this->clean($addr['postal_code'] ?? null);
        $city     = $this->clean($addr['city'] ?? null);
        $region   = $this->clean($addr['region'] ?? null);

        // Se non ho nemmeno città, non ha senso provare
        if (!$city) {
            return null;
        }

        // Costruisco una lista di query (dalla più specifica alla più generica)
        $queries = [];

        // 1) Completa
        $queries[] = $this->buildQuery([$address1, $address2, $postal, $city, $region]);

        // 2) Senza CAP
        $queries[] = $this->buildQuery([$address1, $address2, $city, $region]);

        // 3) Senza via (solo città + regione + CAP se presente)
        $queries[] = $this->buildQuery([$postal, $city, $region]);

        // 4) Solo città + regione
        $queries[] = $this->buildQuery([$city, $region]);

        // Rimuove vuoti e duplicati mantenendo l'ordine
        $queries = array_values(array_unique(array_filter($queries)));

        foreach ($queries as $query) {
            $coords = $this->nominatimSearchCached($query);
            if ($coords) {
                return $coords;
            }
        }

        return null;
    }

    private function nominatimSearchCached(string $query): ?array
    {
        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim($query)));
        $cacheKey = 'geocode:nominatim:it:' . sha1($normalized);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            $resp = Http::withHeaders([
                    'User-Agent' => config('app.name', 'BookingBridge') . '/1.0 (geocoding)',
                    'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
                ])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'it',
                    'addressdetails' => 0,
                ]);

            if (!$resp->ok()) {
                return null;
            }

            $data = $resp->json();
            if (!is_array($data) || count($data) === 0) {
                return null;
            }

            $lat = $data[0]['lat'] ?? null;
            $lon = $data[0]['lon'] ?? null;

            if (!$lat || !$lon) {
                return null;
            }

            return [
                'lat' => (float) $lat,
                'lng' => (float) $lon,
            ];
        });
    }

    private function buildQuery(array $parts): string
    {
        $parts = array_filter(array_map([$this, 'clean'], $parts));
        return trim(implode(', ', $parts));
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) return null;

        $value = trim($value);
        if ($value === '') return null;

        // Normalizza spazi doppi
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?: null;
    }
}