<?php

namespace App\Providers;

use App\Models\VendorOfferingProfile;
use App\Models\VendorOfferingImage;
use App\Models\VendorAccount;
use App\Policies\VendorAccountPolicy;
use App\Policies\VendorOfferingProfilePolicy;
use App\Policies\VendorOfferingImagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        VendorOfferingProfile::class => VendorOfferingProfilePolicy::class,
        VendorOfferingImage::class   => VendorOfferingImagePolicy::class,
        VendorAccount::class => VendorAccountPolicy::class,
    ];
}