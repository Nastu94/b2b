<?php

namespace App\Domain\Pricing\Contracts;

/**
 * Chiavi standard utilizzabili nel payload JSON delle conditions
 * delle regole di pricing.
 *
 * Questo contratto centralizza i nomi delle chiavi per evitare
 * stringhe hardcoded sparse tra form, service e resolver.
 */
final class PricingConditionKeys
{
    public const DISTANCE_KM_MIN = 'distance_km_min';
    public const DISTANCE_KM_MAX = 'distance_km_max';

    public const LEAD_DAYS_MIN = 'lead_days_min';
    public const LEAD_DAYS_MAX = 'lead_days_max';

    public const TIME_FROM = 'time_from';
    public const TIME_TO = 'time_to';

    public const GUESTS_MIN = 'guests_min';
    public const GUESTS_MAX = 'guests_max';

    public const EVENT_TYPE = 'event_type';
    public const EVENT_TYPES = 'event_types';

    /**
     * Costruttore privato per impedire istanziazione.
     */
    private function __construct()
    {
    }

    /**
     * Restituisce tutte le chiavi standard supportate.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::DISTANCE_KM_MIN,
            self::DISTANCE_KM_MAX,
            self::LEAD_DAYS_MIN,
            self::LEAD_DAYS_MAX,
            self::TIME_FROM,
            self::TIME_TO,
            self::GUESTS_MIN,
            self::GUESTS_MAX,
            self::EVENT_TYPE,
            self::EVENT_TYPES,
        ];
    }
}