<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorAccount;

#[Layout('layouts.admin')]
class AdminDashboardPage extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    // Variabili per filtri e ricerca
    public string $search = '';
    public string $status = 'ALL'; // ALL | ACTIVE | INACTIVE
    public bool $confirmingDelete = false;
    public ?int $vendorToDeleteId = null;

    /**
     * Gating iniziale (difesa in profondità).
     */
    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        // Accesso area admin (permesso esistente)
        abort_unless($user->can('admin.access'), 403);

        // Policy: listing vendor
        $this->authorize('viewAny', VendorAccount::class);
    }

    /**
     * Resetta la paginazione quando cambiano i filtri o la ricerca
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Resetta la paginazione quando cambia il filtro di stato
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    // Funzioni per conferma e cancellazione vendor
    public function confirmDelete(int $vendorAccountId): void
    {
        $vendor = VendorAccount::query()->findOrFail($vendorAccountId);

        // Policy: delete vendor (soft delete)
        $this->authorize('delete', $vendor);

        $this->vendorToDeleteId = $vendorAccountId;
        $this->confirmingDelete = true;
    }

    /**
     * Se l'admin cancella, facciamo soft delete. 
     */
    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
        $this->vendorToDeleteId = null;
    }

    // Soft delete del vendor
    public function deleteVendor(): void
    {
        abort_unless($this->vendorToDeleteId, 400);

        $vendor = VendorAccount::query()->findOrFail($this->vendorToDeleteId);

        // Policy: delete vendor (soft delete)
        $this->authorize('delete', $vendor);

        $vendor->delete(); // soft delete

        $this->cancelDelete();
        session()->flash('status', 'Vendor eliminato (soft delete).');
    }

    // Render con filtri e ricerca
    public function render()
    {
        // Policy: listing vendor
        $this->authorize('viewAny', VendorAccount::class);

        $query = VendorAccount::query()
            ->with(['user:id,email', 'category:id,name'])
            ->orderByDesc('id');

        if ($this->status !== 'ALL') {
            $query->where('status', $this->status);
        }

        // Ricerca su più campi, incluso email dell'utente associato
        $s = trim($this->search);
        if ($s !== '') {
            $query->where(function ($q) use ($s) {
                $q->where('company_name', 'like', "%{$s}%")
                    ->orWhere('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('vat_number', 'like', "%{$s}%")
                    ->orWhere('tax_code', 'like', "%{$s}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('email', 'like', "%{$s}%"));
            });
        }

        return view('livewire.admin.dashboard.admin-dashboard-page', [
            'title' => 'Dashboard',
            'vendors' => $query->paginate(15),
        ]);
    }
}