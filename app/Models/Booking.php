<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    /**
     * Campi mass-assignable
     */
    protected $fillable = [
        'vendor_account_id',
        'slot_lock_id',
        'prestashop_order_id',
        'event_date',
        'vendor_slot_id',
        'customer_data',
        'status',
        'total_amount',
        'paid_at',
        'confirmed_at',
        'declined_at',
        'vendor_notes',
        'decline_reason',
    ];

    /**
     * Cast automatici per tipi specifici
     */
    protected $casts = [
        'event_date' => 'date',
        'customer_data' => 'array',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    /**
     * Relazione: booking appartiene a un vendor
     */
    public function vendorAccount()
    {
        return $this->belongsTo(VendorAccount::class);
    }

    /**
     * Relazione: booking collegato a slot lock
     */
    public function slotLock()
    {
        return $this->belongsTo(SlotLock::class);
    }

    /**
     * Relazione: booking occupa uno slot specifico
     */
    public function vendorSlot()
    {
        return $this->belongsTo(VendorSlot::class);
    }

    /**
     * Scope: solo booking in attesa di conferma
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING_VENDOR_CONFIRMATION');
    }

    /**
     * Scope: solo booking confermati
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'CONFIRMED');
    }

    /**
     * Scope: solo booking futuri
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }
}