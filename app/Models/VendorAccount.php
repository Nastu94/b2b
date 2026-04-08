<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Cashier\Billable;

class VendorAccount extends Model
{
    use SoftDeletes, Billable;

    protected static function booted()
    {
        static::saved(function ($vendor) {
            \App\Jobs\PushVendorToPrestashopJob::dispatch($vendor);
        });

        static::deleted(function ($vendor) {
            \App\Jobs\PushVendorToPrestashopJob::dispatch($vendor);
        });
    }

    // Campi assegnabili in mass assignment.
    protected $fillable = [
        'user_id',

        // Categoria
        'category_id',

        // Tipo account
        'account_type',

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

        // Integrazione PrestaShop
        'prestashop_product_id',

        // Immagine profilo / logo
        'profile_image_path',

        // Configurazione
        'custom_commission_rate',

        // Stato
        'status',
        'activated_at',
        'deactivated_at',
    ];

    // Cast espliciti per mantenere coerenti i tipi in lettura e scrittura.
    protected $casts = [
        'operational_same_as_legal' => 'boolean',
        'legal_lat' => 'float',
        'legal_lng' => 'float',
        'operational_lat' => 'float',
        'operational_lng' => 'float',
        'prestashop_product_id' => 'integer',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    // Ogni vendor appartiene a una categoria.
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Ogni vendor appartiene a un utente.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Tipi di evento che il vendor è abilitato a servire.
    public function eventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'event_type_vendor_account');
    }

    // Relazione many-to-many con il catalogo offerings.
    public function offerings(): BelongsToMany
    {
        return $this->belongsToMany(
            Offering::class,
            'vendor_offerings'
        )->withPivot('is_active')->withTimestamps();
    }

    // Relazione legacy mantenuta per compatibilità interna.
    public function offeringProfiles(): HasMany
    {
        return $this->hasMany(VendorOfferingProfile::class);
    }

    // Relazione esplicita con i profili offering del vendor.
    public function vendorOfferingProfiles(): HasMany
    {
        return $this->hasMany(VendorOfferingProfile::class, 'vendor_account_id');
    }

    // Restituisce la latitudine effettiva del vendor.
    // Se disponibile, usa la sede operativa; altrimenti usa quella legale.
    public function effectiveLat(): ?float
    {
        return $this->operational_lat ?? $this->legal_lat;
    }

    // Restituisce la longitudine effettiva del vendor.
    // Se disponibile, usa la sede operativa; altrimenti usa quella legale.
    public function effectiveLng(): ?float
    {
        return $this->operational_lng ?? $this->legal_lng;
    }

    // Restituisce la città effettiva del vendor.
    // Se disponibile, usa la sede operativa; altrimenti usa quella legale.
    public function effectiveCity(): ?string
    {
        return $this->operational_city ?: $this->legal_city;
    }

    // Relazione con gli slot configurati dal vendor.
    public function slots(): HasMany
    {
        return $this->hasMany(VendorSlot::class);
    }

    // Relazione con la configurazione settimanale degli slot.
    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(VendorWeeklySchedule::class);
    }

    // Relazione con le regole di lead time del vendor.
    public function leadTimes(): HasMany
    {
        return $this->hasMany(VendorLeadTime::class);
    }

    // Relazione con i blackout del vendor.
    public function blackouts(): HasMany
    {
        return $this->hasMany(VendorBlackout::class);
    }

    // Relazione con le prenotazioni del vendor.
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Relazione con i listini base del vendor.
     */
    public function pricings(): HasMany
    {
        return $this->hasMany(VendorOfferingPricing::class);
    }
}
