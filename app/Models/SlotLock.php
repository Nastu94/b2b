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
        'offering_id',
        'date',
        'distance_km',
        'guests',
        'quoted_amount',
        'currency',
        'pricing_breakdown',
        'status',
        'hold_token',
        'expires_at',
        'is_active',
        'created_by_user_id',
        'booking_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'distance_km' => 'decimal:2',
        'guests' => 'integer',
        'quoted_amount' => 'decimal:2',
        'pricing_breakdown' => 'array',
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

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}