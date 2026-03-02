<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorLeadTime extends Model
{
    protected $fillable = [
        'vendor_account_id',
        'day_of_week',
        'min_notice_hours',
        'cutoff_time',
    ];

    protected $casts = [
        'day_of_week'      => 'integer',
        'min_notice_hours' => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────

    public function vendorAccount(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class);
    }

    // ── Helpers ────────────────────────────────────────────

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

    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }
}