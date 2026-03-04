<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorBlackout extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_account_id',
        'date_from',
        'date_to',
        'vendor_slot_id',
        'reason_internal',
        'reason_public',
        'created_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ────────────────────────────────────────────

    /**
     * È un blackout su giorno singolo?
     */
    public function isSingleDay(): bool
    {
        return $this->date_from->equalTo($this->date_to);
    }

    /**
     * Blocca tutti gli slot o solo uno specifico?
     */
    public function isFullDay(): bool
    {
        return is_null($this->vendor_slot_id);
    }

    /**
     * Label leggibile per la UI
     */
    public function rangeLabel(): string
    {
        if ($this->isSingleDay()) {
            return $this->date_from->format('d/m/Y');
        }
        return $this->date_from->format('d/m/Y') . ' → ' . $this->date_to->format('d/m/Y');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('date_to', '>=', now()->toDateString());
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('date_from', '<=', $date)
                     ->where('date_to', '>=', $date);
    }
}