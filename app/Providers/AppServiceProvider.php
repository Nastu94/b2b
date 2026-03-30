<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Pricing\Contracts\PricingResolverInterface;
use App\Domain\Pricing\Resolvers\PricingResolver;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Cashier;
use App\Listeners\StripeEventListener;

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
    }
}
