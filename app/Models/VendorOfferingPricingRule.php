<?php

namespace App\Models;

use App\Domain\Pricing\Contracts\PricingConditionKeys;
use App\Domain\Pricing\Enums\PricingAdjustmentType;
use App\Domain\Pricing\Enums\PricingRuleType;
use App\Domain\Pricing\Support\PricingConditions;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model delle regole di pricing.
 *
 * Le regole rappresentano modifiche applicabili al listino base:
 * - surcharge
 * - discount
 * - override
 *
 * La valutazione concreta delle condizioni sarà demandata
 * al resolver applicativo.
 */
class VendorOfferingPricingRule extends Model
{
    use SoftDeletes;

    /**
     * Attributi mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vendor_offering_pricing_id',
        'name',
        'is_active',
        'priority',
        'rule_type',
        'adjustment_type',
        'adjustment_value',
        'override_price',
        'starts_at',
        'ends_at',
        'weekdays',
        'min_quantity',
        'max_quantity',
        'is_exclusive',
        'conditions',
        'notes_internal',
    ];

    /**
     * Cast automatici.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'rule_type' => PricingRuleType::class,
        'adjustment_type' => PricingAdjustmentType::class,
        'adjustment_value' => 'decimal:2',
        'override_price' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'weekdays' => 'array',
        'conditions' => 'array',
        'is_exclusive' => 'boolean',
    ];

    /**
     * Relazione con il listino base.
     */
    public function pricing(): BelongsTo
    {
        return $this->belongsTo(VendorOfferingPricing::class, 'vendor_offering_pricing_id');
    }

    /**
     * Scope: solo regole attive.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordinate per priorità crescente.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority');
    }

    /**
     * Scope: regole del listino specificato.
     */
    public function scopeForPricing($query, int $vendorOfferingPricingId)
    {
        return $query->where('vendor_offering_pricing_id', $vendorOfferingPricingId);
    }

    /**
     * Scope: regole owner-scoped sul vendor autenticato.
     */
    public function scopeOwnedByUser($query, User $user)
    {
        return $query->whereHas('pricing', function ($pricingQuery) use ($user) {
            $pricingQuery->where('vendor_account_id', $user->vendorAccount?->id);
        });
    }

    /**
     * Indica se la regola è di tipo override.
     */
    public function isOverrideRule(): bool
    {
        return $this->rule_type === PricingRuleType::OVERRIDE;
    }

    /**
     * Indica se la regola è di tipo maggiorazione.
     */
    public function isSurchargeRule(): bool
    {
        return $this->rule_type === PricingRuleType::SURCHARGE;
    }

    /**
     * Indica se la regola è di tipo sconto.
     */
    public function isDiscountRule(): bool
    {
        return $this->rule_type === PricingRuleType::DISCOUNT;
    }

    /**
     * Indica se la regola usa un importo fisso.
     */
    public function usesFixedAdjustment(): bool
    {
        return $this->adjustment_type === PricingAdjustmentType::FIXED;
    }

    /**
     * Indica se la regola usa una percentuale.
     */
    public function usesPercentAdjustment(): bool
    {
        return $this->adjustment_type === PricingAdjustmentType::PERCENT;
    }

    /**
     * Indica se la regola è esclusiva.
     */
    public function isExclusive(): bool
    {
        return $this->is_exclusive === true;
    }

    /**
     * Restituisce il valore di aggiustamento come float.
     */
    public function adjustmentValue(): ?float
    {
        return $this->adjustment_value !== null
            ? (float) $this->adjustment_value
            : null;
    }

    /**
     * Restituisce il prezzo override come float.
     */
    public function overridePriceValue(): ?float
    {
        return $this->override_price !== null
            ? (float) $this->override_price
            : null;
    }

    /**
     * Indica se la regola ha una finestra date configurata.
     */
    public function hasDateRange(): bool
    {
        return $this->starts_at !== null || $this->ends_at !== null;
    }

    /**
     * Verifica se la data ricade nell'intervallo della regola.
     */
    public function matchesDate(?CarbonInterface $date): bool
    {
        if ($date === null) {
            return true;
        }

        if ($this->starts_at !== null && $date->toDateString() < $this->starts_at->toDateString()) {
            return false;
        }

        if ($this->ends_at !== null && $date->toDateString() > $this->ends_at->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Indica se la regola ha vincoli sui giorni della settimana.
     */
    public function hasWeekdayConstraint(): bool
    {
        return is_array($this->weekdays) && $this->weekdays !== [];
    }

    /**
     * Verifica se il giorno della settimana è compatibile con la regola.
     *
     * Convenzione:
     * - 1 = lunedì
     * - 7 = domenica
     */
    public function matchesWeekday(?int $weekday): bool
    {
        if (! $this->hasWeekdayConstraint() || $weekday === null) {
            return true;
        }

        $weekdays = array_map(
            static fn (mixed $value): int => (int) $value,
            is_array($this->weekdays) ? $this->weekdays : []
        );

        return in_array($weekday, $weekdays, true);
    }

    /**
     * Indica se la regola ha vincoli di quantità.
     */
    public function hasQuantityConstraint(): bool
    {
        return $this->min_quantity !== null || $this->max_quantity !== null;
    }

    /**
     * Verifica se la quantità ricade nei limiti configurati.
     */
    public function matchesQuantity(?int $quantity): bool
    {
        if ($quantity === null) {
            return true;
        }

        if ($this->min_quantity !== null && $quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity !== null && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    /**
     * Indica se il payload conditions contiene almeno una condizione.
     */
    public function hasConditions(): bool
    {
        return PricingConditions::sanitize($this->conditions) !== [];
    }

    /**
     * Verifica se esiste una specifica chiave standard nelle conditions.
     */
    public function hasCondition(string $key): bool
    {
        return PricingConditions::has($this->conditions, $key);
    }

    /**
     * Restituisce una specifica chiave standard delle conditions.
     */
    public function condition(string $key, mixed $default = null): mixed
    {
        return PricingConditions::get($this->conditions, $key, $default);
    }

    /**
     * Indica se la regola ha condizioni di distanza.
     */
    public function hasDistanceCondition(): bool
    {
        return $this->hasCondition(PricingConditionKeys::DISTANCE_KM_MIN)
            || $this->hasCondition(PricingConditionKeys::DISTANCE_KM_MAX);
    }

    /**
     * Verifica se la distanza ricade nei limiti configurati.
     */
    public function matchesDistance(?float $distanceKm): bool
    {
        if ($distanceKm === null) {
            return ! $this->hasDistanceCondition();
        }

        $distanceMin = $this->condition(PricingConditionKeys::DISTANCE_KM_MIN);
        $distanceMax = $this->condition(PricingConditionKeys::DISTANCE_KM_MAX);

        if ($distanceMin !== null && $distanceKm < (float) $distanceMin) {
            return false;
        }

        if ($distanceMax !== null && $distanceKm > (float) $distanceMax) {
            return false;
        }

        return true;
    }

    /**
     * Indica se la regola ha condizioni di anticipo prenotazione.
     */
    public function hasLeadDaysCondition(): bool
    {
        return $this->hasCondition(PricingConditionKeys::LEAD_DAYS_MIN)
            || $this->hasCondition(PricingConditionKeys::LEAD_DAYS_MAX);
    }

    /**
     * Verifica se i giorni di anticipo ricadono nei limiti configurati.
     */
    public function matchesLeadDays(?int $leadDays): bool
    {
        if ($leadDays === null) {
            return ! $this->hasLeadDaysCondition();
        }

        $leadDaysMin = $this->condition(PricingConditionKeys::LEAD_DAYS_MIN);
        $leadDaysMax = $this->condition(PricingConditionKeys::LEAD_DAYS_MAX);

        if ($leadDaysMin !== null && $leadDays < (int) $leadDaysMin) {
            return false;
        }

        if ($leadDaysMax !== null && $leadDays > (int) $leadDaysMax) {
            return false;
        }

        return true;
    }

    /**
     * Indica se la regola ha condizioni sul numero ospiti.
     */
    public function hasGuestsCondition(): bool
    {
        return $this->hasCondition(PricingConditionKeys::GUESTS_MIN)
            || $this->hasCondition(PricingConditionKeys::GUESTS_MAX);
    }

    /**
     * Verifica se il numero ospiti ricade nei limiti configurati.
     */
    public function matchesGuests(?int $guests): bool
    {
        if ($guests === null) {
            return ! $this->hasGuestsCondition();
        }

        $guestsMin = $this->condition(PricingConditionKeys::GUESTS_MIN);
        $guestsMax = $this->condition(PricingConditionKeys::GUESTS_MAX);

        if ($guestsMin !== null && $guests < (int) $guestsMin) {
            return false;
        }

        if ($guestsMax !== null && $guests > (int) $guestsMax) {
            return false;
        }

        return true;
    }

    /**
     * Restituisce true se la regola è potenzialmente applicabile
     * in base ai soli vincoli locali del model.
     *
     * Questo helper non sostituisce il resolver, ma alleggerisce
     * UI, simulatore e logica futura.
     */
    public function matchesContext(
        ?CarbonInterface $date = null,
        ?int $weekday = null,
        ?int $quantity = null,
        ?float $distanceKm = null,
        ?int $leadDays = null,
        ?int $guests = null
    ): bool {
        return $this->is_active
            && $this->matchesDate($date)
            && $this->matchesWeekday($weekday)
            && $this->matchesQuantity($quantity)
            && $this->matchesDistance($distanceKm)
            && $this->matchesLeadDays($leadDays)
            && $this->matchesGuests($guests);
    }
}