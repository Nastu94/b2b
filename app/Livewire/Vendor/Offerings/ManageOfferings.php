<?php

namespace App\Livewire\Vendor\Offerings;

use App\Models\Offering;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.vendor')]
class ManageOfferings extends Component
{
    use AuthorizesRequests;

    /** @var \Illuminate\Support\Collection<int, \App\Models\Offering> */
    public Collection $availableOfferings;

    /** @var array<int> IDs offerings selezionate */
    public array $selectedOfferingIds = [];

    /**
     * IDs services attivi calcolati dal DB (source of truth).
     * Questa lista viene usata per renderizzare le schede contenuti in Blade,
     * evitando di fidarsi di selectedOfferingIds (client-side).
     *
     * @var array<int, int>
     */
    public array $activeOfferingIds = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        // Gating: il pannello vendor richiede vendor.access
        abort_unless($user->can('vendor.access'), 403);

        $vendorAccount = $user->vendorAccount;

        // Difesa in profondità: anche se c'è il middleware, evitiamo errori e accessi anomali
        if (!$vendorAccount || $vendorAccount->trashed()) {
            abort(403);
        }

        // solo offerings della categoria del vendor
        $this->availableOfferings = Offering::query()
            ->where('category_id', $vendorAccount->category_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // pre-seleziona quelle già associate
        $this->selectedOfferingIds = $vendorAccount->offerings()
            ->wherePivot('is_active', true)
            ->pluck('offerings.id')
            ->map(fn($id) => (int) $id)
            ->toArray();
        $this->activeOfferingIds = $this->selectedOfferingIds;
    }

    /**
     * Salva le offerings selezionate per il vendor, attivando quelle nuove e disattivando quelle rimosse.
     */
    public function save(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        abort_unless($user->can('vendor.access'), 403);

        $vendorAccount = $user->vendorAccount;

        if (!$vendorAccount || $vendorAccount->trashed()) {
            abort(403);
        }

        // Sicurezza: allowed IDs ricalcolati dal DB (source of truth)
        $allowedIds = Offering::query()
            ->where('category_id', $vendorAccount->category_id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $finalIds = array_values(array_intersect(
            array_map('intval', $this->selectedOfferingIds),
            $allowedIds
        ));

        // Sync "soft": attiva i selezionati, disattiva gli altri (manteniamo righe pivot con is_active)
        $current = $vendorAccount->offerings()
            ->pluck('offerings.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($allowedIds as $offeringId) {
            $isActive = in_array($offeringId, $finalIds, true);

            if (in_array($offeringId, $current, true)) {
                $vendorAccount->offerings()->updateExistingPivot($offeringId, ['is_active' => $isActive]);
            } else {
                if ($isActive) {
                    $vendorAccount->offerings()->attach($offeringId, ['is_active' => true]);
                }
            }
        }

        // Ricarica dal DB solo quelli davvero attivi (stato "source of truth")
        $this->selectedOfferingIds = $vendorAccount->offerings()
            ->wherePivot('is_active', true)
            ->pluck('offerings.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
        $this->activeOfferingIds = $this->selectedOfferingIds;

        session()->flash('status', 'Servizi aggiornati con successo.');

        // Forza rerender (utile quando sotto ci sono componenti figli per ogni offering)
        $this->dispatch('$refresh');
    }

    /**
     * Quando l'utente spunta/despunta checkbox, Livewire aggiorna selectedOfferingIds.
     * Qui NON ci fidiamo del payload client-side: ricostruiamo la lista attiva
     * interrogando il DB, così renderizziamo schede solo per offerings realmente
     * attive e appartenenti al vendor.
     *
     * Nota: le schede contenuti devono riflettere lo stato salvato (pivot is_active),
     * quindi aggiorniamo activeOfferingIds leggendo dal DB.
     *
     * @param mixed $value
     */
    public function updatedSelectedOfferingIds($value): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        abort_unless($user->can('vendor.access'), 403);

        $vendorAccount = $user->vendorAccount;

        if (!$vendorAccount || $vendorAccount->trashed()) {
            abort(403);
        }

        // Filtra subito gli ID selezionati: solo quelli ammessi (categoria + attivi).
        $allowedIds = Offering::query()
            ->where('category_id', $vendorAccount->category_id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $safeSelectedIds = array_values(array_intersect(
            array_map('intval', (array) $this->selectedOfferingIds),
            $allowedIds
        ));

        // Aggiorna selectedOfferingIds con versione "safe" (evita valori spuri in UI)
        $this->selectedOfferingIds = $safeSelectedIds;

        /**
         * Scelta di UX:
         * - Le schede contenuti mostrano SOLO offerings già "attive" in pivot (stato salvato).
         * - Quindi activeOfferingIds resta agganciato al DB.
         *
         * Se invece vuoi far comparire le schede "prima del salvataggio",
         * nel prossimo step possiamo renderizzare in base a $safeSelectedIds.
         */
        $this->activeOfferingIds = $vendorAccount->offerings()
            ->wherePivot('is_active', true)
            ->pluck('offerings.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Rende la vista del componente, passando il titolo e le offerings disponibili.
     */
    public function render()
    {
        return view('livewire.vendor.offerings.manage-offerings', [
            'title' => 'Servizi',
        ]);
    }
}
