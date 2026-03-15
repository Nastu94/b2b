<?php

namespace App\Models;

use App\Mail\NuovaPrenotazioneVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_account_id',
        'offering_id',
        'slot_lock_id',
        'prestashop_order_id',
        'prestashop_order_line_id',
        'event_date',
        'vendor_slot_id',
        'distance_km',
        'guests',
        'customer_data',
        'status',
        'total_amount',
        'currency',
        'pricing_breakdown',
        'paid_at',
        'confirmed_at',
        'declined_at',
        'vendor_notes',
        'decline_reason',
    ];

    protected $casts = [
        'event_date'        => 'date',
        'distance_km'       => 'decimal:2',
        'guests'            => 'integer',
        'customer_data'     => 'array',
        'total_amount'      => 'decimal:2',
        'pricing_breakdown' => 'array',
        'paid_at'           => 'datetime',
        'confirmed_at'      => 'datetime',
        'declined_at'       => 'datetime',
    ];

    /**
     * Dopo la creazione di un nuovo booking inviamo la notifica email al vendor.
     * Usiamo created (post-persist) così il booking ha già l'id e tutte le relazioni
     * sono caricabili senza rischi.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Booking $booking) {
            static::notifyVendorNewBooking($booking);
        });
    }

    protected static function notifyVendorNewBooking(Booking $booking): void
    {
        try {
            $booking->loadMissing(['vendorAccount', 'offering', 'vendorSlot']);

            $vendorEmail = $booking->vendorAccount?->billing_email
                ?? $booking->vendorAccount?->pec_email;

            if (empty($vendorEmail)) {
                Log::warning('NuovaPrenotazione: email vendor mancante', [
                    'booking_id'        => $booking->id,
                    'vendor_account_id' => $booking->vendor_account_id,
                ]);
                return;
            }

            Mail::to($vendorEmail)->send(new NuovaPrenotazioneVendor($booking));
        } catch (\Throwable $e) {
            // Non blocchiamo il flusso se l'email fallisce — logghiamo e andiamo avanti
            Log::error('NuovaPrenotazione: invio email vendor fallito', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ─── Relazioni ───────────────────────────────────────────────────────────

    public function vendorAccount()
    {
        return $this->belongsTo(VendorAccount::class);
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function slotLock()
    {
        return $this->belongsTo(SlotLock::class);
    }

    public function vendorSlot()
    {
        return $this->belongsTo(VendorSlot::class);
    }

    // ─── Scope ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING_VENDOR_CONFIRMATION');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'CONFIRMED');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }
}