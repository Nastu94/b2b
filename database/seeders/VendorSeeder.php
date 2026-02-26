<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Assicura che il ruolo esista
        Role::firstOrCreate(['name' => 'vendor']);

        $vendor = User::firstOrCreate(
            ['email' => 'vendor@vendor.it'],
            [
                'name' => 'Vendor Demo',
                'password' => Hash::make('Vendor123'),
            ]
        );

        if (!$vendor->hasRole('vendor')) {
            $vendor->assignRole('vendor');
        }

        VendorAccount::firstOrCreate(
            ['user_id' => $vendor->id],
            [
                'company_name' => 'Demo SRL',
                'vat_number' => 'IT12345678901',
                'category' => 'catering',
                'status' => 'ACTIVE',
                'activated_at' => now(),
            ]
        );
    }
}
