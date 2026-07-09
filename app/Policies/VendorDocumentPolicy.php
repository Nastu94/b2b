<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorDocument;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the document.
     * Admin can view all.
     * Vendor can view their own documents.
     */
    public function view(User $user, VendorDocument $document): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('vendor') && $document->vendorAccount && $document->vendorAccount->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('vendor');
    }

    /**
     * Determine whether the user can delete the document.
     * Admin can delete all.
     * Vendor can delete only their own if status is PENDING or REJECTED.
     */
    public function delete(User $user, VendorDocument $document): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('vendor') && $document->vendorAccount && $document->vendorAccount->user_id === $user->id) {
            return in_array($document->status, [VendorDocument::STATUS_PENDING, VendorDocument::STATUS_REJECTED]);
        }

        return false;
    }

    /**
     * Determine whether the user can approve or reject the document.
     */
    public function review(User $user, VendorDocument $document): bool
    {
        return $user->hasRole('admin');
    }
}
