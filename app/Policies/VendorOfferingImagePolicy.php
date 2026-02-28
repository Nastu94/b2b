<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOfferingImage;

/**
 * Policy VendorOfferingImage
 *
 * Obiettivo:
 * - Admin: può fare tutto.
 * - Vendor: può vedere/modificare SOLO le immagini appartenenti ai propri profili.
 *
 * Ownership:
 * VendorOfferingImage -> profile (vendor_offering_profile_id) -> vendor_account_id
 */
class VendorOfferingImagePolicy
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
     * Lista immagini.
     *
     * Nota: come sempre, viewAny non filtra i record.
     * Lo scoping va applicato nelle query.
     */
    public function viewAny(User $user): bool
    {
        if (!$user->can('vendor.access')) {
            return false;
        }

        $vendorAccount = $user->vendorAccount;

        return (bool) $vendorAccount && !$vendorAccount->trashed();
    }

    /**
     * Vedere una singola immagine.
     */
    public function view(User $user, VendorOfferingImage $vendorOfferingImage): bool
    {
        return $this->ownsImage($user, $vendorOfferingImage);
    }

    /**
     * Creare un'immagine.
     *
     * Nota: la create in genere avviene "sotto" un profilo.
     * Qui concediamo solo se il vendor è attivo; l'ownership vera la verifichi
     * sul profilo target (es. authorize('update', $profile)).
     */
    public function create(User $user): bool
    {
        if (!$user->can('vendor.access')) {
            return false;
        }

        $vendorAccount = $user->vendorAccount;

        return (bool) $vendorAccount && !$vendorAccount->trashed();
    }

    /**
     * Aggiornare un'immagine (es. sort_order, path).
     */
    public function update(User $user, VendorOfferingImage $vendorOfferingImage): bool
    {
        return $this->ownsImage($user, $vendorOfferingImage);
    }

    /**
     * Eliminare un'immagine.
     */
    public function delete(User $user, VendorOfferingImage $vendorOfferingImage): bool
    {
        return $this->ownsImage($user, $vendorOfferingImage);
    }

    /**
     * Check ownership:
     * l'immagine è "mia" se il profilo associato appartiene al mio VendorAccount.
     */
    private function ownsImage(User $user, VendorOfferingImage $vendorOfferingImage): bool
    {
        if (!$user->can('vendor.access')) {
            return false;
        }

        $vendorAccount = $user->vendorAccount;

        if (!$vendorAccount || $vendorAccount->trashed()) {
            return false;
        }

        /**
         * VendorOfferingImage ha la relazione profile() verso VendorOfferingProfile
         * tramite 'vendor_offering_profile_id'. :contentReference[oaicite:2]{index=2}
         */
        $profile = $vendorOfferingImage->profile;

        if (!$profile) {
            // Dato incoerente: non concediamo nulla.
            return false;
        }

        /**
         * VendorOfferingProfile è vendor-owned tramite vendor_account_id. :contentReference[oaicite:3]{index=3}
         */
        return (int) $profile->vendor_account_id === (int) $vendorAccount->id;
    }
}