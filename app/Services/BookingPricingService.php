<?php

namespace App\Services;

use App\Domain\Pricing\Contracts\PricingResolverInterface;
use App\Domain\Pricing\Data\PricingSimulationInput;
use App\Models\VendorOfferingPricing;
use Carbon\Carbon;

class BookingPricingService
{
    public function __construct(
        private readonly PricingResolverInterface $pricingResolver
    ) {
    }

    /**
     * Risolve il prezzo finale per il contesto booking.
     *
     * @return array{
     *     pricing_id:int,
     *     offering_id:int,
     *     vendor_account_id:int,
     *     base_price:float,
     *     final_price:float,
     *     currency:string,
     *     breakdown:array,
     *     matched_rule_ids:array,
     *     notes:array,
     *     ignored_rules:array
     * }
     */
    public function resolveForBooking(
        int $vendorAccountId,
        int $offeringId,
        string $eventDate,
        ?float $distanceKm = null,
        ?int $guests = null,
    ): array {
        $date = Carbon::createFromFormat('Y-m-d', $eventDate)->startOfDay();

        $pricing = VendorOfferingPricing::query()
            ->active()
            ->forVendor($vendorAccountId)
            ->forOffering($offeringId)
            ->with(['rules' => fn ($query) => $query->active()->ordered()])
            ->first();

        if (! $pricing) {
            throw new \RuntimeException('Nessun listino attivo trovato per vendor e servizio selezionati.');
        }

        if (! $pricing->acceptsDistance($distanceKm)) {
            throw new \RuntimeException('Servizio non disponibile per la distanza richiesta.');
        }

        $input = new PricingSimulationInput(
            eventDate: $date,
            weekday: $date->dayOfWeekIso,
            distanceKm: $distanceKm,
            leadDays: now()->startOfDay()->diffInDays($date, false),
            guests: $guests,
        );

        $result = $this->pricingResolver->resolve($pricing, $input);

        if (! $result->hasResolvedPrice()) {
            throw new \RuntimeException('Impossibile risolvere un prezzo finale per il contesto selezionato.');
        }

        return [
            'pricing_id' => $pricing->id,
            'offering_id' => $offeringId,
            'vendor_account_id' => $vendorAccountId,
            'base_price' => (float) $result->basePrice,
            'final_price' => (float) $result->resolvedPrice,
            'currency' => $pricing->currencyCode(),
            'breakdown' => $result->breakdown,
            'matched_rule_ids' => $result->matchedRuleIds,
            'notes' => $result->notes,
            'ignored_rules' => $result->ignoredRules,
        ];
    }
}