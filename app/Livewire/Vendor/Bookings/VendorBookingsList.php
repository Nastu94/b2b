<?php

namespace App\Livewire\Vendor\Bookings;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class VendorBookingsList extends Component
{
    use WithPagination;

    public string $status;

    public function mount(string $status): void
    {
        $this->status = $status;

        // Policy viewAny
        $this->authorize('viewAny', Booking::class);
    }

    public function render()
    {
        $user = Auth::user();

        $bookings = Booking::query()
            ->whereHas('vendorAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', $this->status)
            ->orderBy('event_date')
            ->paginate(20);

        return view('livewire.vendor.bookings.vendor-bookings-list', [
            'bookings' => $bookings,
        ])->layout('layouts.vendor');
    }
}