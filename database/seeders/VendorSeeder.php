<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'vendor']);

        // Usa una macro-categoria del PDF
        $category = Category::where('slug', Str::slug('Animazione Bambini'))->firstOrFail();

        $vendorUser = User::firstOrCreate(
            ['email' => 'vendor@vendor.it'],
            [
                'name' => 'Vendor Demo',
                'password' => Hash::make('Vendor123'),
            ]
        );

        if (!$vendorUser->hasRole('vendor')) {
            $vendorUser->assignRole('vendor');
        }

        VendorAccount::firstOrCreate(
            ['user_id' => $vendorUser->id],
            [
                'category_id' => $category->id,
                'account_type' => 'COMPANY',

                'company_name' => 'Demo SRL',
                'legal_entity_type' => 'SRL',
                'vat_number' => 'IT12345678901',
                'tax_code' => null,

                'legal_country' => 'IT',
                'legal_region' => 'Puglia',
                'legal_city' => 'Bari',
                'legal_postal_code' => '70100',
                'legal_address_line1' => 'Via Roma 1',
                'legal_address_line2' => null,

                'operational_same_as_legal' => true,

                'status' => 'ACTIVE',
                'activated_at' => now(),
            ]
        );
    }
}