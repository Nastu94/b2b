<?php

namespace App\Domain\Pricing\Support;

use App\Domain\Pricing\Enums\PricingAdjustmentType;
use App\Domain\Pricing\Enums\PricingDistanceMode;
use App\Domain\Pricing\Enums\PricingPriceType;
use App\Domain\Pricing\Enums\PricingRuleType;

/**
 * Support class per opzioni select, label e valori ammessi
 * del dominio pricing.
 *
 * Questa classe evita la duplicazione di array hardcoded tra:
 * - Livewire
 * - validazione
 * - blade
 * - service applicativi
 */
final class PricingOptions
{
    /**
     * Costruttore privato per impedire istanziazione.
     */
    private function __construct()
    {
    }

    /**
     * Elenco valori ammessi per il tipo prezzo base.
     *
     * @return array<int, string>
     */
    public static function priceTypeValues(): array
    {
        return PricingPriceType::values();
    }

    /**
     * Elenco opzioni per select del tipo prezzo base.
     *
     * @return array<int, array<string, string>>
     */
    public static function priceTypeOptions(): array
    {
        return array_map(
            fn (PricingPriceType $type): array => [
                'value' => $type->value,
                'label' => self::priceTypeLabel($type),
            ],
            PricingPriceType::cases()
        );
    }

    /**
     * Restituisce la label leggibile del tipo prezzo base.
     */
    public static function priceTypeLabel(PricingPriceType|string|null $type): string
    {
        $type = self::normalizePriceType($type);

        return match ($type) {
            PricingPriceType::FIXED => 'Prezzo fisso',
            PricingPriceType::STARTING_FROM => 'A partire da',
            PricingPriceType::FREE => 'Gratis',
            default => '',
        };
    }

    /**
     * Elenco valori ammessi per la modalità distanza.
     *
     * @return array<int, string>
     */
    public static function distanceModeValues(): array
    {
        return PricingDistanceMode::values();
    }

    /**
     * Elenco opzioni per select della modalità distanza.
     *
     * @return array<int, array<string, string>>
     */
    public static function distanceModeOptions(): array
    {
        return array_map(
            fn (PricingDistanceMode $mode): array => [
                'value' => $mode->value,
                'label' => self::distanceModeLabel($mode),
            ],
            PricingDistanceMode::cases()
        );
    }

    /**
     * Restituisce la label leggibile della modalità distanza.
     */
    public static function distanceModeLabel(PricingDistanceMode|string|null $mode): string
    {
        $mode = self::normalizeDistanceMode($mode);

        return match ($mode) {
            PricingDistanceMode::INCLUDED => 'Inclusa nel prezzo',
            PricingDistanceMode::SURCHARGE_BY_RULE => 'Gestita con regole',
            PricingDistanceMode::NOT_AVAILABLE_OUTSIDE_RADIUS => 'Non disponibile fuori raggio',
            default => '',
        };
    }

    /**
     * Elenco valori ammessi per il tipo regola.
     *
     * @return array<int, string>
     */
    public static function ruleTypeValues(): array
    {
        return PricingRuleType::values();
    }

    /**
     * Elenco opzioni per select del tipo regola.
     *
     * @return array<int, array<string, string>>
     */
    public static function ruleTypeOptions(): array
    {
        return array_map(
            fn (PricingRuleType $type): array => [
                'value' => $type->value,
                'label' => self::ruleTypeLabel($type),
            ],
            PricingRuleType::cases()
        );
    }

    /**
     * Restituisce la label leggibile del tipo regola.
     */
    public static function ruleTypeLabel(PricingRuleType|string|null $type): string
    {
        $type = self::normalizeRuleType($type);

        return match ($type) {
            PricingRuleType::SURCHARGE => 'Maggiorazione',
            PricingRuleType::DISCOUNT => 'Sconto',
            PricingRuleType::OVERRIDE => 'Override prezzo',
            default => '',
        };
    }

    /**
     * Elenco valori ammessi per il tipo di aggiustamento.
     *
     * @return array<int, string>
     */
    public static function adjustmentTypeValues(): array
    {
        return PricingAdjustmentType::values();
    }

    /**
     * Elenco opzioni per select del tipo di aggiustamento.
     *
     * @return array<int, array<string, string>>
     */
    public static function adjustmentTypeOptions(): array
    {
        return array_map(
            fn (PricingAdjustmentType $type): array => [
                'value' => $type->value,
                'label' => self::adjustmentTypeLabel($type),
            ],
            PricingAdjustmentType::cases()
        );
    }

    /**
     * Restituisce la label leggibile del tipo di aggiustamento.
     */
    public static function adjustmentTypeLabel(PricingAdjustmentType|string|null $type): string
    {
        $type = self::normalizeAdjustmentType($type);

        return match ($type) {
            PricingAdjustmentType::FIXED => 'Importo fisso',
            PricingAdjustmentType::PERCENT => 'Percentuale',
            default => '',
        };
    }

    /**
     * Normalizza il tipo prezzo in enum, se possibile.
     */
    private static function normalizePriceType(PricingPriceType|string|null $type): ?PricingPriceType
    {
        if ($type instanceof PricingPriceType) {
            return $type;
        }

        if (is_string($type)) {
            return PricingPriceType::tryFrom($type);
        }

        return null;
    }

    /**
     * Normalizza la modalità distanza in enum, se possibile.
     */
    private static function normalizeDistanceMode(PricingDistanceMode|string|null $mode): ?PricingDistanceMode
    {
        if ($mode instanceof PricingDistanceMode) {
            return $mode;
        }

        if (is_string($mode)) {
            return PricingDistanceMode::tryFrom($mode);
        }

        return null;
    }

    /**
     * Normalizza il tipo regola in enum, se possibile.
     */
    private static function normalizeRuleType(PricingRuleType|string|null $type): ?PricingRuleType
    {
        if ($type instanceof PricingRuleType) {
            return $type;
        }

        if (is_string($type)) {
            return PricingRuleType::tryFrom($type);
        }

        return null;
    }

    /**
     * Normalizza il tipo aggiustamento in enum, se possibile.
     */
    private static function normalizeAdjustmentType(PricingAdjustmentType|string|null $type): ?PricingAdjustmentType
    {
        if ($type instanceof PricingAdjustmentType) {
            return $type;
        }

        if (is_string($type)) {
            return PricingAdjustmentType::tryFrom($type);
        }

        return null;
    }
}