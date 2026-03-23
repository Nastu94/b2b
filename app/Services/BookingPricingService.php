<?php

namespace App\Services;

use App\Domain\Pricing\Contracts\PricingResolverInterface;
use App\Domain\Pricing\Data\PricingSimulationInput;
use App\Models\VendorOfferingPricing;
use Carbon\CarbonImmutable;
use RuntimeException;

class BookingPricingService
{
    private const TIMEZONE = 'Europe/Rome';

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
        $date = CarbonImmutable::createFromFormat('Y-m-d', $eventDate, self::TIMEZONE);

        if (! $date || $date->format('Y-m-d') !== $eventDate) {
            throw new RuntimeException('Data evento non valida.');
        }

        $date = $date->startOfDay();
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();

        $pricing = VendorOfferingPricing::query()
            ->active()
            ->forVendor($vendorAccountId)
            ->forOffering($offeringId)
            ->with(['rules' => fn ($query) => $query->active()->ordered()])
            ->first();

        if (! $pricing) {
            throw new RuntimeException('Nessun listino attivo trovato per vendor e servizio selezionati.');
        }

        if ($distanceKm !== null && $distanceKm < 0) {
            throw new RuntimeException('Distanza non valida.');
        }

        if (! $pricing->acceptsDistance($distanceKm)) {
            throw new RuntimeException('Servizio non disponibile per la distanza richiesta.');
        }

        if ($guests !== null && $guests < 1) {
            throw new RuntimeException('Numero ospiti non valido.');
        }

        $input = new PricingSimulationInput(
            eventDate: $date,
            weekday: $date->dayOfWeekIso,
            distanceKm: $distanceKm,
            leadDays: $today->diffInDays($date, false),
            guests: $guests,
        );

        $result = $this->pricingResolver->resolve($pricing, $input);

        if (! $result->hasResolvedPrice()) {
            throw new RuntimeException('Impossibile risolvere un prezzo finale per il contesto selezionato.');
        }

        return [
            'pricing_id' => (int) $pricing->id,
            'offering_id' => $offeringId,
            'vendor_account_id' => $vendorAccountId,
            'base_price' => (float) $result->basePrice,
            'final_price' => (float) $result->resolvedPrice,
            'currency' => (string) $pricing->currencyCode(),
            'breakdown' => $result->breakdown ?? [],
            'matched_rule_ids' => $result->matchedRuleIds ?? [],
            'notes' => $result->notes ?? [],
            'ignored_rules' => $result->ignoredRules ?? [],
        ];
    }
}