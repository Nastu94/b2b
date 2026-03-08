<?php

namespace App\Models;

use App\Domain\Pricing\Enums\PricingDistanceMode;
use App\Domain\Pricing\Enums\PricingPriceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model del listino base vendor + servizio.
 *
 * Questa entità rappresenta la base commerciale del pricing engine.
 * Le regole di maggiorazione/sconto/override verranno collegate
 * e risolte a livello applicativo.
 */
class VendorOfferingPricing extends Model
{
    use SoftDeletes;

    /**
     * Attributi mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vendor_account_id',
        'offering_id',
        'is_active',
        'price_type',
        'base_price',
        'currency',
        'service_radius_km',
        'distance_pricing_mode',
        'notes_internal',
    ];

    /**
     * Cast automatici.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'price_type' => PricingPriceType::class,
        'base_price' => 'decimal:2',
        'service_radius_km' => 'decimal:2',
        'distance_pricing_mode' => PricingDistanceMode::class,
    ];

    /**
     * Relazione: il listino appartiene a un vendor.
     */
    public function vendorAccount(): BelongsTo
    {
        return $this->belongsTo(VendorAccount::class);
    }

    /**
     * Relazione: il listino appartiene a un offering.
     */
    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    /**
     * Relazione con le regole di pricing del listino.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(VendorOfferingPricingRule::class)
            ->orderBy('priority');
    }

    /**
     * Scope: solo listini attivi.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: listini del vendor specificato.
     */
    public function scopeForVendor($query, int $vendorAccountId)
    {
        return $query->where('vendor_account_id', $vendorAccountId);
    }

    /**
     * Scope: listino per specifico servizio.
     */
    public function scopeForOffering($query, int $offeringId)
    {
        return $query->where('offering_id', $offeringId);
    }

    /**
     * Scope: query owner-scoped sul vendor autenticato.
     */
    public function scopeOwnedByUser($query, User $user)
    {
        return $query->where('vendor_account_id', $user->vendorAccount?->id);
    }

    /**
     * Indica se il listino è gratuito.
     */
    public function isFree(): bool
    {
        return $this->price_type === PricingPriceType::FREE;
    }

    /**
     * Indica se il listino usa prezzo fisso.
     */
    public function isFixedPrice(): bool
    {
        return $this->price_type === PricingPriceType::FIXED;
    }

    /**
     * Indica se il listino è di tipo "a partire da".
     */
    public function isStartingFrom(): bool
    {
        return $this->price_type === PricingPriceType::STARTING_FROM;
    }

    /**
     * Indica se il listino è attualmente utilizzabile.
     */
    public function isUsable(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Indica se il raggio commerciale è configurato.
     */
    public function hasServiceRadius(): bool
    {
        return $this->service_radius_km !== null;
    }

    /**
     * Restituisce il raggio commerciale come float.
     */
    public function serviceRadiusKmValue(): ?float
    {
        return $this->service_radius_km !== null
            ? (float) $this->service_radius_km
            : null;
    }

    /**
     * Indica se la distanza è inclusa nel prezzo base.
     */
    public function includesDistance(): bool
    {
        return $this->distance_pricing_mode === PricingDistanceMode::INCLUDED;
    }

    /**
     * Indica se la distanza è gestita tramite regole.
     */
    public function usesDistanceRules(): bool
    {
        return $this->distance_pricing_mode === PricingDistanceMode::SURCHARGE_BY_RULE;
    }

    /**
     * Indica se il servizio non è disponibile fuori raggio.
     */
    public function blocksOutsideRadius(): bool
    {
        return $this->distance_pricing_mode === PricingDistanceMode::NOT_AVAILABLE_OUTSIDE_RADIUS;
    }

    /**
     * Indica se una distanza è fuori raggio.
     */
    public function isDistanceOutsideRadius(?float $distanceKm): bool
    {
        if ($distanceKm === null || ! $this->hasServiceRadius()) {
            return false;
        }

        return $distanceKm > (float) $this->service_radius_km;
    }

    /**
     * Indica se la distanza richiesta è commercialmente accettabile
     * rispetto alla configurazione del listino.
     */
    public function acceptsDistance(?float $distanceKm): bool
    {
        if (! $this->blocksOutsideRadius()) {
            return true;
        }

        return ! $this->isDistanceOutsideRadius($distanceKm);
    }

    /**
     * Restituisce il prezzo base come float.
     */
    public function basePriceValue(): float
    {
        return (float) $this->base_price;
    }

    /**
     * Restituisce il prezzo iniziale risolvibile del listino.
     *
     * Per FREE restituisce 0.00.
     */
    public function resolvableBasePrice(): float
    {
        if ($this->isFree()) {
            return 0.0;
        }

        return $this->basePriceValue();
    }

    /**
     * Restituisce la currency normalizzata in uppercase.
     */
    public function currencyCode(): string
    {
        return strtoupper((string) $this->currency);
    }

    /**
     * Indica se il listino ha almeno una regola attiva.
     */
    public function hasActiveRules(): bool
    {
        if ($this->relationLoaded('rules')) {
            return $this->rules->contains(fn (VendorOfferingPricingRule $rule) => $rule->is_active);
        }

        return $this->rules()->active()->exists();
    }

    /**
     * Restituisce le regole attive ordinate per priorità.
     */
    public function activeRules()
    {
        return $this->rules()->active()->ordered();
    }
}