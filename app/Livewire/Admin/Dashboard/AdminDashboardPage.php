<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Category;
use App\Models\VendorAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class AdminDashboardPage extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    // Filtri dashboard
    public string $search = '';
    public string $status = 'ALL';
    public string $categoryId = '';
    public string $serviceMode = '';

    public bool $confirmingDelete = false;
    public ?int $vendorToDeleteId = null;

    public array $categories = [];

    // Gating iniziale.
    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        abort_unless($user->can('admin.access'), 403);

        $this->authorize('viewAny', VendorAccount::class);

        $this->categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }

    // Resetta la paginazione quando cambia la ricerca.
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Resetta la paginazione quando cambia lo status.
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    // Resetta la paginazione quando cambia la categoria.
    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    // Resetta la paginazione quando cambia la modalità di servizio.
    public function updatedServiceMode(): void
    {
        $this->resetPage();
    }

    // Apre la conferma di cancellazione.
    public function confirmDelete(int $vendorAccountId): void
    {
        $vendor = VendorAccount::query()->findOrFail($vendorAccountId);

        $this->authorize('delete', $vendor);

        $this->vendorToDeleteId = $vendorAccountId;
        $this->confirmingDelete = true;
    }

    // Chiude la conferma di cancellazione.
    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
        $this->vendorToDeleteId = null;
    }

    // Esegue soft delete del vendor.
    public function deleteVendor(): void
    {
        abort_unless($this->vendorToDeleteId, 400);

        $vendor = VendorAccount::query()->findOrFail($this->vendorToDeleteId);

        $this->authorize('delete', $vendor);

        $vendor->delete();

        $this->cancelDelete();

        session()->flash('status', 'Vendor eliminato (soft delete).');
    }

    // Render dashboard con filtri e ricerca.
    public function render()
    {
        $this->authorize('viewAny', VendorAccount::class);

        $query = VendorAccount::query()
            ->with([
                'user:id,email',
                'category:id,name',
                'vendorOfferingProfiles:id,vendor_account_id,service_mode',
            ])
            ->orderByDesc('id');

        if ($this->status !== 'ALL') {
            $query->where('status', $this->status);
        }

        if ($this->categoryId !== '') {
            $query->where('category_id', (int) $this->categoryId);
        }

        if ($this->serviceMode !== '') {
            $query->whereHas('vendorOfferingProfiles', function ($query) {
                $query->where('service_mode', $this->serviceMode);
            });
        }

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('vat_number', 'like', "%{$search}%")
                    ->orWhere('tax_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%");
                    });
            });
        }

        return view('livewire.admin.dashboard.admin-dashboard-page', [
            'title' => 'Dashboard',
            'vendors' => $query->paginate(15),
            'categories' => $this->categories,
        ]);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = 'ALL';
        $this->categoryId = '';
        $this->serviceMode = '';

        $this->resetPage();
    }
}