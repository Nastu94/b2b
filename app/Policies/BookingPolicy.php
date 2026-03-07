<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determina se user può vedere lista bookings.
     * Admin vede tutto, vendor solo i suoi.
     */
    public function viewAny(User $user): bool
    {
        // Admin ha accesso completo
        if ($user->hasRole('admin')) {
            return true;
        }

        // Vendor può vedere i suoi booking
        return $user->vendorAccount()->exists();
    }

    /**
     * Determina se user può vedere singolo booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Admin vede tutto
        if ($user->hasRole('admin')) {
            return true;
        }

        // Vendor vede solo booking del suo account
        return $user->id === $booking->vendorAccount->user_id;
    }

    /**
     * Determina se user può confermare o rifiutare booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Admin può sempre modificare
        if ($user->hasRole('admin')) {
            return true;
        }

        // Vendor può gestire solo:
        // - I propri booking
        // - Che sono ancora in pending
        return $user->id === $booking->vendorAccount->user_id 
            && $booking->status === 'PENDING_VENDOR_CONFIRMATION';
    }

    /**
     * Solo admin può cancellare booking.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin');
    }
}