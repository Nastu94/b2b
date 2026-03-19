<?php

namespace App\Livewire\Admin\Bookings;

use App\Models\Booking;
use App\Models\SlotLock;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AdminBookingShowPage extends Component
{
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
        $this->authorize('view', $this->booking);
    }

    public function deleteBooking(): void
    {
        $this->authorize('delete', $this->booking);

        DB::transaction(function () {
            $b = Booking::lockForUpdate()->withTrashed()->findOrFail($this->booking->id);

            if ($b->slot_lock_id) {
                $lock = SlotLock::lockForUpdate()->find($b->slot_lock_id);

                if ($lock && $lock->is_active) {
                    $lock->markCancelled();
                }
            }

            $b->delete();
        });

        $this->redirect(route('admin.bookings', ['tab' => 'all']), navigate: true);
    }

    public function render()
    {
        $booking = Booking::with(['vendorAccount', 'vendorSlot', 'slotLock'])
            ->withTrashed()
            ->findOrFail($this->booking->id);

        return view('livewire.admin.bookings.admin-booking-show-page', [
            'b' => $booking,
        ])->layout('layouts.admin');
    }
}