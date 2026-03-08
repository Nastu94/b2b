<?php

namespace App\Domain\Pricing\Support;

use App\Domain\Pricing\Contracts\PricingConditionKeys;

/**
 * Support class con regole di validazione riutilizzabili
 * per il dominio pricing.
 *
 * Le regole vengono esposte come array Laravel compatibili
 * per essere usate in Livewire, validator manuali o FormRequest.
 */
final class PricingValidation
{
    /**
     * Costruttore privato per impedire istanziazione.
     */
    private function __construct()
    {
    }

    /**
     * Regole base per il listino.
     *
     * @return array<string, array<int, string>>
     */
    public static function pricingRules(): array
    {
        return [
            'vendor_account_id' => ['required', 'integer', 'exists:vendor_accounts,id'],
            'offering_id' => ['required', 'integer', 'exists:offerings,id'],
            'is_active' => ['required', 'boolean'],
            'price_type' => ['required', 'string', 'in:' . implode(',', PricingOptions::priceTypeValues())],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'service_radius_km' => ['nullable', 'numeric', 'min:0'],
            'distance_pricing_mode' => ['required', 'string', 'in:' . implode(',', PricingOptions::distanceModeValues())],
            'notes_internal' => ['nullable', 'string'],
        ];
    }

    /**
     * Regole base per una pricing rule.
     *
     * @return array<string, array<int, string>>
     */
    public static function pricingRuleRules(): array
    {
        return [
            'vendor_offering_pricing_id' => ['required', 'integer', 'exists:vendor_offering_pricings,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'priority' => ['required', 'integer', 'min:1'],
            'rule_type' => ['required', 'string', 'in:' . implode(',', PricingOptions::ruleTypeValues())],
            'adjustment_type' => ['nullable', 'string', 'in:' . implode(',', PricingOptions::adjustmentTypeValues())],
            'adjustment_value' => ['nullable', 'numeric', 'min:0'],
            'override_price' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'in:' . implode(',', PricingWeekdays::values())],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1'],
            'is_exclusive' => ['required', 'boolean'],
            'conditions' => ['nullable', 'array'],
            'notes_internal' => ['nullable', 'string'],
        ];
    }

    /**
     * Regole di validazione per le chiavi standard delle conditions.
     *
     * @return array<string, array<int, string>>
     */
    public static function pricingConditionsRules(string $prefix = 'conditions'): array
    {
        return [
            $prefix . '.' . PricingConditionKeys::DISTANCE_KM_MIN => ['nullable', 'numeric', 'min:0'],
            $prefix . '.' . PricingConditionKeys::DISTANCE_KM_MAX => ['nullable', 'numeric', 'min:0'],
            $prefix . '.' . PricingConditionKeys::LEAD_DAYS_MIN => ['nullable', 'integer', 'min:0'],
            $prefix . '.' . PricingConditionKeys::LEAD_DAYS_MAX => ['nullable', 'integer', 'min:0'],
            $prefix . '.' . PricingConditionKeys::TIME_FROM => ['nullable', 'date_format:H:i'],
            $prefix . '.' . PricingConditionKeys::TIME_TO => ['nullable', 'date_format:H:i'],
            $prefix . '.' . PricingConditionKeys::GUESTS_MIN => ['nullable', 'integer', 'min:1'],
            $prefix . '.' . PricingConditionKeys::GUESTS_MAX => ['nullable', 'integer', 'min:1'],
            $prefix . '.' . PricingConditionKeys::EVENT_TYPE => ['nullable', 'string', 'max:100'],
            $prefix . '.' . PricingConditionKeys::EVENT_TYPES => ['nullable', 'array'],
            $prefix . '.' . PricingConditionKeys::EVENT_TYPES . '.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Restituisce le regole complete per una pricing rule,
     * includendo anche le chiavi supportate in conditions.
     *
     * @return array<string, array<int, string>>
     */
    public static function fullPricingRuleRules(): array
    {
        return array_merge(
            self::pricingRuleRules(),
            self::pricingConditionsRules()
        );
    }
}