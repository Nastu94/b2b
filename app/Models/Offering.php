<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offering extends Model
{
    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
