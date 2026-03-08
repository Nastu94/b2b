<?php

namespace App\Livewire\Vendor\Pricings\Tabs;

use App\Domain\Pricing\Contracts\PricingResolverInterface;
use App\Domain\Pricing\Data\PricingSimulationInput;
use App\Domain\Pricing\Data\PricingSimulationResult;
use App\Models\VendorOfferingPricing;
use App\Models\VendorOfferingPricingRule;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Tab Livewire per la simulazione configurativa del pricing.
 *
 * Responsabilità:
 * - raccogliere i dati di input della simulazione
 * - individuare le regole potenzialmente compatibili
 * - mostrare un riepilogo leggibile del contesto simulato
 *
 * Questa tab non calcola ancora il prezzo finale:
 * il resolver verrà introdotto in un passaggio successivo.
 */
class PricingSimulationTab extends Component
{
    use AuthorizesRequests;

    /**
     * ID del listino corrente.
     */
    public ?int $pricingId = null;

    /**
     * Stato del form simulazione.
     *
     * @var array<string, mixed>
     */
    public array $simulation = [];

    /**
     * Flag che indica se è stata eseguita almeno una simulazione.
     */
    public bool $hasSimulated = false;

    /**
     * ID delle regole risultate compatibili con il contesto simulato.
     *
     * @var array<int, int>
     */
    public array $matchingRuleIds = [];

    /**
     * Dati serializzabili dell'ultima simulazione eseguita tramite resolver.
     *
     * @var array<string, mixed>|null
     */
    public ?array $simulationResult = null;

    /**
     * Inizializza il componente.
     */
    public function mount(?int $pricingId = null): void
    {
        $this->pricingId = $pricingId;
        $this->simulation = $this->getDefaultSimulation();
    }

    /**
     * Restituisce il listino corrente.
     */
    public function getPricingProperty(): ?VendorOfferingPricing
    {
        if ($this->pricingId === null) {
            return null;
        }

        $vendorOfferingPricing = VendorOfferingPricing::query()
            ->with('rules')
            ->find($this->pricingId);

        if ($vendorOfferingPricing !== null) {
            $this->authorize('view', $vendorOfferingPricing);
        }

        return $vendorOfferingPricing;
    }

    /**
     * Restituisce le regole del listino ordinate per priorità.
     */
    public function getRulesProperty()
    {
        if ($this->pricing === null) {
            return collect();
        }

        return $this->pricing->rules->sortBy('priority')->values();
    }

    /**
     * Restituisce le regole compatibili con l'ultima simulazione eseguita.
     */
    public function getMatchingRulesProperty()
    {
        if ($this->matchingRuleIds === []) {
            return collect();
        }

        return $this->rules
            ->whereIn('id', $this->matchingRuleIds)
            ->sortBy('priority')
            ->values();
    }

    /**
     * Restituisce i valori iniziali del form simulazione.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultSimulation(): array
    {
        return [
            'event_date' => null,
            'weekday' => null,
            'distance_km' => null,
            'lead_days' => null,
            'guests' => null,
        ];
    }

    /**
     * Esegue la simulazione tramite resolver pricing.
     */
    public function simulate(PricingResolverInterface $pricingResolver): void
    {
        if ($this->pricing === null) {
            return;
        }

        $validated = $this->validate([
            'simulation.event_date' => ['nullable', 'date'],
            'simulation.weekday' => ['nullable', 'integer', 'in:1,2,3,4,5,6,7'],
            'simulation.distance_km' => ['nullable', 'numeric', 'min:0'],
            'simulation.lead_days' => ['nullable', 'integer', 'min:0'],
            'simulation.guests' => ['nullable', 'integer', 'min:1'],
        ]);

        $eventDate = ! blank($validated['simulation']['event_date'])
            ? Carbon::parse($validated['simulation']['event_date'])
            : null;

        $weekday = ! blank($validated['simulation']['weekday'])
            ? (int) $validated['simulation']['weekday']
            : null;

        /**
         * Se la data evento è presente ma il weekday non è stato valorizzato manualmente,
         * lo ricaviamo dalla data usando convenzione 1=lunedì, 7=domenica.
         */
        if ($eventDate !== null && $weekday === null) {
            $weekday = (int) $eventDate->dayOfWeekIso;
            $this->simulation['weekday'] = $weekday;
        }

        $pricingSimulationInput = new PricingSimulationInput(
            eventDate: $eventDate,
            weekday: $weekday,
            distanceKm: ! blank($validated['simulation']['distance_km'])
                ? (float) $validated['simulation']['distance_km']
                : null,
            leadDays: ! blank($validated['simulation']['lead_days'])
                ? (int) $validated['simulation']['lead_days']
                : null,
            guests: ! blank($validated['simulation']['guests'])
                ? (int) $validated['simulation']['guests']
                : null,
        );

        $pricingSimulationResult = $pricingResolver->resolve(
            $this->pricing,
            $pricingSimulationInput
        );

        $this->simulationResult = [
            'base_price' => $pricingSimulationResult->basePrice,
            'resolved_price' => $pricingSimulationResult->resolvedPrice,
            'matched_rule_ids' => $pricingSimulationResult->matchedRuleIds,
            'notes' => $pricingSimulationResult->notes,
            'breakdown' => $pricingSimulationResult->breakdown,
            'ignored_rules' => $pricingSimulationResult->ignoredRules,
        ];

        $this->matchingRuleIds = $pricingSimulationResult->matchedRuleIds;
        $this->hasSimulated = true;
    }

    /**
     * Reimposta la simulazione allo stato iniziale.
     */
    public function resetSimulation(): void
    {
        $this->resetValidation();
        $this->simulation = $this->getDefaultSimulation();
        $this->hasSimulated = false;
        $this->matchingRuleIds = [];
        $this->simulationResult = null;
    }

    /**
     * Restituisce la label del giorno della settimana.
     */
    public function weekdayLabel(?int $weekday): string
    {
        return match ($weekday) {
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            7 => 'Domenica',
            default => 'Non specificato',
        };
    }

    /**
     * Restituisce una descrizione breve dell'effetto della regola.
     */
    public function ruleValueLabel(VendorOfferingPricingRule $vendorOfferingPricingRule): string
    {
        if ($vendorOfferingPricingRule->isOverrideRule()) {
            if ($vendorOfferingPricingRule->overridePriceValue() === null) {
                return 'Override senza importo';
            }

            return 'Override a ' . number_format($vendorOfferingPricingRule->overridePriceValue(), 2, ',', '.') . ' €';
        }

        if (
            ($vendorOfferingPricingRule->isSurchargeRule() || $vendorOfferingPricingRule->isDiscountRule())
            && $vendorOfferingPricingRule->adjustmentValue() !== null
        ) {
            $prefix = $vendorOfferingPricingRule->isSurchargeRule() ? '+' : '-';

            if ($vendorOfferingPricingRule->usesPercentAdjustment()) {
                $value = $vendorOfferingPricingRule->adjustmentValue();
                $formatted = ((float) (int) $value === $value)
                    ? (string) (int) $value
                    : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

                return $prefix . $formatted . '%';
            }

            return $prefix . number_format($vendorOfferingPricingRule->adjustmentValue(), 2, ',', '.') . ' €';
        }

        return 'Valore non definito';
    }

    /**
     * Restituisce le note dell'ultima simulazione.
     *
     * @return array<int, string>
     */
    public function simulationNotes(): array
    {
        return $this->simulationResult['notes'] ?? [];
    }

    /**
     * Restituisce il prezzo base formattato dell'ultima simulazione.
     */
    public function formattedBasePrice(): string
    {
        $basePrice = $this->simulationResult['base_price'] ?? null;

        if ($basePrice === null) {
            return '-';
        }

        $currency = $this->pricing?->currencyCode() ?? 'EUR';

        return number_format((float) $basePrice, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Restituisce il prezzo finale risolto formattato dell'ultima simulazione.
     */
    public function formattedResolvedPrice(): string
    {
        $resolvedPrice = $this->simulationResult['resolved_price'] ?? null;

        if ($resolvedPrice === null) {
            return '-';
        }

        $currency = $this->pricing?->currencyCode() ?? 'EUR';

        return number_format((float) $resolvedPrice, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Indica se il prezzo finale è diverso dal prezzo base.
     */
    public function hasResolvedPriceDifference(): bool
    {
        if ($this->simulationResult === null) {
            return false;
        }

        $basePrice = $this->simulationResult['base_price'] ?? null;
        $resolvedPrice = $this->simulationResult['resolved_price'] ?? null;

        if ($basePrice === null || $resolvedPrice === null) {
            return false;
        }

        return round((float) $basePrice, 2) !== round((float) $resolvedPrice, 2);
    }

    /**
     * Restituisce il breakdown dell'ultima simulazione.
     *
     * @return array<int, array<string, mixed>>
     */
    public function simulationBreakdown(): array
    {
        return $this->simulationResult['breakdown'] ?? [];
    }

    /**
     * Formatta un delta monetario con segno.
     */
    public function formatDelta(float|int|null $value): string
    {
        if ($value === null) {
            return '-';
        }

        $value = (float) $value;
        $currency = $this->pricing?->currencyCode() ?? 'EUR';
        $prefix = $value > 0 ? '+' : '';

        return $prefix . number_format($value, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Restituisce una label leggibile del valore regola nel breakdown.
     */
    public function formatAdjustmentValue(array $step): string
    {
        if (! array_key_exists('adjustment_type', $step) || ! array_key_exists('adjustment_value', $step)) {
            return '-';
        }

        if ($step['adjustment_type'] === 'PERCENT') {
            return number_format((float) $step['adjustment_value'], 2, ',', '.') . '%';
        }

        $currency = $this->pricing?->currencyCode() ?? 'EUR';

        return number_format((float) $step['adjustment_value'], 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Restituisce le regole compatibili ma ignorate dal resolver.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ignoredRules(): array
    {
        return $this->simulationResult['ignored_rules'] ?? [];
    }

    /**
     * Render del componente.
     */
    public function render(): View
    {
        return view('livewire.vendor.pricings.tabs.pricing-simulation-tab');
    }
}