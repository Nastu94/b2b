<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocoding Italia con fallback progressivo.
     * 
     * Tenta il geocoding con query progressivamente meno specifiche:
     * 1) Indirizzo completo con CAP
     * 2) Indirizzo senza CAP
     * 3) Solo città, regione e CAP
     * 4) Solo città e regione
     *
     * @param array $addr Array con chiavi: address_line1, address_line2, postal_code, city, region
     * @return array|null ['lat' => float, 'lng' => float] oppure null se non trovato
     */
    public function geocodeItaly(array $addr): ?array
    {
        // Estrae e pulisce i campi dell'indirizzo
        $address1 = $this->clean($addr['address_line1'] ?? null);
        $address2 = $this->clean($addr['address_line2'] ?? null);
        $postal   = $this->clean($addr['postal_code'] ?? null);
        $city     = $this->clean($addr['city'] ?? null);
        $region   = $this->clean($addr['region'] ?? null);

        // Senza almeno la città, non possiamo procedere
        if (!$city) {
            return null;
        }

        // Costruisce lista di query dal più specifico al più generico
        $queries = [];

        // Query 1: Indirizzo completo con CAP
        $queries[] = $this->buildQuery([$address1, $address2, $postal, $city, $region]);

        // Query 2: Indirizzo senza CAP
        $queries[] = $this->buildQuery([$address1, $address2, $city, $region]);

        // Query 3: Solo città, regione e CAP (senza via)
        $queries[] = $this->buildQuery([$postal, $city, $region]);

        // Query 4: Solo città e regione
        $queries[] = $this->buildQuery([$city, $region]);

        // Rimuove stringhe vuote e duplicati mantenendo l'ordine
        $queries = array_values(array_unique(array_filter($queries)));

        // Prova ogni query finché non trova coordinate valide
        foreach ($queries as $query) {
            $coords = $this->nominatimSearchCached($query);
            if ($coords) {
                return $coords;
            }
        }

        // Nessuna query ha prodotto risultati
        return null;
    }

    /**
     * Esegue ricerca geocoding su Nominatim con cache.
     * 
     * Le risposte vengono cachate per 30 giorni per ridurre
     * le chiamate API e migliorare le performance.
     *
     * @param string $query Query di ricerca (es: "Via Roma, 70121, Bari, Puglia")
     * @return array|null ['lat' => float, 'lng' => float] oppure null
     */
    private function nominatimSearchCached(string $query): ?array
    {
        // Normalizza la query per cache consistency
        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim($query)));
        $cacheKey = 'geocode:nominatim:it:' . sha1($normalized);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            // Chiamata API Nominatim OpenStreetMap
            $resp = Http::withHeaders([
                    'User-Agent' => config('app.name', 'BookingBridge') . '/1.0 (geocoding)',
                    'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
                ])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'it',  // Solo risultati italiani
                    'addressdetails' => 0,    // Non servono dettagli indirizzo
                ]);

            // Verifica successo chiamata HTTP
            if (!$resp->ok()) {
                return null;
            }

            $data = $resp->json();

            // Verifica che ci siano risultati
            if (!is_array($data) || count($data) === 0) {
                return null;
            }

            // Estrae coordinate dal primo risultato
            $lat = $data[0]['lat'] ?? null;
            $lon = $data[0]['lon'] ?? null;

            // Verifica che entrambe le coordinate siano presenti
            if (!$lat || !$lon) {
                return null;
            }

            // Ritorna coordinate come float
            return [
                'lat' => (float) $lat,
                'lng' => (float) $lon,
            ];
        });
    }

    /**
     * Calcola la distanza in km tra due punti GPS usando la Formula di Haversine.
     * 
     * La formula di Haversine calcola la distanza ortodromica (great-circle distance)
     * tra due punti sulla superficie di una sfera, considerando la curvatura terrestre.
     *
     * @param float $lat1 Latitudine punto 1 (gradi decimali)
     * @param float $lng1 Longitudine punto 1 (gradi decimali)
     * @param float $lat2 Latitudine punto 2 (gradi decimali)
     * @param float $lng2 Longitudine punto 2 (gradi decimali)
     * @return float Distanza in chilometri
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // Raggio medio della Terra in chilometri
        $earthRadius = 6371;

        // Converte le differenze di latitudine e longitudine in radianti
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        // Formula di Haversine - parte A
        // Calcola il quadrato della metà della corda tra i due punti
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        // Formula di Haversine - parte C
        // Calcola la distanza angolare in radianti
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Moltiplica la distanza angolare per il raggio terrestre
        // per ottenere la distanza in chilometri
        return $earthRadius * $c;
    }

    /**
     * Costruisce una stringa query concatenando le parti fornite.
     * 
     * Filtra valori null/vuoti e unisce con virgola.
     *
     * @param array $parts Array di stringhe da concatenare
     * @return string Query risultante (es: "Via Roma, Bari, Puglia")
     */
    private function buildQuery(array $parts): string
    {
        // Pulisce ogni parte e rimuove quelle vuote
        $parts = array_filter(array_map([$this, 'clean'], $parts));
        
        // Unisce con virgola e spazio
        return trim(implode(', ', $parts));
    }

    /**
     * Pulisce e normalizza una stringa.
     * 
     * Rimuove spazi multipli, trim degli estremi.
     * Ritorna null se la stringa è vuota.
     *
     * @param string|null $value Stringa da pulire
     * @return string|null Stringa pulita oppure null
     */
    private function clean(?string $value): ?string
    {
        // Se è già null, ritorna null
        if ($value === null) {
            return null;
        }

        // Rimuove spazi iniziali e finali
        $value = trim($value);

        // Se vuota dopo trim, ritorna null
        if ($value === '') {
            return null;
        }

        // Normalizza spazi multipli in un singolo spazio
        $value = preg_replace('/\s+/', ' ', $value);

        // Ritorna valore pulito o null se ancora vuoto
        return $value ?: null;
    }
}