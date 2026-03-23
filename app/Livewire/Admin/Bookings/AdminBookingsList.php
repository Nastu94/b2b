<?php

namespace App\Livewire\Admin\Bookings;

use App\Models\Booking;
use App\Models\SlotLock;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AdminBookingsList extends Component
{
    use WithPagination;

    public string $status = '';

    public function mount(string $status = ''): void
    {
        $this->status = $status;
        $this->authorize('viewAny', Booking::class);
    }

    public function deleteBooking(int $id): void
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('delete', $booking);

        DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::lockForUpdate()->findOrFail($booking->id);

            if ($lockedBooking->slot_lock_id) {
                $lock = SlotLock::lockForUpdate()->find($lockedBooking->slot_lock_id);

                if ($lock && $lock->is_active) {
                    $lock->markCancelled();
                }
            }

            $lockedBooking->delete();
        });

        session()->flash('success', 'Prenotazione eliminata.');
        $this->resetPage();
    }

    public function render()
    {
        $query = Booking::query()->orderByDesc('id');

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        $bookings = $query->paginate(20);

        return view('livewire.admin.bookings.admin-bookings-list', [
            'bookings' => $bookings,
        ])->layout('layouts.admin');
    }
}