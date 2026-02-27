<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorOfferingProfile extends Model
{
    protected $fillable = [
        'vendor_account_id',
        'offering_id',
        'title',
        'short_description',
        'description',
        'cover_image_path',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function vendorAccount()
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function images()
    {
        return $this->hasMany(VendorOfferingImage::class)->orderBy('sort_order');
    }
}