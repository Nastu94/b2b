<?php

namespace App\Domain\Pricing\Resolvers;

use App\Domain\Pricing\Contracts\PricingResolverInterface;
use App\Domain\Pricing\Data\PricingSimulationInput;
use App\Domain\Pricing\Data\PricingSimulationResult;
use App\Models\VendorOfferingPricing;
use App\Models\VendorOfferingPricingRule;

/**
 * Implementazione base del resolver pricing.
 *
 * In questo step il resolver:
 * - parte dal prezzo base del listino
 * - individua le regole compatibili con il contesto
 * - applica override / surcharge / discount in ordine di priorità
 * - restituisce un risultato strutturato con prezzo risolto
 *
 * La gestione avanzata delle esclusività verrà raffinata
 * in uno step successivo.
 */
class PricingResolver implements PricingResolverInterface
{
    /**
     * Risolve il pricing per il listino e il contesto fornito.
     */
    public function resolve(
        VendorOfferingPricing $vendorOfferingPricing,
        PricingSimulationInput $pricingSimulationInput
    ): PricingSimulationResult {
        $vendorOfferingPricing->loadMissing('rules');

        $matchedRules = $vendorOfferingPricing->rules
            ->filter(function (VendorOfferingPricingRule $vendorOfferingPricingRule) use ($pricingSimulationInput): bool {
                return $vendorOfferingPricingRule->matchesContext(
                    date: $pricingSimulationInput->eventDate,
                    weekday: $pricingSimulationInput->resolvedWeekday(),
                    quantity: null,
                    distanceKm: $pricingSimulationInput->distanceKm,
                    leadDays: $pricingSimulationInput->leadDays,
                    guests: $pricingSimulationInput->guests,
                );
            })
            ->sortBy('priority')
            ->values();

        /**
         * Regole esclusive compatibili ordinate per priorità.
         */
        $exclusiveRules = $matchedRules
            ->filter(fn (VendorOfferingPricingRule $vendorOfferingPricingRule): bool => $vendorOfferingPricingRule->isExclusive())
            ->values();

        $basePrice = $vendorOfferingPricing->resolvableBasePrice();
        $resolvedPrice = $basePrice;
        $notes = [];
        $ignoredRules = [];
        $breakdown = [
            [
                'type' => 'base_price',
                'label' => 'Prezzo base',
                'amount' => $this->normalizeMoney($basePrice),
            ],
        ];

        if (! $vendorOfferingPricing->isUsable()) {
            $notes[] = 'Il listino base è attualmente disattivato.';
        }

        if ($matchedRules->isEmpty()) {
            $notes[] = 'Nessuna regola compatibile con il contesto simulato.';
        } else {
            $notes[] = 'Regole compatibili individuate correttamente.';
        }

        /**
         * Se esiste almeno una regola override compatibile,
         * prevale la prima per priorità.
         */
        $overrideRule = $matchedRules->first(
            fn (VendorOfferingPricingRule $vendorOfferingPricingRule): bool => $vendorOfferingPricingRule->isOverrideRule()
        );

        if ($overrideRule !== null && $overrideRule->overridePriceValue() !== null) {
            $resolvedPrice = $overrideRule->overridePriceValue();

            $breakdown[] = [
                'type' => 'override',
                'rule_id' => (int) $overrideRule->id,
                'rule_name' => $overrideRule->name,
                'label' => 'Override prezzo',
                'amount' => $this->normalizeMoney($resolvedPrice),
                'priority' => (int) $overrideRule->priority,
            ];

            $notes[] = 'È stata applicata una regola override con priorità più alta.';

            $ignoredRules = $matchedRules
                ->filter(fn (VendorOfferingPricingRule $vendorOfferingPricingRule): bool => (int) $vendorOfferingPricingRule->id !== (int) $overrideRule->id)
                ->map(function (VendorOfferingPricingRule $vendorOfferingPricingRule): array {
                    return [
                        'rule_id' => (int) $vendorOfferingPricingRule->id,
                        'rule_name' => $vendorOfferingPricingRule->name,
                        'priority' => (int) $vendorOfferingPricingRule->priority,
                        'reason' => 'Scartata perché superata da una regola override con priorità applicativa assoluta.',
                    ];
                })
                ->values()
                ->all();

            return new PricingSimulationResult(
                basePrice: $basePrice,
                resolvedPrice: $this->normalizeMoney($resolvedPrice),
                matchedRuleIds: $matchedRules
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
                notes: $notes,
                breakdown: $breakdown,
                ignoredRules: $ignoredRules,
            );
        }

        /**
         * In assenza di override, se esiste almeno una regola esclusiva compatibile
         * applichiamo solo la prima per priorità e ignoriamo tutte le altre.
         */
        $exclusiveRule = $exclusiveRules->first();

        if ($exclusiveRule !== null) {
            $resolvedPrice = $this->applyRuleToPrice($resolvedPrice, $exclusiveRule);

            if ($exclusiveRule->isSurchargeRule() || $exclusiveRule->isDiscountRule()) {
                $adjustmentValue = $exclusiveRule->adjustmentValue();
                $delta = $this->calculateRuleDelta($basePrice, $exclusiveRule);

                $breakdown[] = [
                    'type' => $exclusiveRule->isDiscountRule() ? 'discount' : 'surcharge',
                    'rule_id' => (int) $exclusiveRule->id,
                    'rule_name' => $exclusiveRule->name,
                    'label' => $exclusiveRule->isDiscountRule() ? 'Sconto esclusivo' : 'Maggiorazione esclusiva',
                    'adjustment_type' => $exclusiveRule->usesPercentAdjustment() ? 'PERCENT' : 'FIXED',
                    'adjustment_value' => $this->normalizeMoney($adjustmentValue ?? 0),
                    'delta' => $exclusiveRule->isDiscountRule()
                        ? -$this->normalizeMoney($delta)
                        : $this->normalizeMoney($delta),
                    'result_price' => $this->normalizeMoney($resolvedPrice),
                    'priority' => (int) $exclusiveRule->priority,
                    'exclusive' => true,
                ];
            }

            $notes[] = 'È stata applicata una regola esclusiva: tutte le altre regole compatibili sono state ignorate.';

            $ignoredRules = $matchedRules
                ->filter(fn (VendorOfferingPricingRule $vendorOfferingPricingRule): bool => (int) $vendorOfferingPricingRule->id !== (int) $exclusiveRule->id)
                ->map(function (VendorOfferingPricingRule $vendorOfferingPricingRule): array {
                    return [
                        'rule_id' => (int) $vendorOfferingPricingRule->id,
                        'rule_name' => $vendorOfferingPricingRule->name,
                        'priority' => (int) $vendorOfferingPricingRule->priority,
                        'reason' => 'Scartata perché superata da una regola esclusiva compatibile.',
                    ];
                })
                ->values()
                ->all();

            return new PricingSimulationResult(
                basePrice: $basePrice,
                resolvedPrice: $this->normalizeMoney(max(0, $resolvedPrice)),
                matchedRuleIds: $matchedRules
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
                notes: $notes,
                breakdown: $breakdown,
                ignoredRules: $ignoredRules,
            );
        }

        /**
         * In assenza di override, applichiamo in sequenza
         * surcharge e discount compatibili.
         */
        foreach ($matchedRules as $vendorOfferingPricingRule) {
            if (! $vendorOfferingPricingRule->isSurchargeRule() && ! $vendorOfferingPricingRule->isDiscountRule()) {
                continue;
            }

            $adjustmentValue = $vendorOfferingPricingRule->adjustmentValue();

            if ($adjustmentValue === null) {
                continue;
            }

            $delta = $this->calculateRuleDelta($resolvedPrice, $vendorOfferingPricingRule);

            if ($vendorOfferingPricingRule->isDiscountRule()) {
                $resolvedPrice -= $delta;

                $breakdown[] = [
                    'type' => 'discount',
                    'rule_id' => (int) $vendorOfferingPricingRule->id,
                    'rule_name' => $vendorOfferingPricingRule->name,
                    'label' => 'Sconto',
                    'adjustment_type' => $vendorOfferingPricingRule->usesPercentAdjustment() ? 'PERCENT' : 'FIXED',
                    'adjustment_value' => $this->normalizeMoney($adjustmentValue),
                    'delta' => -$this->normalizeMoney($delta),
                    'result_price' => $this->normalizeMoney($resolvedPrice),
                    'priority' => (int) $vendorOfferingPricingRule->priority,
                ];
            } else {
                $resolvedPrice += $delta;

                $breakdown[] = [
                    'type' => 'surcharge',
                    'rule_id' => (int) $vendorOfferingPricingRule->id,
                    'rule_name' => $vendorOfferingPricingRule->name,
                    'label' => 'Maggiorazione',
                    'adjustment_type' => $vendorOfferingPricingRule->usesPercentAdjustment() ? 'PERCENT' : 'FIXED',
                    'adjustment_value' => $this->normalizeMoney($adjustmentValue),
                    'delta' => $this->normalizeMoney($delta),
                    'result_price' => $this->normalizeMoney($resolvedPrice),
                    'priority' => (int) $vendorOfferingPricingRule->priority,
                ];
            }
        }

        /**
         * Non permettiamo prezzi finali negativi.
         */
        $resolvedPrice = max(0, $resolvedPrice);

        if ($resolvedPrice !== $basePrice) {
            $notes[] = 'Il prezzo finale è stato aggiornato applicando le regole compatibili in ordine di priorità.';
        } else {
            $notes[] = 'Il prezzo finale coincide con il prezzo base del listino.';
        }

        return new PricingSimulationResult(
            basePrice: $basePrice,
            resolvedPrice: $this->normalizeMoney($resolvedPrice),
            matchedRuleIds: $matchedRules
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
            notes: $notes,
            breakdown: $breakdown,
            ignoredRules: $ignoredRules,
        );
    }

    /**
     * Applica una singola regola al prezzo corrente.
     */
    private function applyRuleToPrice(float $currentPrice, VendorOfferingPricingRule $vendorOfferingPricingRule): float
    {
        if ($vendorOfferingPricingRule->isOverrideRule() && $vendorOfferingPricingRule->overridePriceValue() !== null) {
            return $vendorOfferingPricingRule->overridePriceValue();
        }

        $delta = $this->calculateRuleDelta($currentPrice, $vendorOfferingPricingRule);

        if ($vendorOfferingPricingRule->isDiscountRule()) {
            return $currentPrice - $delta;
        }

        if ($vendorOfferingPricingRule->isSurchargeRule()) {
            return $currentPrice + $delta;
        }

        return $currentPrice;
    }

    /**
     * Calcola il delta monetario prodotto da una singola regola.
     */
    private function calculateRuleDelta(float $currentPrice, VendorOfferingPricingRule $vendorOfferingPricingRule): float
    {
        $adjustmentValue = $vendorOfferingPricingRule->adjustmentValue();

        if ($adjustmentValue === null) {
            return 0.0;
        }

        if ($vendorOfferingPricingRule->usesPercentAdjustment()) {
            return $currentPrice * ($adjustmentValue / 100);
        }

        return $adjustmentValue;
    }

    /**
     * Normalizza un importo monetario a 2 decimali.
     */
    private function normalizeMoney(float $amount): float
    {
        return round($amount, 2);
    }
}