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
        'service_mode',
        'service_radius_km',
        'is_published',
    ];

    protected $casts = [
        'service_radius_km' => 'float',
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

    public function isMobileService(): bool
    {
        return $this->service_mode === 'MOBILE';
    }

    public function hasServiceRadius(): bool
    {
        return $this->service_radius_km !== null && $this->service_radius_km > 0;
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image_path) {
            return null;
        }

        return route('media.public', [
            'path' => ltrim($this->cover_image_path, '/'),
        ]);
    }
}