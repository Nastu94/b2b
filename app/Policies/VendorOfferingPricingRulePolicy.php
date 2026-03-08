<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOfferingPricingRule;

/**
 * Policy per l'autorizzazione alle regole di pricing.
 *
 * La proprietà viene verificata risalendo al listino base padre.
 */
class VendorOfferingPricingRulePolicy
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
     * Determina se l'utente può visualizzare la lista delle regole.
     */
    public function viewAny(User $user): bool
    {
        return $user->vendorAccount !== null;
    }

    /**
     * Determina se l'utente può visualizzare una regola.
     */
    public function view(User $user, VendorOfferingPricingRule $vendorOfferingPricingRule): bool
    {
        return $this->ownsRule($user, $vendorOfferingPricingRule);
    }

    /**
     * Determina se l'utente può creare una regola.
     */
    public function create(User $user): bool
    {
        return $user->vendorAccount !== null;
    }

    /**
     * Determina se l'utente può aggiornare una regola.
     */
    public function update(User $user, VendorOfferingPricingRule $vendorOfferingPricingRule): bool
    {
        return $this->ownsRule($user, $vendorOfferingPricingRule);
    }

    /**
     * Determina se l'utente può eliminare una regola.
     */
    public function delete(User $user, VendorOfferingPricingRule $vendorOfferingPricingRule): bool
    {
        return $this->ownsRule($user, $vendorOfferingPricingRule);
    }

    /**
     * Determina se l'utente può ripristinare una regola.
     */
    public function restore(User $user, VendorOfferingPricingRule $vendorOfferingPricingRule): bool
    {
        return $this->ownsRule($user, $vendorOfferingPricingRule);
    }

    /**
     * Determina se l'utente può eliminare definitivamente una regola.
     */
    public function forceDelete(User $user, VendorOfferingPricingRule $vendorOfferingPricingRule): bool
    {
        return $this->ownsRule($user, $vendorOfferingPricingRule);
    }

    /**
     * Verifica se la regola appartiene a un listino del vendor autenticato.
     */
    private function ownsRule(User $user, VendorOfferingPricingRule $vendorOfferingPricingRule): bool
    {
        $vendorOfferingPricingRule->loadMissing('pricing');

        return (int) $user->vendorAccount?->id === (int) $vendorOfferingPricingRule->pricing?->vendor_account_id;
    }
}