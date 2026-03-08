<?php

namespace App\Domain\Pricing\Enums;

/**
 * Tipologia di prezzo base del listino.
 */
enum PricingPriceType: string
{
    case FIXED = 'FIXED';
    case STARTING_FROM = 'STARTING_FROM';
    case FREE = 'FREE';

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