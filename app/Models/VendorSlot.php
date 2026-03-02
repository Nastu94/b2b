<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\VendorWeeklySchedule;


class VendorSlot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_account_id',
        'slug',
        'label',
        'start_time',
        'end_time',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────

    public function vendorAccount(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(VendorWeeklySchedule::class);
    }

    // ── Helpers ────────────────────────────────────────────

    /**
     * Restituisce il label orario leggibile.
     * Es. "09:00 - 13:00" oppure "Intera giornata"
     */
    public function timeLabel(): string
    {
        if ($this->start_time && $this->end_time) {
            return substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5);
        }
        return 'Intera giornata';
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}