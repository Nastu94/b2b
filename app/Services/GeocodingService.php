<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    // Geocoding principale per il contesto Italia.
    // Accetta sia indirizzi completi sia dati parziali.
    //
    // Chiavi supportate:
    // - address_line1
    // - address_line2
    // - postal_code
    // - city
    // - region
    //
    // Restituisce:
    // - ['lat' => float, 'lng' => float]
    // - null se non trova un risultato affidabile
    public function geocodeItaly(array $addr): ?array
    {
        $address1 = $this->clean($addr['address_line1'] ?? null);
        $address2 = $this->clean($addr['address_line2'] ?? null);
        $postalCode = $this->clean($addr['postal_code'] ?? null);
        $city = $this->clean($addr['city'] ?? null);
        $region = $this->clean($addr['region'] ?? null);

        // Senza città non possiamo costruire una ricerca affidabile.
        if ($city === null) {
            return null;
        }

        $street = $this->buildStreet($address1, $address2);

        // Prima proviamo ricerche strutturate.
        // Sono più affidabili delle query testuali libere quando i campi sono separati.
        $structuredAttempts = [
            [
                'street' => $street,
                'postalcode' => $postalCode,
                'city' => $city,
                'state' => $region,
                'country' => 'Italia',
            ],
            [
                'street' => $street,
                'city' => $city,
                'state' => $region,
                'country' => 'Italia',
            ],
            [
                'postalcode' => $postalCode,
                'city' => $city,
                'state' => $region,
                'country' => 'Italia',
            ],
            [
                'city' => $city,
                'state' => $region,
                'country' => 'Italia',
            ],
            [
                'city' => $city,
                'country' => 'Italia',
            ],
        ];

        foreach ($structuredAttempts as $params) {
            $params = $this->filterEmpty($params);

            if (empty($params['city'])) {
                continue;
            }

            $result = $this->searchStructuredCached($params, [
                'city' => $city,
                'region' => $region,
                'postal_code' => $postalCode,
                'street' => $street,
            ]);

            if ($result !== null) {
                return $result;
            }
        }

        // Se le ricerche strutturate non bastano, usiamo fallback testuali.
        // Questo aiuta in casi in cui Nominatim indicizza meglio la query libera.
        $textAttempts = [
            $this->buildQuery([$street, $postalCode, $city, $region, 'Italia']),
            $this->buildQuery([$street, $city, $region, 'Italia']),
            $this->buildQuery([$postalCode, $city, $region, 'Italia']),
            $this->buildQuery([$city, $region, 'Italia']),
            $this->buildQuery([$city, 'Italia']),
        ];

        $textAttempts = array_values(array_unique(array_filter($textAttempts)));

        foreach ($textAttempts as $query) {
            $result = $this->searchTextCached($query, [
                'city' => $city,
                'region' => $region,
                'postal_code' => $postalCode,
                'street' => $street,
            ]);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    // Metodo dedicato al caso "solo città".
    // È utile quando dobbiamo usare la città come punto geografico di fallback.
    public function geocodeCity(string $city, ?string $region = null): ?array
    {
        return $this->geocodeItaly([
            'city' => $city,
            'region' => $region,
        ]);
    }

    // Calcola la distanza in chilometri tra due punti GPS.
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // Esegue una ricerca strutturata su Nominatim e ne seleziona il miglior risultato.
    private function searchStructuredCached(array $params, array $context): ?array
    {
        $cacheKey = 'geocode:nominatim:it:structured:' . sha1(json_encode($params));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($params, $context) {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout(10)
                ->retry(2, 300)
                ->get('https://nominatim.openstreetmap.org/search', array_merge([
                    'format' => 'jsonv2',
                    'limit' => 5,
                    'countrycodes' => 'it',
                    'addressdetails' => 1,
                    'dedupe' => 1,
                ], $params));

            if (!$response->ok()) {
                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                return null;
            }

            return $this->extractBestCoordinates($data, $context);
        });
    }

    // Esegue una ricerca testuale su Nominatim e ne seleziona il miglior risultato.
    private function searchTextCached(string $query, array $context): ?array
    {
        $normalizedQuery = mb_strtolower(preg_replace('/\s+/', ' ', trim($query)));
        $cacheKey = 'geocode:nominatim:it:text:' . sha1($normalizedQuery);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query, $context) {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout(10)
                ->retry(2, 300)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 5,
                    'countrycodes' => 'it',
                    'addressdetails' => 1,
                    'dedupe' => 1,
                ]);

            if (!$response->ok()) {
                return null;
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                return null;
            }

            return $this->extractBestCoordinates($data, $context);
        });
    }

    // Seleziona il risultato migliore tra più candidati.
    // La logica è prudente:
    // - preferisce città coerente
    // - preferisce CAP coerente
    // - preferisce regione coerente
    // - premia la presenza della via quando richiesta
    // - usa importanza e place_rank come criterio secondario
    private function extractBestCoordinates(array $results, array $context): ?array
    {
        $city = $this->normalizeForMatch($context['city'] ?? null);
        $region = $this->normalizeForMatch($context['region'] ?? null);
        $postalCode = $this->normalizePostalCode($context['postal_code'] ?? null);
        $street = $this->normalizeForMatch($context['street'] ?? null);

        $scored = [];

        foreach ($results as $result) {
            $lat = $result['lat'] ?? null;
            $lon = $result['lon'] ?? null;

            if (!is_numeric($lat) || !is_numeric($lon)) {
                continue;
            }

            $score = $this->scoreResult($result, $city, $region, $postalCode, $street);

            // Scartiamo risultati troppo deboli per evitare coordinate fuorvianti.
            if ($score < 40) {
                continue;
            }

            $scored[] = [
                'score' => $score,
                'lat' => (float) $lat,
                'lng' => (float) $lon,
            ];
        }

        if (empty($scored)) {
            return null;
        }

        usort($scored, function (array $a, array $b) {
            return $b['score'] <=> $a['score'];
        });

        return [
            'lat' => $scored[0]['lat'],
            'lng' => $scored[0]['lng'],
        ];
    }

    // Attribuisce un punteggio al risultato Nominatim.
    private function scoreResult(
        array $result,
        ?string $expectedCity,
        ?string $expectedRegion,
        ?string $expectedPostalCode,
        ?string $expectedStreet
    ): float {
        $score = 0.0;

        $address = is_array($result['address'] ?? null) ? $result['address'] : [];

        $candidateCity = $this->normalizeForMatch(
            $address['city']
            ?? $address['town']
            ?? $address['village']
            ?? $address['municipality']
            ?? $address['hamlet']
            ?? null
        );

        $candidateRegion = $this->normalizeForMatch(
            $address['state']
            ?? $address['region']
            ?? null
        );

        $candidatePostalCode = $this->normalizePostalCode($address['postcode'] ?? null);

        $candidateStreet = $this->normalizeForMatch(
            $address['road']
            ?? $address['pedestrian']
            ?? $address['residential']
            ?? $address['footway']
            ?? null
        );

        $displayName = $this->normalizeForMatch($result['display_name'] ?? null);
        $class = $result['class'] ?? null;
        $type = $result['type'] ?? null;
        $importance = isset($result['importance']) && is_numeric($result['importance'])
            ? (float) $result['importance']
            : 0.0;
        $placeRank = isset($result['place_rank']) && is_numeric($result['place_rank'])
            ? (float) $result['place_rank']
            : 0.0;

        // La città è il vincolo più importante.
        if ($expectedCity !== null) {
            if ($candidateCity === $expectedCity) {
                $score += 50;
            } elseif ($displayName !== null && str_contains($displayName, $expectedCity)) {
                $score += 20;
            } else {
                $score -= 40;
            }
        }

        // La regione aiuta a distinguere città omonime.
        if ($expectedRegion !== null) {
            if ($candidateRegion === $expectedRegion) {
                $score += 20;
            } elseif ($displayName !== null && str_contains($displayName, $expectedRegion)) {
                $score += 8;
            } else {
                $score -= 10;
            }
        }

        // Il CAP è un ottimo segnale quando disponibile.
        if ($expectedPostalCode !== null) {
            if ($candidatePostalCode === $expectedPostalCode) {
                $score += 20;
            } else {
                $score -= 8;
            }
        }

        // Se l'input contiene una via, premiamo risultati che contengono una strada coerente.
        if ($expectedStreet !== null) {
            if ($candidateStreet !== null && $this->streetLooksCompatible($expectedStreet, $candidateStreet)) {
                $score += 20;
            } elseif ($displayName !== null && str_contains($displayName, $expectedStreet)) {
                $score += 10;
            } else {
                $score -= 8;
            }
        } else {
            // Nel caso città-only, premiamo entità amministrative o urbane sensate.
            if (in_array($type, ['city', 'town', 'village', 'administrative'], true)) {
                $score += 10;
            }

            if (in_array($class, ['boundary', 'place'], true)) {
                $score += 6;
            }
        }

        // Importanza e place rank aiutano a discriminare tra più risultati simili.
        $score += min($importance * 10, 5);
        $score += min($placeRank / 10, 5);

        return $score;
    }

    // Verifica compatibilità "morbida" tra due nomi strada.
    // Serve a tollerare abbreviazioni e differenze minori di scrittura.
    private function streetLooksCompatible(string $expectedStreet, string $candidateStreet): bool
    {
        $expectedTokens = $this->tokenizeForMatch($expectedStreet);
        $candidateTokens = $this->tokenizeForMatch($candidateStreet);

        if (empty($expectedTokens) || empty($candidateTokens)) {
            return false;
        }

        $intersection = array_intersect($expectedTokens, $candidateTokens);

        // Consideriamo compatibile una strada se condivide almeno due token
        // o tutti i token quando la stringa è molto corta.
        if (count($expectedTokens) <= 2) {
            return count($intersection) >= count($expectedTokens);
        }

        return count($intersection) >= 2;
    }

    // Costruisce gli header HTTP richiesti da Nominatim.
    private function buildHeaders(): array
    {
        return [
            'User-Agent' => config('app.name', 'BookingBridge') . '/1.0 (geocoding)',
            'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
        ];
    }

    // Costruisce la stringa strada unendo address_line1 e address_line2.
    private function buildStreet(?string $address1, ?string $address2): ?string
    {
        $street = $this->buildQuery([$address1, $address2]);

        return $street !== '' ? $street : null;
    }

    // Costruisce una query testuale pulita.
    private function buildQuery(array $parts): string
    {
        $parts = array_filter(array_map([$this, 'clean'], $parts));

        return trim(implode(', ', $parts));
    }

    // Rimuove i valori null o vuoti da un array associativo.
    private function filterEmpty(array $values): array
    {
        return array_filter($values, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    // Pulisce una stringa mantenendo solo un formato coerente.
    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value);

        return $value !== '' ? $value : null;
    }

    // Normalizza una stringa per confronti tolleranti.
    private function normalizeForMatch(?string $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        $value = mb_strtolower($value);

        // Rimuoviamo la maggior parte della punteggiatura per confronti più robusti.
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value) !== '' ? trim($value) : null;
    }

    // Normalizza il CAP per confronti esatti.
    private function normalizePostalCode(?string $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\D+/', '', $value);

        return $value !== '' ? $value : null;
    }

    // Tokenizza una stringa normalizzata per confronti su parole chiave.
    private function tokenizeForMatch(?string $value): array
    {
        $normalized = $this->normalizeForMatch($value);

        if ($normalized === null) {
            return [];
        }

        $tokens = explode(' ', $normalized);

        // Rimuoviamo token troppo corti e parole molto comuni delle strade.
        $stopWords = [
            'via',
            'viale',
            'piazza',
            'corso',
            'largo',
            'vicolo',
            'strada',
            'piazzale',
            'dei',
            'degli',
            'delle',
            'del',
            'di',
            'da',
            'la',
            'le',
            'il',
            'lo',
        ];

        $tokens = array_filter($tokens, function ($token) use ($stopWords) {
            return mb_strlen($token) >= 2 && !in_array($token, $stopWords, true);
        });

        return array_values(array_unique($tokens));
    }
}