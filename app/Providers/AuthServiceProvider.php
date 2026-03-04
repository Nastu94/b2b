<?php

namespace App\Providers;

use App\Models\VendorOfferingProfile;
use App\Models\VendorOfferingImage;
use App\Models\VendorAccount;
use App\Models\VendorLeadTime;
use App\Policies\VendorAccountPolicy;
use App\Policies\VendorOfferingProfilePolicy;
use App\Policies\VendorOfferingImagePolicy;
use App\Policies\VendorWeeklySchedulePolicy;
use App\Policies\VendorSlotPolicy;
use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use App\Models\VendorBlackout;
use App\Policies\VendorLeadTimePolicy;
use App\Policies\VendorBlackoutPolicy;


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
        VendorSlot::class => VendorSlotPolicy::class,
        VendorWeeklySchedule::class => VendorWeeklySchedulePolicy::class,
        VendorLeadTime::class => VendorLeadTimePolicy::class,
        VendorBlackout::class => VendorBlackoutPolicy::class,
    ];
}
