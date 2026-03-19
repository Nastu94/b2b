<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeocodingService
{
    private const COUNTRY = 'Italia';
    private const COUNTRY_CODE = 'it';

    private const CACHE_TTL_DAYS = 30;
    private const NOT_FOUND_CACHE_HOURS = 12;

    private const REQUEST_TIMEOUT_SECONDS = 10;
    private const REQUEST_RETRIES = 2;
    private const REQUEST_RETRY_SLEEP_MS = 300;

    private const RESULTS_LIMIT = 5;
    private const MIN_CONFIDENCE_SCORE = 40.0;

    // Geocoding principale per indirizzi italiani.
    public function geocodeItaly(array $addr): ?array
    {
        $address1 = $this->clean($addr['address_line1'] ?? null);
        $address2 = $this->clean($addr['address_line2'] ?? null);
        $postalCode = $this->clean($addr['postal_code'] ?? null);
        $city = $this->clean($addr['city'] ?? null);
        $region = $this->clean($addr['region'] ?? null);

        if ($city === null) {
            return null;
        }

        $street = $this->buildStreet($address1, $address2);

        $context = [
            'city' => $city,
            'region' => $region,
            'postal_code' => $postalCode,
            'street' => $street,
        ];

        $structuredAttempts = [
            [
                'street' => $street,
                'postalcode' => $postalCode,
                'city' => $city,
                'state' => $region,
                'country' => self::COUNTRY,
            ],
            [
                'street' => $street,
                'city' => $city,
                'state' => $region,
                'country' => self::COUNTRY,
            ],
            [
                'postalcode' => $postalCode,
                'city' => $city,
                'state' => $region,
                'country' => self::COUNTRY,
            ],
            [
                'city' => $city,
                'state' => $region,
                'country' => self::COUNTRY,
            ],
            [
                'city' => $city,
                'country' => self::COUNTRY,
            ],
        ];

        foreach ($structuredAttempts as $params) {
            $params = $this->filterEmpty($params);

            if (empty($params['city'])) {
                continue;
            }

            $result = $this->searchStructuredCached($params, $context);

            if ($result !== null) {
                return $result;
            }
        }

        $textAttempts = [
            $this->buildQuery([$street, $postalCode, $city, $region, self::COUNTRY]),
            $this->buildQuery([$street, $city, $region, self::COUNTRY]),
            $this->buildQuery([$postalCode, $city, $region, self::COUNTRY]),
            $this->buildQuery([$city, $region, self::COUNTRY]),
            $this->buildQuery([$city, self::COUNTRY]),
        ];

        $textAttempts = array_values(array_unique(array_filter($textAttempts)));

        foreach ($textAttempts as $query) {
            $result = $this->searchTextCached($query, $context);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    // Geocoding città.
    public function geocodeCity(string $city, ?string $region = null): ?array
    {
        return $this->geocodeItaly([
            'city' => $city,
            'region' => $region,
        ]);
    }

    // Distanza in km con formula Haversine.
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function searchStructuredCached(array $params, array $context): ?array
    {
        $cacheKey = 'geocode:nominatim:it:structured:' . $this->stableHash($params);

        return $this->rememberGeocodeResult($cacheKey, function () use ($params, $context) {
            $results = $this->performSearch(array_merge([
                'format' => 'jsonv2',
                'limit' => self::RESULTS_LIMIT,
                'countrycodes' => self::COUNTRY_CODE,
                'addressdetails' => 1,
                'dedupe' => 1,
            ], $params));

            if ($results === null) {
                return ['cacheable' => false, 'value' => null];
            }

            return [
                'cacheable' => true,
                'value' => $this->extractBestCoordinates($results, $context),
            ];
        });
    }

    private function searchTextCached(string $query, array $context): ?array
    {
        $normalizedQuery = $this->normalizeForMatch($query) ?? $query;
        $cacheKey = 'geocode:nominatim:it:text:' . sha1($normalizedQuery);

        return $this->rememberGeocodeResult($cacheKey, function () use ($query, $context) {
            $results = $this->performSearch([
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => self::RESULTS_LIMIT,
                'countrycodes' => self::COUNTRY_CODE,
                'addressdetails' => 1,
                'dedupe' => 1,
            ]);

            if ($results === null) {
                return ['cacheable' => false, 'value' => null];
            }

            return [
                'cacheable' => true,
                'value' => $this->extractBestCoordinates($results, $context),
            ];
        });
    }

    private function rememberGeocodeResult(string $cacheKey, callable $resolver): ?array
    {
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $resolved = $resolver();

        if (! is_array($resolved) || ! array_key_exists('cacheable', $resolved)) {
            return null;
        }

        if (($resolved['cacheable'] ?? false) !== true) {
            return $resolved['value'] ?? null;
        }

        $value = $resolved['value'] ?? null;
        $ttl = $value !== null
            ? now()->addDays(self::CACHE_TTL_DAYS)
            : now()->addHours(self::NOT_FOUND_CACHE_HOURS);

        Cache::put($cacheKey, $value, $ttl);

        return $value;
    }

    private function performSearch(array $queryParams): ?array
    {
        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->retry(self::REQUEST_RETRIES, self::REQUEST_RETRY_SLEEP_MS)
                ->get('https://nominatim.openstreetmap.org/search', $queryParams);

            if (! $response->ok()) {
                Log::warning('Geocoding provider response non OK', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! is_array($data) || empty($data)) {
                return null;
            }

            return $data;
        } catch (Throwable $e) {
            Log::warning('Geocoding provider call fallita', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // Sceglie il candidato migliore.
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

            if (! is_numeric($lat) || ! is_numeric($lon)) {
                continue;
            }

            $lat = (float) $lat;
            $lng = (float) $lon;

            if (! $this->areCoordinatesValid($lat, $lng)) {
                continue;
            }

            $score = $this->scoreResult($result, $city, $region, $postalCode, $street);

            if ($score < self::MIN_CONFIDENCE_SCORE) {
                continue;
            }

            $scored[] = [
                'score' => $score,
                'lat' => $lat,
                'lng' => $lng,
            ];
        }

        if (empty($scored)) {
            return null;
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return [
            'lat' => $scored[0]['lat'],
            'lng' => $scored[0]['lng'],
        ];
    }

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

        if ($expectedCity !== null) {
            if ($candidateCity === $expectedCity) {
                $score += 50;
            } elseif ($displayName !== null && str_contains($displayName, $expectedCity)) {
                $score += 20;
            } else {
                $score -= 40;
            }
        }

        if ($expectedRegion !== null) {
            if ($candidateRegion === $expectedRegion) {
                $score += 20;
            } elseif ($displayName !== null && str_contains($displayName, $expectedRegion)) {
                $score += 8;
            } else {
                $score -= 10;
            }
        }

        if ($expectedPostalCode !== null) {
            if ($candidatePostalCode === $expectedPostalCode) {
                $score += 20;
            } else {
                $score -= 8;
            }
        }

        if ($expectedStreet !== null) {
            if ($candidateStreet !== null && $this->streetLooksCompatible($expectedStreet, $candidateStreet)) {
                $score += 20;
            } elseif ($displayName !== null && str_contains($displayName, $expectedStreet)) {
                $score += 10;
            } else {
                $score -= 8;
            }
        } else {
            if (in_array($type, ['city', 'town', 'village', 'administrative'], true)) {
                $score += 10;
            }

            if (in_array($class, ['boundary', 'place'], true)) {
                $score += 6;
            }
        }

        $score += min($importance * 10, 5);
        $score += min($placeRank / 10, 5);

        return $score;
    }

    private function streetLooksCompatible(string $expectedStreet, string $candidateStreet): bool
    {
        $expectedTokens = $this->tokenizeForMatch($expectedStreet);
        $candidateTokens = $this->tokenizeForMatch($candidateStreet);

        if (empty($expectedTokens) || empty($candidateTokens)) {
            return false;
        }

        $intersection = array_intersect($expectedTokens, $candidateTokens);

        if (count($expectedTokens) <= 2) {
            return count($intersection) >= count($expectedTokens);
        }

        return count($intersection) >= 2;
    }

    private function buildHeaders(): array
    {
        return [
            'User-Agent' => config('app.name', 'BookingBridge') . '/1.0 (geocoding)',
            'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
        ];
    }

    private function buildStreet(?string $address1, ?string $address2): ?string
    {
        $street = $this->buildQuery([$address1, $address2]);

        return $street !== '' ? $street : null;
    }

    private function buildQuery(array $parts): string
    {
        $parts = array_filter(array_map([$this, 'clean'], $parts));

        return trim(implode(', ', $parts));
    }

    private function filterEmpty(array $values): array
    {
        return array_filter($values, static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = (string) preg_replace('/\s+/', ' ', $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeForMatch(?string $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        $value = mb_strtolower($value);
        $value = (string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value);
        $value = (string) preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function normalizePostalCode(?string $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        $value = (string) preg_replace('/\D+/', '', $value);

        return $value !== '' ? $value : null;
    }

    private function tokenizeForMatch(?string $value): array
    {
        $normalized = $this->normalizeForMatch($value);

        if ($normalized === null) {
            return [];
        }

        $tokens = explode(' ', $normalized);

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
            return mb_strlen($token) >= 2 && ! in_array($token, $stopWords, true);
        });

        return array_values(array_unique($tokens));
    }

    private function stableHash(array $payload): string
    {
        $normalized = $this->sortArrayRecursively($payload);

        return sha1(json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function sortArrayRecursively(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortArrayRecursively($value);
            }
        }

        return $data;
    }

    private function areCoordinatesValid(float $lat, float $lng): bool
    {
        return $lat >= -90
            && $lat <= 90
            && $lng >= -180
            && $lng <= 180;
    }
}