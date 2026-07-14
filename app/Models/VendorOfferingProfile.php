<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorOfferingProfile extends Model
{
    // Modalità servizio
    public const SERVICE_MODE_MOBILE = 'MOBILE';
    public const SERVICE_MODE_FIXED_LOCATION = 'FIXED_LOCATION';

    protected static function booted()
    {
        static::saved(function ($profile) {
            // Sincronizza solo se il servizio è approvato e il vendor è attivo
            if ($profile->vendor_account_id && $profile->is_approved) {
                $vendor = \App\Models\VendorAccount::find($profile->vendor_account_id);
                if ($vendor && $vendor->status === 'ACTIVE') {
                    \App\Jobs\PushVendorToPrestashopJob::dispatch($vendor)->afterCommit();
                }
            }
        });

        static::deleted(function ($profile) {
            // Se un servizio viene eliminato, aggiorniamo per rimuoverlo (se il vendor è attivo)
            if ($profile->vendor_account_id) {
                $vendor = \App\Models\VendorAccount::find($profile->vendor_account_id);
                if ($vendor && $vendor->status === 'ACTIVE') {
                    \App\Jobs\PushVendorToPrestashopJob::dispatch($vendor)->afterCommit();
                }
            }
        });
    }

    protected $fillable = [
        'vendor_account_id',
        'offering_id',
        'title',
        'short_description',
        'description',
        'cover_image_path',
        'service_mode',
        'service_radius_km',
        'max_guests',
        'is_published',
        'is_approved',
    ];

    protected $casts = [
        'service_radius_km' => 'float',
        'max_guests' => 'integer',
        'is_published' => 'boolean',
        'is_approved' => 'boolean',
    ];

    // ─── Scope ─────────────────────────────────────────────

    public function scopeBookable(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('is_published', true)
            ->where('is_approved', true)
            ->whereHas('vendorAccount', function ($q) {
                $q->whereNull('deleted_at')
                  ->where('status', 'ACTIVE')
                  ->whereHas('category', function ($qc) {
                      $qc->where('is_active', true);
                  });
            })
            ->whereHas('offering', function ($q) {
                $q->where('is_active', true);
            })
            ->whereExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('vendor_offerings')
                  ->whereColumn('vendor_offerings.vendor_account_id', 'vendor_offering_profiles.vendor_account_id')
                  ->whereColumn('vendor_offerings.offering_id', 'vendor_offering_profiles.offering_id')
                  ->where('vendor_offerings.is_active', true);
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

    public function images(): HasMany
    {
        return $this->hasMany(VendorOfferingImage::class)->orderBy('sort_order');
    }

    // ─── Dominio servizio ──────────────────────────────────

    public function isMobileService(): bool
    {
        return $this->service_mode === self::SERVICE_MODE_MOBILE;
    }

    public function isFixedLocationService(): bool
    {
        return $this->service_mode === self::SERVICE_MODE_FIXED_LOCATION;
    }

    public function hasServiceRadius(): bool
    {
        return $this->isMobileService()
            && $this->service_radius_km !== null
            && $this->service_radius_km > 0;
    }

    // max_guests ha senso solo per FIXED_LOCATION
    public function hasMaxGuests(): bool
    {
        return $this->isFixedLocationService()
            && $this->max_guests !== null
            && $this->max_guests > 0;
    }

    // Regola positiva: il servizio supporta questo numero di ospiti.
    public function supportsGuests(?int $guests): bool
    {
        if ($guests === null || $guests <= 0) {
            return true;
        }

        if ($this->isMobileService()) {
            return true;
        }

        if (! $this->hasMaxGuests()) {
            return true;
        }

        return $guests <= (int) $this->max_guests;
    }

    // Regola negativa complementare a supportsGuests().
    public function exceedsCapacity(?int $guests): bool
    {
        return ! $this->supportsGuests($guests);
    }

    // URL pubblico dell'immagine copertina.
    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        return route('media.public', [
            'path' => ltrim($this->cover_image_path, '/'),
        ]);
    }
}