<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookHandled;
use App\Models\VendorAccount;
use Illuminate\Support\Facades\Log;

class StripeEventListener
{
    /**
     * Handle the event.
     */
    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'];

        if (in_array($type, ['customer.subscription.deleted', 'customer.subscription.paused'])) {
            $stripeId = $payload['data']['object']['customer'];
            $vendor = VendorAccount::where('stripe_id', $stripeId)->first();
            
            if ($vendor && $vendor->payment_model !== 'COMMISSION') {
                $vendor->update(['payment_model' => 'COMMISSION']);
                Log::info("VendorAccount {$vendor->id} retrocesso a COMMISSION a causa del webhook {$type}");
            }
        }

        if (in_array($type, ['customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.resumed', 'checkout.session.completed'])) {
            $stripeId = $payload['data']['object']['customer'] ?? null;
            
            Log::info("[StripeEventListener] Ricevuto {$type} per stripe_id: " . ($stripeId ?: 'NULL'));

            if ($stripeId) {
                $vendor = VendorAccount::where('stripe_id', $stripeId)->first();
                if ($vendor) {
                    Log::info("[StripeEventListener] Trovato Vendor #{$vendor->id}. Model: {$vendor->payment_model}");
                    if ($vendor->payment_model !== 'SUBSCRIPTION') {
                        $isSubscribed = $vendor->subscribed('default');
                        $isValid = $isSubscribed ? $vendor->subscription('default')->valid() : false;
                        Log::info("[StripeEventListener] Subscribed: " . ($isSubscribed ? 'SI' : 'NO') . " | Valid: " . ($isValid ? 'SI' : 'NO'));
                        
                        if ($isSubscribed && $isValid) {
                            $vendor->update(['payment_model' => 'SUBSCRIPTION']);
                            Log::info("VendorAccount {$vendor->id} promosso a SUBSCRIPTION a causa del webhook {$type}");
                        }
                    }
                } else {
                    Log::info("[StripeEventListener] Vendor NON trovato per stripe_id: {$stripeId}");
                }
            }
        }
    }
}
