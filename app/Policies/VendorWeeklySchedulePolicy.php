<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorWeeklySchedule;

class VendorWeeklySchedulePolicy
{
    /**
     * Hook globale: admin bypassa tutto.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('vendor')
            && $user->vendorAccount
            && !$user->vendorAccount->trashed();
    }

    public function view(User $user, VendorWeeklySchedule $schedule): bool
    {
        return $user->hasRole('vendor')
            && (int) $user->vendorAccount?->id === (int) $schedule->vendor_account_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('vendor')
            && $user->vendorAccount
            && !$user->vendorAccount->trashed();
    }

    public function update(User $user, VendorWeeklySchedule $schedule): bool
    {
        return $user->hasRole('vendor')
            && (int) $user->vendorAccount?->id === (int) $schedule->vendor_account_id;
    }

    public function delete(User $user, VendorWeeklySchedule $schedule): bool
    {
        return $user->hasRole('vendor')
            && (int) $user->vendorAccount?->id === (int) $schedule->vendor_account_id;
    }
}