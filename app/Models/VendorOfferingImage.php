<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorOfferingImage extends Model
{
    protected static function booted()
    {
        static::saved(function ($image) {
            if ($image->vendor_offering_profile_id) {
                $profile = \App\Models\VendorOfferingProfile::with('vendorAccount')->find($image->vendor_offering_profile_id);
                if ($profile && $profile->vendorAccount) {
                    \App\Jobs\PushVendorToPrestashopJob::dispatch($profile->vendorAccount);
                }
            }
        });

        static::deleted(function ($image) {
            if ($image->vendor_offering_profile_id) {
                $profile = \App\Models\VendorOfferingProfile::with('vendorAccount')->find($image->vendor_offering_profile_id);
                if ($profile && $profile->vendorAccount) {
                    \App\Jobs\PushVendorToPrestashopJob::dispatch($profile->vendorAccount);
                }
            }
        });
    }

    protected $fillable = [
        'vendor_offering_profile_id',
        'path',
        'sort_order',
    ];

    public function profile()
    {
        return $this->belongsTo(VendorOfferingProfile::class, 'vendor_offering_profile_id');
    }
}