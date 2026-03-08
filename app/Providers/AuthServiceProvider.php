<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\VendorOfferingProfile;
use App\Models\VendorOfferingImage;
use App\Models\VendorAccount;
use App\Models\VendorLeadTime;
use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use App\Models\VendorBlackout;
use App\Models\VendorOfferingPricing;
use App\Models\VendorOfferingPricingRule;
use App\Policies\VendorAccountPolicy;
use App\Policies\VendorOfferingProfilePolicy;
use App\Policies\VendorOfferingImagePolicy;
use App\Policies\VendorWeeklySchedulePolicy;
use App\Policies\VendorSlotPolicy;
use App\Policies\VendorLeadTimePolicy;
use App\Policies\VendorBlackoutPolicy;
use App\Policies\BookingPolicy;
use App\Policies\VendorOfferingPricingPolicy;
use App\Policies\VendorOfferingPricingRulePolicy;



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
        Booking::class => BookingPolicy::class,
        VendorOfferingPricing::class => VendorOfferingPricingPolicy::class,
        VendorOfferingPricingRule::class => VendorOfferingPricingRulePolicy::class,
    ];
}
