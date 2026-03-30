<?php

namespace App\Livewire\Admin\Vendors\Tabs;

use App\Models\VendorAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Exception;
use Illuminate\Support\Facades\Log;

class VendorBillingTab extends Component
{
    use AuthorizesRequests;

    public VendorAccount $vendorAccount;

    public bool $editingCommission = false;
    public ?int $newCommissionRate = null;

    public function editCommission()
    {
        $this->authorize('update', $this->vendorAccount);
        $this->newCommissionRate = $this->vendorAccount->custom_commission_rate;
        $this->editingCommission = true;
    }

    public function cancelCommission()
    {
        $this->editingCommission = false;
    }

    public function saveCommission()
    {
        $this->authorize('update', $this->vendorAccount);
        
        $this->validate([
            'newCommissionRate' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $this->vendorAccount->update([
            'custom_commission_rate' => $this->newCommissionRate
        ]);

        $this->editingCommission = false;
        session()->flash('billing_success', 'Commissione personalizzata aggiornata con successo! Effettivo dalla prossima prenotazione.');
    }

    public function mount(VendorAccount $vendorAccount): void
    {
        $this->authorize('view', $vendorAccount);
        $this->vendorAccount = $vendorAccount;
    }

    public function cancelSubscription(): void
    {
        $this->authorize('update', $this->vendorAccount);

        try {
            if ($this->vendorAccount->subscribed('default')) {
                $this->vendorAccount->subscription('default')->cancel();
                session()->flash('billing_success', 'Rinnovo automatico disdetto! Il vendor riceverà i benefici Premium fino alla fine del periodo di grazia (Grace Period).');
            }
        } catch (Exception $e) {
            Log::error('Errore disdetta admin: ' . $e->getMessage());
            session()->flash('billing_error', 'Impossibile disdire: ' . $e->getMessage());
        }
    }

    public function revokeSubscriptionNow(): void
    {
        $this->authorize('update', $this->vendorAccount);

        try {
            if ($this->vendorAccount->subscribed('default')) {
                $this->vendorAccount->subscription('default')->cancelNow();
            }
            
            $this->vendorAccount->update([
                'payment_model' => 'COMMISSION'
            ]);

            session()->flash('billing_success', 'Abbonamento revocato con effetto istantaneo. Il vendor è tornato a far parte del piano a Commissioni.');
        } catch (Exception $e) {
            Log::error('Errore revoca admin: ' . $e->getMessage());
            session()->flash('billing_error', 'Impossibile revocare: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $invoices = [];
        $isSubscribed = $this->vendorAccount->subscribed('default');
        $paymentMethod = null;

        if ($this->vendorAccount->hasDefaultPaymentMethod()) {
            $paymentMethod = $this->vendorAccount->defaultPaymentMethod();
        }

        try {
            if ($this->vendorAccount->hasStripeId()) {
                // Recupero lista fatture Stripe
                $invoices = $this->vendorAccount->invoices();
            }
        } catch (Exception $e) {
            // Se le API di Stripe non riescono a recuperare le fatture, si logga e si ignora
            Log::error('Impossibile recuperare le fatture: ' . $e->getMessage());
        }

        return view('livewire.admin.vendors.tabs.vendor-billing-tab', [
            'isSubscribed' => $isSubscribed,
            'subscription' => $isSubscribed ? $this->vendorAccount->subscription('default') : null,
            'paymentMethod' => $paymentMethod,
            'invoices' => $invoices,
        ]);
    }
}
