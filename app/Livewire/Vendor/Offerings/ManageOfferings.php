<?php

namespace App\Livewire\Vendor\Offerings;

use App\Models\Offering;
use Illuminate\Support\Collection;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.vendor')]
class ManageOfferings extends Component
{
    /** @var \Illuminate\Support\Collection<int, \App\Models\Offering> */
    public Collection $availableOfferings;

    /** @var array<int> IDs offerings selezionate */
    public array $selectedOfferingIds = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $vendorAccount = $user->vendorAccount;

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
    }

   public function save(): void
{
    $user = Auth::user();

    if (!$user) {
        abort(403);
    }

    $vendorAccount = $user->vendorAccount;

    // sicurezza: accetta solo offering della sua categoria
    $allowedIds = $this->availableOfferings
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

    session()->flash('status', 'Servizi aggiornati con successo.');

    // Forza rerender (utile quando sotto ci sono componenti figli per ogni offering)
    $this->dispatch('$refresh');
}

    public function render()
    {
        return view('livewire.vendor.offerings.manage-offerings', [
            'title' => 'Servizi',
        ]);
    }
}
