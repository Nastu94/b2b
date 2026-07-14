<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Pricing\Contracts\PricingResolverInterface;
use App\Domain\Pricing\Resolvers\PricingResolver;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Cashier;
use App\Listeners\StripeEventListener;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PricingResolverInterface::class, PricingResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(\App\Models\VendorAccount::class);

        Event::listen(
            WebhookHandled::class,
            StripeEventListener::class,
        );

        RateLimiter::for('api-read', function (Request $request) {
            $customer = $request->input('prestashop_customer_id');
            $key = $customer ? "api-read-customer-{$customer}" : $request->ip();
            return Limit::perMinute(120)->by($key);
        });

        RateLimiter::for('api-write', function (Request $request) {
            $customer = $request->input('prestashop_customer_id');
            $slot = $request->input('vendor_slot_id');
            $key = $customer ? "api-write-customer-{$customer}-slot-{$slot}" : $request->ip();
            return Limit::perMinute(60)->by($key);
        });
    }
}
