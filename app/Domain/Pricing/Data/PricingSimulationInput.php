<?php

namespace App\Domain\Pricing\Data;

use Carbon\CarbonInterface;

/**
 * DTO di input per la simulazione / risoluzione del pricing.
 *
 * Questo oggetto centralizza il contesto richiesto dal resolver,
 * evitando l'uso di array liberi e chiavi sparse nel codice.
 */
final class PricingSimulationInput
{
    /**
     * Costruisce il DTO di simulazione.
     */
    public function __construct(
        public readonly ?CarbonInterface $eventDate = null,
        public readonly ?int $weekday = null,
        public readonly ?float $distanceKm = null,
        public readonly ?int $leadDays = null,
        public readonly ?int $guests = null,
    ) {
    }

    /**
     * Restituisce true se almeno un filtro è stato valorizzato.
     */
    public function hasAnyConstraint(): bool
    {
        return $this->eventDate !== null
            || $this->weekday !== null
            || $this->distanceKm !== null
            || $this->leadDays !== null
            || $this->guests !== null;
    }

    /**
     * Restituisce il weekday effettivo.
     *
     * Se non è stato passato esplicitamente ma è presente la data evento,
     * viene derivato usando la convenzione ISO:
     * - 1 = lunedì
     * - 7 = domenica
     */
    public function resolvedWeekday(): ?int
    {
        if ($this->weekday !== null) {
            return $this->weekday;
        }

        return $this->eventDate?->dayOfWeekIso;
    }
}