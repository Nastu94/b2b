<?php

namespace App\Domain\Pricing\Enums;

/**
 * Tipo di modifica applicata da una regola.
 */
enum PricingAdjustmentType: string
{
    case FIXED = 'FIXED';
    case PERCENT = 'PERCENT';

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