<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorAccount;

/**
 * Policy VendorAccount
 *
 * Per ora: solo admin può gestire i vendor.
 * (Vendor non deve vedere/modificare VendorAccount di altri.)
 */
class VendorAccountPolicy
{
    /**
     * Hook globale: se admin, bypass totale.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Lista vendor (dashboard admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('admin.access');
    }

    /**
     * Vedere un vendor specifico.
     */
    public function view(User $user, VendorAccount $vendorAccount): bool
    {
        return $user->can('admin.access');
    }

    /**
     * Soft delete vendor.
     */
    public function delete(User $user, VendorAccount $vendorAccount): bool
    {
        return $user->can('vendors.manage');
    }
}