<?php

namespace App\Livewire\Vendor\Offerings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Container Livewire per la gestione "a tab" del pannello Servizi del vendor.
 *
 * Responsabilità:
 * - Autorizzazione e gating vendor
 * - Gestione stato tab attiva (UI)
 * - Rendering dei componenti figli per singola sezione
 *
 * Nota: ogni tab dovrebbe avere un proprio componente root dedicato
 * per evitare file monolitici e re-render pesanti.
 */
#[Layout('layouts.vendor')]
class ManageOfferingsTabs extends Component
{
    use AuthorizesRequests;

    /**
     * Identificatore tab attiva.
     *
     * @var string
     */
    public string $activeTab = 'offerings';

    /**
     * Mount: applica difese in profondità come nel componente esistente.
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
    }

    /**
     * Imposta la tab attiva.
     *
     * @param string $tab
     */
    public function setTab(string $tab): void
    {
        // Whitelist semplice per evitare tab “inventate” via client.
        $allowed = ['offerings', 'content', 'availability', 'blackouts', 'leadtime'];

        if (!in_array($tab, $allowed, true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /**
     * Render della vista container.
     */
    public function render()
    {
        return view('livewire.vendor.offerings.manage-offerings-tabs', [
            'title' => 'Gestione servizi',
        ]);
    }
}