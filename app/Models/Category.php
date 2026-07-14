<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'is_active',
        'sort_order',
        'prestashop_category_id',
        'commission_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'commission_rate' => 'float',
    ];

    public function vendorAccounts(): HasMany
    {
        return $this->hasMany(VendorAccount::class);
    }

    public function eventTypes(): HasMany
    {
        return $this->hasMany(EventType::class);
    }
}
