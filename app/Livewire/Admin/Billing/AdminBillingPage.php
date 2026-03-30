<?php

namespace App\Livewire\Admin\Billing;

use App\Models\VendorAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class AdminBillingPage extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    public string $search = '';
    public string $paymentModel = 'ALL'; 

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        abort_unless($user->can('admin.access'), 403);
        $this->authorize('viewAny', VendorAccount::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentModel(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', VendorAccount::class);

        $query = VendorAccount::query()
            ->with([
                'user:id,email',
                'subscriptions.items'
            ])
            ->orderByDesc('id');

        if ($this->paymentModel === 'SUBSCRIPTION') {
            $query->whereHas('subscriptions', function ($q) {
                $q->whereIn('stripe_status', ['active', 'trialing']);
            });
        } elseif ($this->paymentModel === 'COMMISSION') {
            $query->whereDoesntHave('subscriptions', function ($q) {
                $q->whereIn('stripe_status', ['active', 'trialing']);
            });
        }

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('vat_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%");
                    });
            });
        }

        return view('livewire.admin.billing.admin-billing-page', [
            'title' => 'Gestione Abbonamenti',
            'vendors' => $query->paginate(15),
        ]);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->paymentModel = 'ALL';
        $this->resetPage();
    }
}
