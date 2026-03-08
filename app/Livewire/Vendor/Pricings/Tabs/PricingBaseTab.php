<?php

namespace App\Livewire\Vendor\Pricings\Tabs;

use App\Domain\Pricing\Support\PricingOptions;
use App\Domain\Pricing\Support\PricingValidation;
use App\Models\VendorOfferingPricing;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Tab Livewire per la gestione del listino base.
 *
 * Responsabilità:
 * - creare il listino base se non esiste
 * - aggiornare il listino base se già presente
 * - gestire i campi principali del pricing vendor + servizio
 */
class PricingBaseTab extends Component
{
    use AuthorizesRequests;

    /**
     * ID del vendor proprietario del listino.
     */
    public int $vendorAccountId;

    /**
     * ID del servizio selezionato.
     */
    public int $offeringId;

    /**
     * ID del listino base, se già esistente.
     */
    public ?int $pricingId = null;

    /**
     * Stato del form.
     *
     * @var array<string, mixed>
     */
    public array $form = [];

    /**
     * Inizializza il componente.
     */
    public function mount(int $vendorAccountId, int $offeringId, ?int $pricingId = null): void
    {
        $this->vendorAccountId = $vendorAccountId;
        $this->offeringId = $offeringId;
        $this->pricingId = $pricingId;

        $this->loadPricing();
    }

    /**
     * Carica il listino esistente oppure inizializza il form di default.
     */
    public function loadPricing(): void
    {
        if ($this->pricingId === null) {
            $this->authorize('create', VendorOfferingPricing::class);

            $this->form = $this->getDefaultForm();

            return;
        }

        $vendorOfferingPricing = VendorOfferingPricing::query()
            ->where('vendor_account_id', $this->vendorAccountId)
            ->where('offering_id', $this->offeringId)
            ->findOrFail($this->pricingId);

        $this->authorize('view', $vendorOfferingPricing);

        $this->form = [
            'is_active' => (bool) $vendorOfferingPricing->is_active,
            'price_type' => $vendorOfferingPricing->price_type?->value ?? $vendorOfferingPricing->price_type,
            'base_price' => $vendorOfferingPricing->base_price,
            'currency' => $vendorOfferingPricing->currency,
            'service_radius_km' => $vendorOfferingPricing->service_radius_km,
            'distance_pricing_mode' => $vendorOfferingPricing->distance_pricing_mode?->value ?? $vendorOfferingPricing->distance_pricing_mode,
            'notes_internal' => $vendorOfferingPricing->notes_internal,
        ];
    }

    /**
     * Restituisce i valori di default del form.
     *
     * @return array<string, mixed>
     */
    protected function getDefaultForm(): array
    {
        return [
            'is_active' => true,
            'price_type' => 'FIXED',
            'base_price' => '0.00',
            'currency' => 'EUR',
            'service_radius_km' => null,
            'distance_pricing_mode' => 'INCLUDED',
            'notes_internal' => null,
        ];
    }

    /**
     * Salva il listino base.
     *
     * Se il listino non esiste viene creato.
     * Se esiste già viene aggiornato.
     */
    public function save(): void
    {
        $rules = PricingValidation::pricingRules();

        /**
         * I campi di ownership non vengono presi dal client:
         * li imponiamo dal contesto corrente.
         */
        unset($rules['vendor_account_id'], $rules['offering_id']);

        $validated = $this->validate([
            'form.is_active' => $rules['is_active'],
            'form.price_type' => $rules['price_type'],
            'form.base_price' => $rules['base_price'],
            'form.currency' => $rules['currency'],
            'form.service_radius_km' => $rules['service_radius_km'],
            'form.distance_pricing_mode' => $rules['distance_pricing_mode'],
            'form.notes_internal' => $rules['notes_internal'],
        ]);

        $payload = [
            'vendor_account_id' => $this->vendorAccountId,
            'offering_id' => $this->offeringId,
            'is_active' => $validated['form']['is_active'],
            'price_type' => $validated['form']['price_type'],
            'base_price' => $validated['form']['base_price'],
            'currency' => strtoupper((string) $validated['form']['currency']),
            'service_radius_km' => $validated['form']['service_radius_km'],
            'distance_pricing_mode' => $validated['form']['distance_pricing_mode'],
            'notes_internal' => $validated['form']['notes_internal'],
        ];

        if ($this->pricingId === null) {
            $this->authorize('create', VendorOfferingPricing::class);

            $vendorOfferingPricing = VendorOfferingPricing::query()->create($payload);

            $this->pricingId = (int) $vendorOfferingPricing->id;

            $this->dispatch('pricing-created', pricingId: $vendorOfferingPricing->id);
            $this->dispatch('pricing-updated');
        } else {
            $vendorOfferingPricing = VendorOfferingPricing::query()
                ->where('vendor_account_id', $this->vendorAccountId)
                ->where('offering_id', $this->offeringId)
                ->findOrFail($this->pricingId);

            $this->authorize('update', $vendorOfferingPricing);

            $vendorOfferingPricing->update($payload);

            $this->dispatch('pricing-updated');
        }

        session()->flash('pricing_base_success', 'Listino base salvato correttamente.');

        $this->loadPricing();
    }

    /**
     * Restituisce le opzioni select del dominio pricing.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public function getOptionsProperty(): array
    {
        return [
            'priceTypes' => PricingOptions::priceTypeOptions(),
            'distanceModes' => PricingOptions::distanceModeOptions(),
        ];
    }

    /**
     * Render del componente.
     */
    public function render(): View
    {
        return view('livewire.vendor.pricings.tabs.pricing-base-tab');
    }
}