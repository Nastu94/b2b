<?php

namespace App\Livewire\Vendor\Billing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.vendor')]
class VendorBillingPage extends Component
{
    public string $currentModel = 'COMMISSION';
    public bool $isSubscribed = false;
    public ?string $activePlan = null;
    
    // Nuove proprietà per l'interfaccia a schede e stato Grace Period
    public bool $showPlans = false;
    public bool $onGracePeriod = false;
    public ?string $endsAt = null;
    
    public function mount()
    {
        $user = Auth::user();
        abort_unless($user && $user->can('vendor.access'), 403);
        
        $vendor = $user->vendorAccount;
        abort_unless($vendor && !$vendor->trashed(), 403);

        $this->currentModel = $vendor->payment_model;
        $this->isSubscribed = $vendor->subscribed('default');
        
        if (request()->query('upgrade')) {
            $this->showPlans = true;
        }
        
        if ($this->isSubscribed) {
            $subscription = $vendor->subscription('default');
            $monthlyPrice = config('services.stripe.price_monthly', env('STRIPE_PRICE_MONTHLY', ''));
            $yearlyPrice = config('services.stripe.price_yearly', env('STRIPE_PRICE_YEARLY', ''));

            if ($monthlyPrice && $subscription->hasPrice($monthlyPrice)) {
                $this->activePlan = 'MONTHLY';
            } elseif ($yearlyPrice && $subscription->hasPrice($yearlyPrice)) {
                $this->activePlan = 'YEARLY';
            }
            
            $this->onGracePeriod = $subscription->onGracePeriod();
            
            try {
                if ($this->onGracePeriod && $subscription->ends_at) {
                    $this->endsAt = $subscription->ends_at->format('d/m/Y');
                } else {
                    // Estrazione sicura del prossimo rinnovo calcolando la prossima fattura emettibile (Cashier)
                    $upcomingInvoice = $subscription->upcomingInvoice();
                    if ($upcomingInvoice) {
                        try {
                            $timestamp = $upcomingInvoice->period_end;
                            $this->endsAt = $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp)->format('d/m/Y') : null;
                        } catch (\Exception $e) {
                            $this->endsAt = null;
                        }
                    } else {
                        $this->endsAt = null;
                    }
                }
            } catch (\Exception $e) {
                $this->endsAt = null; // Il frontend mostrerà N/D in caso di errore di connessione API
            }
        }
    }

    public function togglePlans()
    {
        $this->showPlans = !$this->showPlans;
    }

    public function subscribeToPlan(string $planType)
    {
        $vendor = Auth::user()->vendorAccount;
        
        $priceId = $planType === 'YEARLY' 
            ? config('services.stripe.price_yearly') 
            : config('services.stripe.price_monthly');
        
        if (!$priceId) {
            session()->flash('error', 'Configurazione prezzi mancante. Contattare l\'amministratore.');
            return;
        }

        // Se è già abbonato, cambiamo piano
        if ($vendor->subscribed('default')) {
            $vendor->subscription('default')->swap($priceId);
            $this->isSubscribed = true;
            $this->activePlan = $planType;
            session()->flash('success', 'Piano aggiornato correttamente.');
            return redirect()->route('vendor.billing');
        }

        // Checkout per nuova sottoscrizione tramite Livewire Redirect bypassando il Responsable di Cashier
        $checkout = $vendor->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('vendor.billing') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('vendor.billing'),
            ]);

        return redirect($checkout->url);
    }

    public function cancelSubscription()
    {
        $vendor = Auth::user()->vendorAccount;
        if ($vendor->subscribed('default')) {
            $vendor->subscription('default')->cancel();
            
            // NON modifichiamo payment_model a COMMISSION qui.
            // Il vendor beneficerà dell'abbonamento zero-commissioni fino alla scadenza del mese pagato.
            // L'aggiornamento a COMMISSION verrà fatto dal Webhook quando Stripe invia 'customer.subscription.deleted'
            
            $this->onGracePeriod = true;
            $subscription = $vendor->subscription('default');
            $this->endsAt = $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : null;
            
            session()->flash('success', 'Rinnovo automatico annullato! Manterrai i vantaggi Premium fino alla naturale scadenza del periodo pagato.');
        }
    }

    public function render()
    {
        return view('livewire.vendor.billing.vendor-billing-page', [
            'title' => 'Gestione Abbonamento'
        ]);
    }
}
