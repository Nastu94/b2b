<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_account_id',
        'offering_id',
        'slot_lock_id',
        'prestashop_order_id',
        'prestashop_order_line_id',
        'event_date',
        'vendor_slot_id',
        'distance_km',
        'guests',
        'customer_data',
        'status',
        'total_amount',
        'currency',
        'pricing_breakdown',
        'paid_at',
        'confirmed_at',
        'declined_at',
        'vendor_notes',
        'decline_reason',
    ];

    protected $casts = [
        'event_date' => 'date',
        'distance_km' => 'decimal:2',
        'guests' => 'integer',
        'customer_data' => 'array',
        'total_amount' => 'decimal:2',
        'pricing_breakdown' => 'array',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    public function vendorAccount()
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function slotLock()
    {
        return $this->belongsTo(SlotLock::class);
    }

    public function vendorSlot()
    {
        return $this->belongsTo(VendorSlot::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING_VENDOR_CONFIRMATION');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'CONFIRMED');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }
}