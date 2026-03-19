<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotLock extends Model
{
    public const STATUS_HOLD = 'HOLD';
    public const STATUS_BOOKED = 'BOOKED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_EXPIRED = 'EXPIRED';

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
        'active_slot_key',
        'created_by_user_id',
        'booking_id',
        'booked_at',
        'cancelled_at',
        'expired_at',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'distance_km' => 'decimal:2',
        'guests' => 'integer',
        'quoted_amount' => 'decimal:2',
        'pricing_breakdown' => 'array',
        'expires_at' => 'datetime',
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $lock): void {
            if (! $lock->is_active) {
                $lock->active_slot_key = null;
            }

            if ($lock->is_active && $lock->isBooked()) {
                $lock->active_slot_key = self::makeActiveSlotKey(
                    (int) $lock->vendor_account_id,
                    (int) $lock->vendor_slot_id,
                    (string) $lock->date->format('Y-m-d')
                );
            }
        });
    }

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSlot(
        Builder $query,
        int $vendorAccountId,
        int $vendorSlotId,
        string $date
    ): Builder {
        return $query
            ->where('vendor_account_id', $vendorAccountId)
            ->where('vendor_slot_id', $vendorSlotId)
            ->where('date', $date);
    }

    public function isHold(): bool
    {
        return $this->status === self::STATUS_HOLD;
    }

    public function isBooked(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isActiveHold(): bool
    {
        return $this->isHold() && $this->is_active;
    }

    public function isExpiredHold(?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $this->isActiveHold()
            && $this->expires_at
            && $this->expires_at->lte($now);
    }

    public function isBlocking(?CarbonInterface $now = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isBooked()) {
            return true;
        }

        return $this->isHold() && ! $this->isExpiredHold($now);
    }

    public static function makeActiveSlotKey(int $vendorAccountId, int $vendorSlotId, string $date): string
    {
        return "{$vendorAccountId}:{$vendorSlotId}:{$date}";
    }

    public function markExpired(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->is_active = false;
        $this->expires_at = null;
        $this->expired_at = now();
        $this->save();
    }

    public function markCancelled(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->is_active = false;
        $this->expires_at = null;
        $this->cancelled_at = now();
        $this->save();
    }

    public function markBooked(int $bookingId): void
    {
        $this->status = self::STATUS_BOOKED;
        $this->is_active = true;
        $this->expires_at = null;
        $this->booking_id = $bookingId;
        $this->booked_at = now();
        $this->save();
    }
}