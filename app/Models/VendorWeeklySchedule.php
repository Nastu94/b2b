<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\VendorSlot;

class VendorWeeklySchedule extends Model
{
    protected $fillable = [
        'vendor_account_id',
        'vendor_slot_id',
        'day_of_week',
        'is_open',
        'min_notice_hours',
        'cutoff_time',
    ];

    protected $casts = [
        'is_open'          => 'boolean',
        'day_of_week'      => 'integer',
        'min_notice_hours' => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────

    public function vendorAccount(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(VendorSlot::class, 'vendor_slot_id');
    }

    // ── Helpers ────────────────────────────────────────────

    /**
     * Restituisce il nome del giorno in italiano.
     */
    public function dayName(): string
    {
        return match($this->day_of_week) {
            0 => 'Domenica',
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            default => '?',
        };
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }
}