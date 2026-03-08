<?php

namespace App\Domain\Pricing\Support;

use App\Domain\Pricing\Contracts\PricingConditionKeys;

/**
 * Support class per le condizioni avanzate delle regole pricing.
 *
 * Centralizza lettura, scrittura e pulizia delle chiavi del payload
 * conditions per evitare accessi hardcoded sparsi nel codice.
 */
final class PricingConditions
{
    /**
     * Costruttore privato per impedire istanziazione.
     */
    private function __construct()
    {
    }

    /**
     * Restituisce il payload conditions ripulito da chiavi non supportate
     * e valori vuoti non significativi.
     *
     * @param  array<string, mixed>|null  $conditions
     * @return array<string, mixed>
     */
    public static function sanitize(?array $conditions): array
    {
        if ($conditions === null) {
            return [];
        }

        $allowedKeys = PricingConditionKeys::values();
        $sanitized = [];

        foreach ($conditions as $key => $value) {
            if (! in_array($key, $allowedKeys, true)) {
                continue;
            }

            if (self::isEmptyValue($value)) {
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Restituisce una chiave specifica dal payload conditions.
     *
     * @param  array<string, mixed>|null  $conditions
     */
    public static function get(?array $conditions, string $key, mixed $default = null): mixed
    {
        if ($conditions === null) {
            return $default;
        }

        return $conditions[$key] ?? $default;
    }

    /**
     * Imposta o rimuove una chiave nel payload conditions.
     *
     * @param  array<string, mixed>|null  $conditions
     * @return array<string, mixed>
     */
    public static function put(?array $conditions, string $key, mixed $value): array
    {
        $conditions = $conditions ?? [];

        if (! in_array($key, PricingConditionKeys::values(), true)) {
            return self::sanitize($conditions);
        }

        if (self::isEmptyValue($value)) {
            unset($conditions[$key]);

            return self::sanitize($conditions);
        }

        $conditions[$key] = $value;

        return self::sanitize($conditions);
    }

    /**
     * Rimuove una chiave dal payload conditions.
     *
     * @param  array<string, mixed>|null  $conditions
     * @return array<string, mixed>
     */
    public static function forget(?array $conditions, string $key): array
    {
        $conditions = $conditions ?? [];

        unset($conditions[$key]);

        return self::sanitize($conditions);
    }

    /**
     * Verifica se una chiave standard è valorizzata.
     *
     * @param  array<string, mixed>|null  $conditions
     */
    public static function has(?array $conditions, string $key): bool
    {
        if ($conditions === null || ! array_key_exists($key, $conditions)) {
            return false;
        }

        return ! self::isEmptyValue($conditions[$key]);
    }

    /**
     * Restituisce true se il valore deve essere considerato vuoto.
     */
    private static function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && $value === []) {
            return true;
        }

        return false;
    }
}