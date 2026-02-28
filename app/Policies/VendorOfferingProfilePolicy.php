<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOfferingProfile;

/**
 * Policy VendorOfferingProfile
 *
 * Obiettivo:
 * - Admin: può fare tutto.
 * - Vendor: può vedere/modificare SOLO i profili appartenenti al proprio VendorAccount
 *   (ownership su vendor_account_id).
 *
 * Nota sicurezza:
 * Anche se abbiamo già il middleware "active.vendor", la policy non deve fidarsi
 * del contesto della rotta: potrebbe essere riusata altrove (API, job, admin panel, ecc.).
 */
class VendorOfferingProfilePolicy
{
    /**
     * Hook globale: se admin, bypass totale.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Nel progetto usate Spatie Roles; admin deve poter gestire tutto.
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Lista profili.
     *
     * Importante: questa abilità non filtra i record.
     * Lo scoping "solo i miei" va comunque applicato nelle query.
     */
    public function viewAny(User $user): bool
    {
        // Il vendor deve avere accesso al pannello vendor.
        if (!$user->can('vendor.access')) {
            return false;
        }

        // Deve esistere un VendorAccount (relazione 1:1).
        $vendorAccount = $user->vendorAccount;

        // Se non esiste o è soft-deleted, non può accedere.
        return (bool) $vendorAccount && !$vendorAccount->trashed();
    }

    /**
     * Vedere un singolo profilo.
     */
    public function view(User $user, VendorOfferingProfile $vendorOfferingProfile): bool
    {
        return $this->ownsProfile($user, $vendorOfferingProfile);
    }

    /**
     * Creare un profilo.
     *
     * Nota: il vendor_account_id NON deve mai arrivare dal client.
     * Va impostato server-side al momento della creazione.
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
     * Aggiornare un profilo.
     */
    public function update(User $user, VendorOfferingProfile $vendorOfferingProfile): bool
    {
        return $this->ownsProfile($user, $vendorOfferingProfile);
    }

    /**
     * Eliminare un profilo.
     */
    public function delete(User $user, VendorOfferingProfile $vendorOfferingProfile): bool
    {
        return $this->ownsProfile($user, $vendorOfferingProfile);
    }

    /**
     * Check ownership: il profilo appartiene al VendorAccount dell'utente autenticato.
     */
    private function ownsProfile(User $user, VendorOfferingProfile $vendorOfferingProfile): bool
    {
        if (!$user->can('vendor.access')) {
            return false;
        }

        $vendorAccount = $user->vendorAccount;

        if (!$vendorAccount || $vendorAccount->trashed()) {
            return false;
        }

        // VendorOfferingProfile è vendor-owned tramite vendor_account_id.
        return (int) $vendorOfferingProfile->vendor_account_id === (int) $vendorAccount->id;
    }
}