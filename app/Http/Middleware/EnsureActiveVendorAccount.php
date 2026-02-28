<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveVendorAccount
{
    /**
     * Blocca l'accesso al pannello vendor se:
     * - l'utente non è autenticato
     * - l'utente ha accesso vendor (permission vendor.access) ma non ha un VendorAccount
     * - il VendorAccount è soft-deleted (vendor disattivato)
     *
     * Nota: Admin non viene bloccato qui (non usa vendor.access tipicamente).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se non autenticato, rimanda al login (evita 500 su $request->user()).
        if (!$request->user()) {
            return redirect('/login');
        }

        // Applichiamo il check solo a chi ha accesso vendor.
        // Il permesso 'vendor.access' è già previsto nel seeder.
        if ($request->user()->can('vendor.access')) {
            // Relazione esistente: User::vendorAccount() (hasOne).
            $vendorAccount = $request->user()->vendorAccount;

            // Se non esiste l'account vendor, blocca (configurazione incoerente).
            if (!$vendorAccount) {
                abort(403, 'Vendor account non configurato.');
            }

            // Se l'account vendor è soft-deleted, blocca (vendor disattivato).
            // VendorAccount usa SoftDeletes, quindi trashed() è disponibile.
            if ($vendorAccount->trashed()) {
                abort(403, 'Vendor account disattivato.');
            }
        }

        return $next($request);
    }
}