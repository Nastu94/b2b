<?php

namespace App\Livewire\Vendor\Pricings\Tabs;

use App\Domain\Pricing\Support\PricingOptions;
use App\Models\VendorOfferingPricing;
use App\Models\VendorOfferingPricingRule;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Tab Livewire di riepilogo configurazione pricing.
 *
 * Responsabilità:
 * - mostrare una lettura umana del listino base
 * - riepilogare le regole configurate
 * - evidenziare eventuali warning utili
 *
 * Questa tab non modifica dati.
 */
class PricingSummaryTab extends Component
{
    use AuthorizesRequests;

    /**
     * ID del listino corrente.
     */
    public ?int $pricingId = null;

    /**
     * Inizializza il componente.
     */
    public function mount(?int $pricingId = null): void
    {
        $this->pricingId = $pricingId;
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
     * Restituisce le regole del listino.
     */
    public function getRulesProperty()
    {
        if ($this->pricing === null) {
            return collect();
        }

        return $this->pricing->rules->sortBy('priority')->values();
    }

    /**
     * Restituisce eventuali warning configurativi.
     *
     * @return array<int, string>
     */
    public function getWarningsProperty(): array
    {
        if ($this->pricing === null) {
            return [];
        }

        $warnings = [];

        if (! $this->pricing->is_active) {
            $warnings[] = 'Il listino base è presente ma attualmente disattivato.';
        }

        if (! $this->pricing->isFree() && $this->pricing->basePriceValue() <= 0) {
            $warnings[] = 'Il prezzo base è pari a 0,00 ma il tipo prezzo non è impostato come gratuito.';
        }

        if ($this->pricing->blocksOutsideRadius() && ! $this->pricing->hasServiceRadius()) {
            $warnings[] = 'Hai bloccato il servizio fuori raggio, ma non hai ancora impostato un raggio di servizio.';
        }

        if ($this->pricing->usesDistanceRules() && ! $this->hasActiveDistanceRules()) {
            $warnings[] = 'La distanza è gestita tramite regole, ma non risultano regole attive con condizioni di distanza.';
        }

        if ($this->rules->isEmpty()) {
            $warnings[] = 'Non è presente nessuna regola aggiuntiva: il listino userà solo la configurazione base.';
        }

        return $warnings;
    }

    /**
     * Numero di regole attive.
     */
    public function activeRulesCount(): int
    {
        return $this->rules
            ->filter(fn (VendorOfferingPricingRule $rule): bool => (bool) $rule->is_active)
            ->count();
    }

    /**
     * Numero di regole esclusive.
     */
    public function exclusiveRulesCount(): int
    {
        return $this->rules
            ->filter(fn (VendorOfferingPricingRule $rule): bool => $rule->isExclusive())
            ->count();
    }

    /**
     * Numero di regole con vincolo date.
     */
    public function dateRulesCount(): int
    {
        return $this->rules
            ->filter(fn (VendorOfferingPricingRule $rule): bool => $rule->hasDateRange())
            ->count();
    }

    /**
     * Numero di regole con vincolo giorni della settimana.
     */
    public function weekdayRulesCount(): int
    {
        return $this->rules
            ->filter(fn (VendorOfferingPricingRule $rule): bool => $rule->hasWeekdayConstraint())
            ->count();
    }

    /**
     * Numero di regole con vincolo distanza.
     */
    public function distanceRulesCount(): int
    {
        return $this->rules
            ->filter(fn (VendorOfferingPricingRule $rule): bool => $rule->hasDistanceCondition())
            ->count();
    }

    /**
     * Numero di regole con vincolo anticipo.
     */
    public function leadDaysRulesCount(): int
    {
        return $this->rules
            ->filter(fn (VendorOfferingPricingRule $rule): bool => $rule->hasLeadDaysCondition())
            ->count();
    }

    /**
     * Numero di regole con vincolo ospiti.
     */
    public function guestsRulesCount(): int
    {
        return $this->rules
            ->filter(fn (VendorOfferingPricingRule $rule): bool => $rule->hasGuestsCondition())
            ->count();
    }

    /**
     * Indica se esistono regole attive con condizioni di distanza.
     */
    private function hasActiveDistanceRules(): bool
    {
        return $this->rules
            ->contains(fn (VendorOfferingPricingRule $rule): bool => (bool) $rule->is_active && $rule->hasDistanceCondition());
    }

    /**
     * Restituisce la label leggibile dello stato del listino.
     */
    public function pricingStatusLabel(): string
    {
        if ($this->pricing === null) {
            return 'Da creare';
        }

        return $this->pricing->is_active ? 'Configurato' : 'Inattivo';
    }

    /**
     * Restituisce la label leggibile del tipo prezzo.
     */
    public function priceTypeLabel(): string
    {
        if ($this->pricing === null) {
            return '-';
        }

        return PricingOptions::priceTypeLabel($this->pricing->price_type);
    }

    /**
     * Restituisce la label leggibile della gestione distanza.
     */
    public function distanceModeLabel(): string
    {
        if ($this->pricing === null) {
            return '-';
        }

        return PricingOptions::distanceModeLabel($this->pricing->distance_pricing_mode);
    }

    /**
     * Formatta il prezzo base.
     */
    public function formattedBasePrice(): string
    {
        if ($this->pricing === null) {
            return '-';
        }

        return number_format($this->pricing->resolvableBasePrice(), 2, ',', '.') . ' ' . $this->pricing->currencyCode();
    }

    /**
     * Formatta il raggio di servizio.
     */
    public function formattedServiceRadius(): string
    {
        if ($this->pricing === null || ! $this->pricing->hasServiceRadius()) {
            return 'Non impostato';
        }

        return number_format($this->pricing->serviceRadiusKmValue(), 2, ',', '.') . ' km';
    }

    /**
     * Render del componente.
     */
    public function render(): View
    {
        return view('livewire.vendor.pricings.tabs.pricing-summary-tab');
    }
}