<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class PurgeFakeVendorsAndEnsureAdminSeeder extends Seeder
{
    /** @var list<string> */
    private const PROTECTED_ROLES = ['admin', 'super_admin'];

    /**
     * Elimina dal database Laravel tutti gli account vendor e i relativi dati.
     *
     * I file, i prodotti PrestaShop e gli abbonamenti Stripe remoti non vengono modificati.
     * Gli ID prodotto PrestaShop collegati vengono mostrati al termine per la pulizia manuale.
     */
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', config('auth.defaults.guard', 'web'))
            ->first();

        if (! $adminRole) {
            throw new RuntimeException(
                'Il ruolo "admin" non esiste per il guard predefinito. Nessun dato e stato eliminato.'
            );
        }

        // Valida l'eventuale creazione admin prima di iniziare qualsiasi cancellazione.
        $adminSeedData = $this->resolveAdminSeedData($adminRole);

        $summary = DB::transaction(function () use ($adminRole, $adminSeedData): array {
            $this->ensureAdmin($adminRole, $adminSeedData);

            $vendors = DB::table('vendor_accounts')
                ->select(['id', 'user_id', 'prestashop_product_id'])
                ->lockForUpdate()
                ->get();

            $vendorIds = $vendors->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            $vendorUserIds = $vendors->pluck('user_id')->map(static fn ($id): int => (int) $id)->all();

            // Include anche eventuali utenti col ruolo vendor rimasti senza vendor_account.
            $vendorRoleUserIds = DB::table('model_has_roles as model_roles')
                ->join('roles', 'roles.id', '=', 'model_roles.role_id')
                ->where('model_roles.model_type', User::class)
                ->where('roles.name', 'vendor')
                ->pluck('model_roles.model_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $userIds = collect($vendorUserIds)
                ->merge($vendorRoleUserIds)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if (empty($vendorIds) && empty($userIds)) {
                return [
                    'users' => 0,
                    'vendors' => 0,
                    'custom_offerings' => 0,
                    'subscriptions' => 0,
                    'prestashop_product_ids' => [],
                ];
            }

            $this->assertNoProtectedUsers($userIds);

            $vendorUsers = empty($userIds)
                ? collect()
                : DB::table('users')
                    ->select(['id', 'email'])
                    ->whereIn('id', $userIds)
                    ->lockForUpdate()
                    ->get();

            $existingUserIds = $vendorUsers
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $emails = $vendorUsers
                ->pluck('email')
                ->map(static fn ($email): string => (string) $email)
                ->all();

            $prestashopProductIds = $vendors
                ->pluck('prestashop_product_id')
                ->filter(static fn ($id): bool => $id !== null)
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $ownedOfferings = empty($vendorIds)
                ? collect()
                : DB::table('offerings')
                    ->select(['id', 'is_custom'])
                    ->whereIn('created_by_vendor_account_id', $vendorIds)
                    ->get();

            if ($ownedOfferings->contains(static fn ($offering): bool => ! (bool) $offering->is_custom)) {
                throw new RuntimeException(
                    'Un servizio creato da un vendor non e marcato come custom. Nessun dato e stato eliminato.'
                );
            }

            $customOfferingIds = $ownedOfferings
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $this->assertCustomOfferingsAreNotShared($customOfferingIds, $vendorIds);

            $subscriptionIds = empty($vendorIds)
                ? []
                : DB::table('subscriptions')
                    ->whereIn('vendor_account_id', $vendorIds)
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

            if (! empty($subscriptionIds)) {
                DB::table('subscription_items')->whereIn('subscription_id', $subscriptionIds)->delete();
                DB::table('subscriptions')->whereIn('id', $subscriptionIds)->delete();
            }

            if (! empty($customOfferingIds)) {
                // Deve avvenire prima della cancellazione dei vendor: la FK altrimenti
                // imposterebbe created_by_vendor_account_id a null.
                DB::table('offerings')->whereIn('id', $customOfferingIds)->delete();
            }

            if (! empty($vendorIds)) {
                // Query Builder intenzionale: evita gli eventi Eloquent di VendorAccount
                // e quindi non accoda webhook per record che stanno per essere rimossi.
                DB::table('vendor_accounts')->whereIn('id', $vendorIds)->delete();

                // Mantiene leggibili eventuali messaggi non appartenenti ai thread eliminati.
                DB::table('conversation_messages')
                    ->where('sender_type', 'vendor')
                    ->whereIn('sender_id', $vendorIds)
                    ->update(['sender_id' => null]);
            }

            if (! empty($userIds)) {
                // Tabelle senza FK verso users: vanno pulite esplicitamente.
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();
                DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $userIds)
                    ->delete();
                DB::table('model_has_permissions')
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $userIds)
                    ->delete();
                DB::table('slot_locks')
                    ->whereIn('created_by_user_id', $userIds)
                    ->update(['created_by_user_id' => null]);
                DB::table('conversation_messages')
                    ->where('sender_type', 'admin')
                    ->whereIn('sender_id', $userIds)
                    ->update(['sender_id' => null]);
            }

            if (! empty($emails)) {
                DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
            }

            if (! empty($existingUserIds)) {
                // Nessun evento Eloquent viene eseguito.
                DB::table('users')->whereIn('id', $existingUserIds)->delete();
            }

            if (
                DB::table('vendor_accounts')->exists()
                || $this->vendorRoleAssignmentsExist()
                || (! empty($existingUserIds) && DB::table('users')->whereIn('id', $existingUserIds)->exists())
                || (! empty($subscriptionIds) && DB::table('subscriptions')->whereIn('id', $subscriptionIds)->exists())
                || (! empty($customOfferingIds) && DB::table('offerings')->whereIn('id', $customOfferingIds)->exists())
            ) {
                throw new RuntimeException('Verifica finale fallita: la transazione di pulizia e stata annullata.');
            }

            return [
                'users' => count($existingUserIds),
                'vendors' => count($vendorIds),
                'custom_offerings' => count($customOfferingIds),
                'subscriptions' => count($subscriptionIds),
                'prestashop_product_ids' => $prestashopProductIds,
            ];
        }, 3);

        if ($summary['users'] === 0 && $summary['vendors'] === 0) {
            $this->command?->info('Nessun account vendor presente. Utente admin verificato.');

            return;
        }

        $this->command?->info(sprintf(
            'Pulizia Laravel completata: %d utenti, %d vendor, %d servizi custom e %d sottoscrizioni locali eliminati.',
            $summary['users'],
            $summary['vendors'],
            $summary['custom_offerings'],
            $summary['subscriptions'],
        ));

        if (! empty($summary['prestashop_product_ids'])) {
            $this->command?->warn(
                'Prodotti PrestaShop da rimuovere manualmente: '.implode(', ', $summary['prestashop_product_ids'])
            );
        }

        $this->command?->warn(
            'Nessun prodotto PrestaShop, file o abbonamento Stripe remoto e stato modificato dal seeder.'
        );
    }

    /**
     * @return array{email: string, name: string, password: string}|null
     */
    private function resolveAdminSeedData(Role $adminRole): ?array
    {
        $adminExists = User::query()
            ->whereHas('roles', static fn ($query) => $query->where('roles.id', $adminRole->getKey()))
            ->exists();

        if ($adminExists) {
            return null;
        }

        $email = strtolower(trim((string) env('SEED_ADMIN_EMAIL')));
        $name = trim((string) env('SEED_ADMIN_NAME'));
        $password = (string) env('SEED_ADMIN_PASSWORD');

        if ($email === '' || $name === '' || $password === '') {
            throw new RuntimeException(
                'Non esiste un amministratore valido e i dati SEED_ADMIN_* non sono completi. Nessun dato e stato eliminato.'
            );
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('SEED_ADMIN_EMAIL non e un indirizzo email valido. Nessun dato e stato eliminato.');
        }

        if (strlen($password) < 12 || preg_match('/^(admin|password|changeme)/i', $password)) {
            throw new RuntimeException(
                'SEED_ADMIN_PASSWORD deve avere almeno 12 caratteri e non puo essere una password comune. Nessun dato e stato eliminato.'
            );
        }

        return [
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ];
    }

    /**
     * @param  array{email: string, name: string, password: string}|null  $adminSeedData
     */
    private function ensureAdmin(Role $adminRole, ?array $adminSeedData): void
    {
        if ($adminSeedData === null) {
            return;
        }

        $admin = User::query()->firstOrCreate(
            ['email' => $adminSeedData['email']],
            [
                'name' => $adminSeedData['name'],
                'password' => Hash::make($adminSeedData['password']),
            ],
        );

        if (! $admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }
    }

    /**
     * Blocca l'intera operazione se un account da eliminare ha privilegi amministrativi.
     *
     * @param  list<int>  $userIds
     */
    private function assertNoProtectedUsers(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        $protectedUserIds = DB::table('model_has_roles as model_roles')
            ->join('roles', 'roles.id', '=', 'model_roles.role_id')
            ->where('model_roles.model_type', User::class)
            ->whereIn('model_roles.model_id', $userIds)
            ->whereIn('roles.name', self::PROTECTED_ROLES)
            ->pluck('model_roles.model_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (! empty($protectedUserIds)) {
            throw new RuntimeException(
                'Gli utenti vendor con ID '.implode(', ', $protectedUserIds)
                .' hanno anche un ruolo amministrativo. Nessun dato e stato eliminato.'
            );
        }
    }

    /**
     * Impedisce che l'eliminazione di un servizio custom coinvolga record di un vendor
     * creato in parallelo e non incluso nella transazione.
     *
     * @param  list<int>  $customOfferingIds
     * @param  list<int>  $vendorIds
     */
    private function assertCustomOfferingsAreNotShared(array $customOfferingIds, array $vendorIds): void
    {
        if (empty($customOfferingIds)) {
            return;
        }

        $sharedReferences = DB::table('vendor_offerings')
            ->whereIn('offering_id', $customOfferingIds)
            ->whereNotIn('vendor_account_id', $vendorIds)
            ->count();

        $sharedReferences += DB::table('vendor_offering_profiles')
            ->whereIn('offering_id', $customOfferingIds)
            ->whereNotIn('vendor_account_id', $vendorIds)
            ->count();

        $sharedReferences += DB::table('vendor_offering_pricings')
            ->whereIn('offering_id', $customOfferingIds)
            ->whereNotIn('vendor_account_id', $vendorIds)
            ->count();

        if ($sharedReferences > 0) {
            throw new RuntimeException(
                'Uno o piu servizi custom sono usati da vendor non inclusi nella transazione. Nessun dato e stato eliminato.'
            );
        }
    }

    private function vendorRoleAssignmentsExist(): bool
    {
        return DB::table('model_has_roles as model_roles')
            ->join('roles', 'roles.id', '=', 'model_roles.role_id')
            ->where('model_roles.model_type', User::class)
            ->where('roles.name', 'vendor')
            ->exists();
    }
}
