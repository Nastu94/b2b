<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',

        // Categoria
        'category_id',

        // Tipo account
        'account_type', // COMPANY | PRIVATE

        // Dati azienda / privato
        'company_name',
        'legal_entity_type',
        'vat_number',
        'tax_code',
        'first_name',
        'last_name',

        // Fatturazione / contatti
        'pec_email',
        'sdi_code',
        'billing_email',
        'contact_name',
        'phone',

        // Sede legale
        'legal_country',
        'legal_region',
        'legal_city',
        'legal_postal_code',
        'legal_address_line1',
        'legal_address_line2',

        // Sede operativa
        'operational_same_as_legal',
        'operational_country',
        'operational_region',
        'operational_city',
        'operational_postal_code',
        'operational_address_line1',
        'operational_address_line2',

        // Coordinate sede legale
        'legal_lat',
        'legal_lng',

        // Coordinate sede operativa
        'operational_lat',
        'operational_lng',

        // Stato
        'status',
        'activated_at',
    ];

    protected $casts = [
        'operational_same_as_legal' => 'boolean',
        'activated_at' => 'datetime',
    ];

    // Relazione con Category, ogni VendorAccount appartiene a una Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Relazione con User, ogni VendorAccount appartiene a un User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offerings(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Offering::class,
            'vendor_offerings'
        )->withPivot('is_active')->withTimestamps();
    }


    public function offeringProfiles()
    {
        return $this->hasMany(VendorOfferingProfile::class);
    }

    /**
     * Relazione con i profili delle offerings del vendor
     */
    public function vendorOfferingProfiles()
    {
        return $this->hasMany(VendorOfferingProfile::class, 'vendor_account_id');
    }

    /**
     * Coordinate effettive: preferisci operativa, altrimenti legale.
     */
    public function effectiveLat(): ?float
    {
        return $this->operational_lat ?? $this->legal_lat;
    }

    public function effectiveLng(): ?float
    {
        return $this->operational_lng ?? $this->legal_lng;
    }

    // Relazione con VendorSlot
    public function slots(): HasMany
    {
        return $this->hasMany(VendorSlot::class);
    }

    // Relazione con VendorWeeklySchedule
    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(VendorWeeklySchedule::class);
    }

    // Relazione con VendorLeadTime
    public function leadTimes(): HasMany
    {
        return $this->hasMany(VendorLeadTime::class);
    }

    // Relazione con VendorBlackout
    public function blackouts(): HasMany
    {
        return $this->hasMany(VendorBlackout::class);
    }
}
