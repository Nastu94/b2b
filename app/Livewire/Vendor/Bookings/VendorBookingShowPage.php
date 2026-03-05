<?php

namespace App\Livewire\Vendor\Bookings;

use App\Models\Booking;
use App\Models\SlotLock;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class VendorBookingShowPage extends Component
{
    public Booking $booking;

    public ?string $vendorNotes = null;
    public ?string $declineReason = null;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;

        // ✅ Coerente con BookingPolicy::view
        $this->authorize('view', $this->booking);

        $this->vendorNotes = $this->booking->vendor_notes;
    }

    public function confirm(): void
    {
        // ✅ Coerente con BookingPolicy::update (pending + owner)
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

            // Step 4.3: qui aggiungeremo refund_request (prossimo passo)
        });

        $this->booking->refresh();
    }

    public function render()
    {
        return view('livewire.vendor.bookings.vendor-booking-show-page')->layout('layouts.vendor');
    }
}