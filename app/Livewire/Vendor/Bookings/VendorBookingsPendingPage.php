<?php

namespace App\Livewire\Vendor\Bookings;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class VendorBookingsPendingPage extends Component
{
    use WithPagination;

    public function render()
    {
        $user = Auth::user();

        // Policy "viewAny": vendor ok, admin ok (ma qui siamo in area vendor)
        $this->authorize('viewAny', Booking::class);

        // ⚠️ Coerente alla tua policy: owner = booking.vendorAccount.user_id
        $bookings = Booking::query()
            ->whereHas('vendorAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->pending()
            ->orderBy('event_date')
            ->paginate(20);

        return view('livewire.vendor.bookings.vendor-bookings-pending-page', [
            'bookings' => $bookings,
        ])->layout('layouts.vendor');
    }
}