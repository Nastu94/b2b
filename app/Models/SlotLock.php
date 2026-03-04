<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotLock extends Model
{
    protected $table = 'slot_locks';

    protected $fillable = [
        'vendor_account_id',
        'vendor_slot_id',
        'date',
        'status',
        'hold_token',
        'expires_at',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function vendorAccount(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function vendorSlot(): BelongsTo
    {
        return $this->belongsTo(VendorSlot::class);
    }
}