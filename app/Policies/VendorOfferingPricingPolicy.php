<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOfferingPricing;

/**
 * Policy per l'autorizzazione ai listini base vendor + servizio.
 *
 * Regola principale:
 * - il vendor può operare solo sui propri listini
 * - eventuali ruoli amministrativi possono bypassare il controllo ownership
 */
class VendorOfferingPricingPolicy
{
    /**
     * Esegue un controllo preliminare globale.
     *
     * Se l'utente ha un ruolo amministrativo, consente tutte le azioni.
     * Adatta i nomi ruolo se nel progetto usate etichette diverse.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determina se l'utente può visualizzare la lista dei listini.
     *
     * Il vendor autenticato può vedere la propria lista.
     */
    public function viewAny(User $user): bool
    {
        return $user->vendorAccount !== null;
    }

    /**
     * Determina se l'utente può visualizzare il listino.
     */
    public function view(User $user, VendorOfferingPricing $vendorOfferingPricing): bool
    {
        return $this->ownsPricing($user, $vendorOfferingPricing);
    }

    /**
     * Determina se l'utente può creare un listino.
     *
     * Qui verifichiamo solo che l'utente sia un vendor.
     * Il controllo sul vendor_account_id specifico lo faremo anche
     * lato servizio / Livewire prima del salvataggio.
     */
    public function create(User $user): bool
    {
        return $user->vendorAccount !== null;
    }

    /**
     * Determina se l'utente può aggiornare il listino.
     */
    public function update(User $user, VendorOfferingPricing $vendorOfferingPricing): bool
    {
        return $this->ownsPricing($user, $vendorOfferingPricing);
    }

    /**
     * Determina se l'utente può eliminare il listino.
     */
    public function delete(User $user, VendorOfferingPricing $vendorOfferingPricing): bool
    {
        return $this->ownsPricing($user, $vendorOfferingPricing);
    }

    /**
     * Determina se l'utente può ripristinare il listino.
     */
    public function restore(User $user, VendorOfferingPricing $vendorOfferingPricing): bool
    {
        return $this->ownsPricing($user, $vendorOfferingPricing);
    }

    /**
     * Determina se l'utente può eliminare definitivamente il listino.
     */
    public function forceDelete(User $user, VendorOfferingPricing $vendorOfferingPricing): bool
    {
        return $this->ownsPricing($user, $vendorOfferingPricing);
    }

    /**
     * Verifica se il listino appartiene al vendor autenticato.
     */
    private function ownsPricing(User $user, VendorOfferingPricing $vendorOfferingPricing): bool
    {
        return (int) $user->vendorAccount?->id === (int) $vendorOfferingPricing->vendor_account_id;
    }
}