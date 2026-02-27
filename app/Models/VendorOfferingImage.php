<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorOfferingImage extends Model
{
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