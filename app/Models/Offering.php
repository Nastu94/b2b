<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offering extends Model
{
    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'is_active',
        'sort_order',
        'created_by_vendor_account_id',
        'status',
        'is_custom',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(
            VendorAccount::class,
            'vendor_offerings'
        )->withPivot('is_active')->withTimestamps();
    }

    public function vendorProfiles()
    {
        return $this->hasMany(VendorOfferingProfile::class);
    }

    /**
     * Relazione con i listini base associati al servizio.
     */
    public function pricings()
    {
        return $this->hasMany(VendorOfferingPricing::class);
    }

    public function createdByVendor(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class, 'created_by_vendor_account_id');
    }
}
