<?php

namespace App\Livewire\Vendor\Offerings;

use App\Models\Offering;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Gestione selezione servizi (Offerings) per il vendor.
 *
 * Responsabilità:
 * - Mostrare le offerings disponibili per la categoria del vendor
 * - Consentire selezione/deselezione
 * - Salvare sul pivot mantenendo la colonna is_active come "soft-sync"
 *
 * Nota: la gestione contenuti (titolo, descrizione, cover, gallery, ecc.)
 * verrà spostata in una tab/componente separato.
 */
class ManageOfferings extends Component
{
    use AuthorizesRequests;

    /**
     * Offerings disponibili per la categoria del vendor.
     *
     * @var \Illuminate\Support\Collection<int, \App\Models\Offering>
     */
    public Collection $availableOfferings;

    /**
     * IDs offerings selezionate (stato UI).
     *
     * @var array<int>
     */
    public array $selectedOfferingIds = [];

    /**
     * Inizializza la pagina: carica offerings disponibili e pre-seleziona quelle attive.
     */
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

        // Solo offerings della categoria del vendor
        $this->availableOfferings = Offering::query()
            ->where('category_id', $vendorAccount->category_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Pre-seleziona quelle già associate e attive
        $this->selectedOfferingIds = $vendorAccount->offerings()
            ->wherePivot('is_active', true)
            ->pluck('offerings.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
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

        // Selezione finale ripulita (evita ID fuori categoria / disattivi / manipolati)
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
                // Inseriamo la pivot solo se l’offering è selezionata (evita righe inutili)
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

        session()->flash('status', 'Servizi aggiornati con successo.');

        // Forza rerender (utile per riallineare UI in caso di input sporchi o race)
        $this->dispatch('$refresh');
    }

    /**
     * Quando l'utente spunta/despunta checkbox, Livewire aggiorna selectedOfferingIds.
     * Qui NON ci fidiamo del payload client-side: filtriamo subito agli ID ammessi.
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
    }

    /**
     * Rende la vista del componente.
     */
    public function render()
    {
        return view('livewire.vendor.offerings.manage-offerings', [
            'title' => 'Servizi',
        ]);
    }
}