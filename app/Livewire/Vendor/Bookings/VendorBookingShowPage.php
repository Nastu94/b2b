<?php

namespace App\Livewire\Vendor\Bookings;

use App\Mail\PrenotazioneConfermata;
use App\Mail\PrenotazioneConfermataVendor;
use App\Mail\PrenotazioneRifiutata;
use App\Models\Booking;
use App\Models\SlotLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class VendorBookingShowPage extends Component
{
    public Booking $booking;

    public ?string $vendorNotes = null;
    public ?string $declineReason = null;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;

        $this->authorize('view', $this->booking);

        $this->vendorNotes = $this->booking->vendor_notes;
    }

    public function confirm(): void
    {
        $this->authorize('update', $this->booking);

        DB::transaction(function () {
            $b = Booking::lockForUpdate()->findOrFail($this->booking->id);

            if ($b->status !== 'PENDING_VENDOR_CONFIRMATION') {
                return;
            }

            $b->update([
                'status'       => 'CONFIRMED',
                'confirmed_at' => now(),
                'vendor_notes' => $this->vendorNotes,
            ]);
        });

        $this->booking->refresh();

        // Email al cliente: prenotazione confermata con dati del vendor
        $this->sendConfirmEmailToClient();

        // Email al vendor: riepilogo conferma con dati del cliente
        $this->sendConfirmEmailToVendor();
    }

    public function decline(): void
    {
        $this->authorize('update', $this->booking);

        DB::transaction(function () {
            $b = Booking::lockForUpdate()->findOrFail($this->booking->id);

            if ($b->status !== 'PENDING_VENDOR_CONFIRMATION') {
                return;
            }

            // Rilascia lock → lo slot torna disponibile
            if ($b->slot_lock_id) {
                $lock = SlotLock::lockForUpdate()->find($b->slot_lock_id);
                if ($lock && $lock->is_active) {
                    $lock->update([
                        'status'    => 'CANCELLED',
                        'is_active' => false,
                    ]);
                }
            }

            $b->update([
                'status'         => 'DECLINED',
                'declined_at'    => now(),
                'decline_reason' => $this->declineReason,
                'vendor_notes'   => $this->vendorNotes,
            ]);
        });

        $this->booking->refresh();

        // Email al cliente: prenotazione rifiutata con eventuale motivo
        $this->sendDeclineEmailToClient();
    }

    protected function sendConfirmEmailToClient(): void
    {
        $clientEmail = data_get($this->booking->customer_data, 'email');

        if (empty($clientEmail)) {
            Log::warning('BookingConfirm: email cliente mancante', ['booking_id' => $this->booking->id]);
            return;
        }

        try {
            $this->booking->loadMissing(['vendorAccount', 'offering', 'vendorSlot']);

            Mail::to($clientEmail)->send(new PrenotazioneConfermata($this->booking));
        } catch (\Throwable $e) {
            Log::error('BookingConfirm: invio email cliente fallito', [
                'booking_id' => $this->booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    protected function sendConfirmEmailToVendor(): void
    {
        $this->booking->loadMissing('vendorAccount');
        $vendorEmail = $this->booking->vendorAccount?->billing_email
            ?? $this->booking->vendorAccount?->pec_email;

        if (empty($vendorEmail)) {
            Log::warning('BookingConfirm: email vendor mancante', ['booking_id' => $this->booking->id]);
            return;
        }

        try {
            $this->booking->loadMissing(['offering', 'vendorSlot']);

            Mail::to($vendorEmail)->send(new PrenotazioneConfermataVendor($this->booking));
        } catch (\Throwable $e) {
            Log::error('BookingConfirm: invio email vendor fallito', [
                'booking_id' => $this->booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    protected function sendDeclineEmailToClient(): void
    {
        $clientEmail = data_get($this->booking->customer_data, 'email');

        if (empty($clientEmail)) {
            Log::warning('BookingDecline: email cliente mancante', ['booking_id' => $this->booking->id]);
            return;
        }

        try {
            $this->booking->loadMissing(['vendorAccount', 'offering', 'vendorSlot']);

            Mail::to($clientEmail)->send(new PrenotazioneRifiutata($this->booking));
        } catch (\Throwable $e) {
            Log::error('BookingDecline: invio email cliente fallito', [
                'booking_id' => $this->booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.vendor.bookings.vendor-booking-show-page')->layout('layouts.vendor');
    }
}