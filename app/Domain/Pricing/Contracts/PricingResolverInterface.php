<?php

namespace App\Domain\Pricing\Contracts;

use App\Domain\Pricing\Data\PricingSimulationInput;
use App\Domain\Pricing\Data\PricingSimulationResult;
use App\Models\VendorOfferingPricing;

/**
 * Contratto del motore di risoluzione pricing.
 *
 * Responsabilità:
 * - ricevere un listino base e un contesto di simulazione
 * - determinare le regole compatibili
 * - restituire un risultato strutturato
 *
 * L'implementazione concreta verrà introdotta in uno step successivo.
 */
interface PricingResolverInterface
{
    /**
     * Risolve il pricing per il listino e il contesto fornito.
     */
    public function resolve(
        VendorOfferingPricing $vendorOfferingPricing,
        PricingSimulationInput $pricingSimulationInput
    ): PricingSimulationResult;
}