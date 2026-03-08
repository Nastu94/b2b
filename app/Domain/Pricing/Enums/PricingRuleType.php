<?php

namespace App\Domain\Pricing\Enums;

/**
 * Tipologia di regola di pricing.
 */
enum PricingRuleType: string
{
    case SURCHARGE = 'SURCHARGE';
    case DISCOUNT = 'DISCOUNT';
    case OVERRIDE = 'OVERRIDE';

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