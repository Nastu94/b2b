<?php

namespace App\Domain\Pricing\Enums;

/**
 * Modalità di gestione distanza del listino.
 */
enum PricingDistanceMode: string
{
    case INCLUDED = 'INCLUDED';
    case SURCHARGE_BY_RULE = 'SURCHARGE_BY_RULE';
    case NOT_AVAILABLE_OUTSIDE_RADIUS = 'NOT_AVAILABLE_OUTSIDE_RADIUS';

    /**
     * Restituisce i valori scalari utilizzabili in validazione o select UI.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}