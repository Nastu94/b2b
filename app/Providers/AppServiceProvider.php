<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Pricing\Contracts\PricingResolverInterface;
use App\Domain\Pricing\Resolvers\PricingResolver;

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
        //
    }
}
