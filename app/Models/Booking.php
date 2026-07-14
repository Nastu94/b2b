<?php

namespace App\Models;

use App\Jobs\SendVendorNewBookingEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    // Stati booking
    public const STATUS_PENDING_VENDOR_CONFIRMATION = 'PENDING_VENDOR_CONFIRMATION';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_DECLINED = 'DECLINED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_REFUNDED = 'REFUNDED';
    public const STATUS_EXPIRED = 'EXPIRED';

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
        'is_commission_based',
        'commission_rate',
        'commission_amount',
        'vendor_payment_status',
        'vendor_transaction_id',
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

    protected static function booted(): void
    {
        static::created(function (Booking $booking): void {
            SendVendorNewBookingEmail::dispatch($booking->id)->afterCommit();
        });
    }

    // ─── Relazioni ─────────────────────────────────────────

    public function vendorAccount(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function slotLock(): BelongsTo
    {
        return $this->belongsTo(SlotLock::class);
    }

    public function vendorSlot(): BelongsTo
    {
        return $this->belongsTo(VendorSlot::class);
    }

    public function conversationThreads()
    {
        return $this->hasMany(ConversationThread::class);
    }

    // ─── Stato ─────────────────────────────────────────────

    public function isPendingVendorConfirmation(): bool
    {
        return $this->status === self::STATUS_PENDING_VENDOR_CONFIRMATION;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function markConfirmed(?string $vendorNotes = null): void
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = now();

        if ($vendorNotes !== null) {
            $this->vendor_notes = $vendorNotes;
        }

        $this->save();
    }

    public function markDeclined(?string $reason = null, ?string $vendorNotes = null): void
    {
        $this->status = self::STATUS_DECLINED;
        $this->declined_at = now();

        if ($reason !== null) {
            $this->decline_reason = $reason;
        }

        if ($vendorNotes !== null) {
            $this->vendor_notes = $vendorNotes;
        }

        $this->save();
    }

    public function markCancelled(?string $vendorNotes = null): void
    {
        $this->status = self::STATUS_CANCELLED;

        if ($vendorNotes !== null) {
            $this->vendor_notes = $vendorNotes;
        }

        $this->save();
    }

    public function markRefunded(?string $vendorNotes = null): void
    {
        $this->status = self::STATUS_REFUNDED;

        if ($vendorNotes !== null) {
            $this->vendor_notes = $vendorNotes;
        }

        $this->save();
    }

    public function markExpired(?string $vendorNotes = null): void
    {
        $this->status = self::STATUS_EXPIRED;

        if ($vendorNotes !== null) {
            $this->vendor_notes = $vendorNotes;
        }

        $this->save();
    }

    // ─── Scope ─────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_VENDOR_CONFIRMATION);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('event_date', '>=', now()->toDateString());
    }


}