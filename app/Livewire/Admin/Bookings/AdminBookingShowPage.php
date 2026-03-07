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
        $this->authorize('view', $this->booking); // admin ok
    }

    public function deleteBooking(): void
    {
        $this->authorize('delete', $this->booking);

        DB::transaction(function () {
            $b = Booking::lockForUpdate()->withTrashed()->findOrFail($this->booking->id);

            // Rilascia lock se presente
            if ($b->slot_lock_id) {
                $lock = SlotLock::lockForUpdate()->find($b->slot_lock_id);
                if ($lock) {
                    $lock->update([
                        'status'    => 'CANCELLED',
                        'is_active' => false,
                    ]);
                }
            }

            $b->delete(); // Soft delete
        });

        // Redirect alla lista admin
        $this->redirect(route('admin.bookings', ['tab' => 'all']), navigate: true);
    }

    public function render()
    {
        // ricarico con relazioni per pagina
        $booking = Booking::with(['vendorAccount', 'vendorSlot', 'slotLock'])
            ->withTrashed()
            ->findOrFail($this->booking->id);

        return view('livewire.admin.bookings.admin-booking-show-page', [
            'b' => $booking,
        ])->layout('layouts.admin');
    }
}