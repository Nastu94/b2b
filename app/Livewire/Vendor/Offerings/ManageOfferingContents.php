<?php

namespace App\Livewire\Vendor\Offerings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tab "Contenuti" per i servizi selezionati dal vendor.
 *
 * Responsabilità:
 * - Leggere dal DB le offerings attive (pivot is_active = true)
 * - Renderizzare le schede contenuti per ogni offering attiva
 *
 * Nota: la selezione dei servizi resta nel componente ManageOfferings.
 * Questo componente si fida solo dello stato "source of truth" nel DB.
 */
class ManageOfferingContents extends Component
{
    use AuthorizesRequests;

    /**
     * IDs offerings attive (source of truth dal pivot).
     *
     * @var array<int, int>
     */
    public array $activeOfferingIds = [];

    /**
     * Mount: gating vendor + caricamento offerings attive.
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

        // Difesa in profondità: account vendor richiesto e non soft-deleted
        if (!$vendorAccount || $vendorAccount->trashed()) {
            abort(403);
        }

        // Stato "source of truth": offerings attive nel pivot
        $this->activeOfferingIds = $vendorAccount->offerings()
            ->wherePivot('is_active', true)
            ->pluck('offerings.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Render della tab Contenuti.
     */
    public function render()
    {
        return view('livewire.vendor.offerings.manage-offering-contents', [
            'title' => 'Contenuti servizi',
        ]);
    }
}