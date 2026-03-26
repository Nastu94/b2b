<?php

namespace App\Livewire\Admin\Vendors;

use App\Models\VendorAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class VendorProfileTabs extends Component
{
    use AuthorizesRequests;

    public VendorAccount $vendorAccount;

    public string $activeTab = 'anagrafica';

    public bool $editing = false;
    public bool $confirmingDelete = false;

    private const TABS = ['anagrafica', 'servizi'];

    public function mount(VendorAccount $vendorAccount): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        abort_unless($user->can('admin.access'), 403);

        $this->authorize('view', $vendorAccount);

        $this->vendorAccount = $vendorAccount->load(['user', 'category', 'offerings']);

        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'anagrafica';
        }
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, self::TABS, true)) {
            return;
        }

        // Replica comportamento attuale: se esci da anagrafica mentre editi -> annulla
        if ($this->editing && $this->activeTab === 'anagrafica' && $tab !== 'anagrafica') {
            $this->cancelEditing();
        }

        $this->activeTab = $tab;
    }

    public function enableEditing(): void
    {
        $this->authorize('update', $this->vendorAccount);

        $this->editing = true;

        // Notifica la tab anagrafica (se è montata)
        $this->dispatch('vendor-anagrafica-enter-edit', vendorAccountId: $this->vendorAccount->id);
    }

    public function cancelEditing(): void
    {
        $this->editing = false;

        // Chiede alla tab di ripristinare snapshot + reset errori
        $this->dispatch('vendor-anagrafica-cancel-edit', vendorAccountId: $this->vendorAccount->id);
    }

    public function approveVendor(): void
    {
        $this->authorize('update', $this->vendorAccount);

        $this->vendorAccount->update([
            'status' => 'ACTIVE',
            'activated_at' => now(),
        ]);

        if ($this->vendorAccount->user && $this->vendorAccount->user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($this->vendorAccount->user->email)
                    ->send(new \App\Mail\VendorAccountApprovedMail($this->vendorAccount));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Impossibile inviare email Vendor: ' . $e->getMessage());
            }
        }

        session()->flash('status', 'Fornitore approvato con successo. Email inviata. La sincronizzazione su catalogo è in coda.');
        $this->vendorAccount->refresh();
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->vendorAccount);
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function deleteVendor()
    {
        $this->authorize('delete', $this->vendorAccount);

        $this->vendorAccount->delete(); // soft delete
        return redirect()->route('admin.dashboard');
    }

    #[On('vendor-anagrafica-saved')]
    public function onAnagraficaSaved(int $vendorAccountId): void
    {
        if ($vendorAccountId !== $this->vendorAccount->id) {
            return;
        }

        // Spegne editing e ricarica i dati per header/servizi
        $this->editing = false;
        $this->vendorAccount->refresh()->load(['user', 'category', 'offerings']);
    }

    public function render()
    {
        $this->authorize('view', $this->vendorAccount);

        return view('livewire.admin.vendors.vendor-profile-tabs');
    }
}