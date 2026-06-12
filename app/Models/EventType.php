<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EventType extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'is_homepage_visible',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_homepage_visible' => 'boolean',
    ];

    public function vendorAccounts(): BelongsToMany
    {
        return $this->belongsToMany(VendorAccount::class, 'event_type_vendor_account');
    }
}
