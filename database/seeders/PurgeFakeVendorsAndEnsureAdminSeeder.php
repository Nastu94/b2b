<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PurgeFakeVendorsAndEnsureAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Delete fake vendors by explicit email list
        $fakeEmails = [
            'mario.rossi@partylegacy.it',
            'luca.bianchi@partylegacy.it',
            'giuseppe.verdi@partylegacy.it',
            'antonio.greco@partylegacy.it',
            'francesca.romano@partylegacy.it',
            'alessandro.ferrara@partylegacy.it',
            'valentina.esposito@partylegacy.it',
            'marco.santoro@partylegacy.it',
            'claudia.marino@partylegacy.it',
            'david.guetta@partylegacy.it',
            'ristorante.baia@partylegacy.it',
            'all.you.can.eat@partylegacy.it',
            'admin@admin.it',
            'test-vendor-pending@example.com',
            'demo.dj@partylegacy.it',
            'demo.burlesque@partylegacy.it',
            'demo.hostess@partylegacy.it',
            'demo.fotografia@partylegacy.it',
            'demo.location@partylegacy.it',
            'demo.catering@partylegacy.it',
            'demo.limousine@partylegacy.it',
            'demo.service@partylegacy.it',
            'demo.planner@partylegacy.it',
            'demo.tour@partylegacy.it',
            'demo.beauty@partylegacy.it',
            'demo.security@partylegacy.it',
        ];

        if (!empty($fakeEmails)) {
            $users = User::whereIn('email', $fakeEmails)->get();

            foreach ($users as $user) {
                // Find and delete associated vendor account and its cascaded data
                $vendor = VendorAccount::where('user_id', $user->id)->first();
                if ($vendor) {
                    \App\Models\Offering::where('created_by_vendor_account_id', $vendor->id)->delete();
                    \App\Models\VendorOfferingProfile::where('vendor_account_id', $vendor->id)->delete();
                    // Additional cascading cleanup could be added here (e.g. Bookings) if fake data creates them.
                    $vendor->delete();
                }

                // Clean up model_has_roles and model_has_permissions for this user specifically
                DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', User::class)
                    ->delete();
                    
                DB::table('model_has_permissions')
                    ->where('model_id', $user->id)
                    ->where('model_type', User::class)
                    ->delete();

                $user->delete();
            }
            $this->command->info('Fake vendors and related user data purged.');
        } else {
            $this->command->info('No explicit fake vendor emails provided, skipping purge.');
        }

        // 2. Ensure admin user exists
        $adminEmail = env('SEED_ADMIN_EMAIL');
        $adminName = env('SEED_ADMIN_NAME');
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        if (!$adminEmail || !$adminName || !$adminPassword) {
            $this->command->warn('Admin details not fully provided in .env (SEED_ADMIN_EMAIL, SEED_ADMIN_NAME, SEED_ADMIN_PASSWORD). Skipping admin creation.');
            return;
        }

        // Fail if the 'admin' role does not exist
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->command->error('The "admin" role does not exist. Failing seeder to prevent creating an admin without permissions.');
            return;
        }

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        $this->command->info("Admin user ({$adminEmail}) ensured.");
    }
}
