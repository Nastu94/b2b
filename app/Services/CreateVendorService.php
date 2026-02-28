<?php

namespace App\Services;

use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Service: CreateVendorService
 *
 * Responsabilità:
 * - Creare un nuovo utente vendor (User)
 * - Assegnare ruolo vendor (Spatie)
 * - Creare VendorAccount collegato
 * - Garantire vendor.access (permission)
 *
 * Nota sicurezza:
 * - Questo service NON fa authorize() da solo.
 *   L'autorizzazione va fatta nel chiamante (Livewire admin) tramite policy.
 */
class CreateVendorService
{
    /**
     * Crea vendor (User + VendorAccount) in transazione.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): VendorAccount
    {
        return DB::transaction(function () use ($data) {
            // 1) User
            $user = User::create([
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'password' => Hash::make((string) $data['password']),
            ]);

            // 2) Ruolo vendor
            $user->assignRole('vendor');

            // 3) VendorAccount
            $vendorAccount = VendorAccount::create([
                'user_id' => $user->id,
                'category_id' => (int) $data['category_id'],
                'status' => 'ACTIVE',

                // Campi vendor dal tuo form (manteniamo le stesse chiavi)
                'account_type' => $data['account_type'] ?? null,

                'company_name' => $data['company_name'] ?? null,
                'legal_entity_type' => $data['legal_entity_type'] ?? null,
                'vat_number' => $data['vat_number'] ?? null,

                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'tax_code' => $data['tax_code'] ?? null,

                'legal_country' => $data['legal_country'] ?? null,
                'legal_region' => $data['legal_region'] ?? null,
                'legal_city' => $data['legal_city'] ?? null,
                'legal_postal_code' => $data['legal_postal_code'] ?? null,
                'legal_address_line1' => $data['legal_address_line1'] ?? null,

                'operational_same_as_legal' => (bool) ($data['operational_same_as_legal'] ?? true),
                'operational_country' => $data['operational_country'] ?? null,
                'operational_region' => $data['operational_region'] ?? null,
                'operational_city' => $data['operational_city'] ?? null,
                'operational_postal_code' => $data['operational_postal_code'] ?? null,
                'operational_address_line1' => $data['operational_address_line1'] ?? null,
            ]);

            // 4) Permesso base vendor
            $user->givePermissionTo('vendor.access');

            return $vendorAccount;
        });
    }
}