<?php

namespace App\Livewire\Vendor\Pricings;

use App\Models\Offering;
use App\Models\VendorAccount;
use App\Models\VendorOfferingPricing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Componente root per la gestione della sezione listini.
 *
 * Responsabilità:
 * - recuperare il vendor autenticato
 * - caricare i servizi del vendor
 * - gestire la tab attiva
 * - gestire il servizio selezionato
 * - risolvere l'eventuale listino esistente per il servizio selezionato
 *
 * Questo componente non salva ancora dati di pricing:
 * funge da contenitore/orchestratore per le tab figlie.
 */
class ManagePricingsTabs extends Component
{
    use AuthorizesRequests;

    /**
     * Tab attiva corrente.
     */
    public string $activeTab = 'base-pricing';

    /**
     * ID del servizio selezionato.
     */
    public ?int $selectedOfferingId = null;

    /**
     * ID del listino selezionato / risolto per il servizio corrente.
     */
    public ?int $selectedPricingId = null;

    /**
     * Vendor autenticato.
     */
    public VendorAccount $vendorAccount;

    /**
     * Elenco servizi del vendor autenticato.
     *
     * @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Offering>
     */
    public Collection $offerings;

    /**
     * Eventi ascoltati dal componente root.
     *
     * Quando una tab figlia crea o aggiorna un listino/regola,
     * il root ricarica il contesto corrente.
     *
     * @var array<string, string>
     */
    protected $listeners = [
        'pricing-created' => 'handlePricingCreated',
        'pricing-updated' => 'refreshPricingContext',
        'pricing-rule-created' => 'refreshPricingContext',
        'pricing-rule-updated' => 'refreshPricingContext',
        'pricing-rule-deleted' => 'refreshPricingContext',
    ];

    /**
     * Inizializza il componente.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', VendorOfferingPricing::class);

        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        abort_if($user === null, 403, 'Utente non autenticato.');
        abort_if($user->vendorAccount === null, 403, 'Account vendor non disponibile.');

        $this->vendorAccount = $user->vendorAccount;

        $this->loadOfferings();

        /**
         * Se esiste almeno un servizio, selezioniamo il primo automaticamente.
         * Questo rende subito utilizzabile l'interfaccia.
         */
        if ($this->offerings->isNotEmpty()) {
            $this->selectedOfferingId = (int) $this->offerings->first()->id;
        }

        $this->loadPricingContext();
    }

    /**
     * Carica tutti i servizi associati al vendor autenticato
     * tramite la pivot vendor_offerings.
     */
    protected function loadOfferings(): void
    {
        $this->offerings = Offering::query()
            ->select('offerings.*')
            ->join('vendor_offerings', 'vendor_offerings.offering_id', '=', 'offerings.id')
            ->where('vendor_offerings.vendor_account_id', $this->vendorAccount->id)
            ->orderBy('offerings.name')
            ->distinct()
            ->get();
    }

    /**
     * Imposta la tab attiva.
     */
    public function selectTab(string $tab): void
    {
        $allowedTabs = [
            'base-pricing',
            'pricing-rules',
            'pricing-summary',
            'pricing-simulation',
        ];

        if (! in_array($tab, $allowedTabs, true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /**
     * Cambia il servizio selezionato e ricarica il contesto pricing.
     */
    public function selectOffering(int $offeringId): void
    {
        $offeringExists = $this->offerings->contains(
            fn (Offering $offering): bool => (int) $offering->id === $offeringId
        );

        if (! $offeringExists) {
            return;
        }

        $this->selectedOfferingId = $offeringId;
        $this->selectedPricingId = null;

        $this->loadPricingContext();
    }

    /**
     * Hook Livewire: reagisce alla modifica via wire:model del select servizi.
     */
    public function updatedSelectedOfferingId($value): void
    {
        if ($value === null || $value === '') {
            $this->selectedOfferingId = null;
            $this->selectedPricingId = null;

            return;
        }

        $this->selectOffering((int) $value);
    }

    /**
     * Ricarica il contesto del listino per il servizio selezionato.
     *
     * Se per la coppia vendor + servizio esiste già un listino,
     * ne salva l'ID in $selectedPricingId.
     */
    public function loadPricingContext(): void
    {
        if ($this->selectedOfferingId === null) {
            $this->selectedPricingId = null;

            return;
        }

        $pricing = VendorOfferingPricing::query()
            ->forVendor($this->vendorAccount->id)
            ->forOffering($this->selectedOfferingId)
            ->first();

        $this->selectedPricingId = $pricing?->id;
    }

    /**
     * Gestisce l'evento di creazione di un nuovo listino.
     *
     * Dopo la creazione aggiorniamo il contesto corrente in modo
     * che le tab possano lavorare sul listino appena generato.
     */
    public function handlePricingCreated(int $pricingId): void
    {
        $pricing = VendorOfferingPricing::query()
            ->forVendor($this->vendorAccount->id)
            ->find($pricingId);

        if ($pricing === null) {
            $this->refreshPricingContext();

            return;
        }

        $this->selectedOfferingId = (int) $pricing->offering_id;
        $this->selectedPricingId = (int) $pricing->id;
    }

    /**
     * Ricarica il contesto corrente mantenendo il servizio selezionato.
     */
    public function refreshPricingContext(): void
    {
        $this->loadOfferings();
        $this->loadPricingContext();
    }

    /**
     * Restituisce il servizio attualmente selezionato.
     */
    public function getSelectedOfferingProperty(): ?Offering
    {
        if ($this->selectedOfferingId === null) {
            return null;
        }

        return $this->offerings->firstWhere('id', $this->selectedOfferingId);
    }

    /**
     * Indica se per il servizio selezionato esiste già un listino base.
     */
    public function getHasPricingProperty(): bool
    {
        return $this->selectedPricingId !== null;
    }

    /**
     * Restituisce lo stato del listino per il servizio selezionato.
     *
     * Valori possibili:
     * - missing
     * - active
     * - inactive
     */
    public function getPricingStatusProperty(): string
    {
        if ($this->selectedPricingId === null) {
            return 'missing';
        }

        $pricing = VendorOfferingPricing::query()
            ->forVendor($this->vendorAccount->id)
            ->forOffering($this->selectedOfferingId)
            ->first();

        if ($pricing === null) {
            return 'missing';
        }

        return $pricing->is_active ? 'active' : 'inactive';
    }

    /**
     * Render del componente.
     */
    public function render(): View
    {
        return view('livewire.vendor.pricings.manage-pricings-tabs')
            ->layout('layouts.vendor', [
                'title' => 'Listini',
            ]);
    }
}