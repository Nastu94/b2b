<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


// Servizio di geocoding per convertire indirizzi italiani in coordinate lat/lng
// Utilizza Nominatim (OpenStreetMap) come provider gratuito
class GeocodingService
{
    public function geocodeItaly(array $addr): ?array
    {
        $query = trim(implode(', ', array_filter([
            $addr['address_line1'] ?? null,
            $addr['address_line2'] ?? null,
            $addr['postal_code'] ?? null,
            $addr['city'] ?? null,
            $addr['region'] ?? null,
            $addr['country'] ?? 'IT',
        ])));

        if ($query === '') {
            return null;
        }

        $resp = Http::withHeaders([
                // Nominatim richiede UA identificabile
                'User-Agent' => config('app.name', 'BookingBridge') . ' (vendor-geocoding)',
                'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
            ])
            ->timeout(10)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
            ]);

        if (!$resp->ok()) {
            return null;
        }

        $data = $resp->json();

        if (!is_array($data) || count($data) === 0) {
            return null;
        }

        return [
            'lat' => (float) ($data[0]['lat'] ?? 0),
            'lng' => (float) ($data[0]['lon'] ?? 0),
        ];
    }
}