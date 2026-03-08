<?php

namespace App\Domain\Pricing\Data;

/**
 * DTO di output del resolver pricing.
 *
 * Questo oggetto rappresenta il risultato strutturato
 * della simulazione / risoluzione pricing.
 */
final class PricingSimulationResult
{
    /**
     * Costruisce il risultato della simulazione.
     *
     * @param  array<int, int>  $matchedRuleIds
     * @param  array<int, string>  $notes
     * @param  array<int, array<string, mixed>>  $breakdown
     * @param  array<int, array<string, mixed>>  $ignoredRules
     */
    public function __construct(
        public readonly float $basePrice,
        public readonly ?float $resolvedPrice,
        public readonly array $matchedRuleIds = [],
        public readonly array $notes = [],
        public readonly array $breakdown = [],
        public readonly array $ignoredRules = [],
    ) {
    }

    /**
     * Indica se esiste un prezzo finale risolto.
     */
    public function hasResolvedPrice(): bool
    {
        return $this->resolvedPrice !== null;
    }
}