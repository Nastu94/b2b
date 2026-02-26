<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perms = [
            'admin.access',
            'vendor.access',
            'vendors.manage',        // admin
            'bookings.manage',       // admin+vendor (poi con policy/ownership)
            'availability.manage',   // vendor (blackout/lead time) 
            'settings.manage',       // admin
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $admin  = Role::firstOrCreate(['name' => 'admin']);
        $vendor = Role::firstOrCreate(['name' => 'vendor']);

        $admin->syncPermissions($perms);
        $vendor->syncPermissions(['vendor.access','bookings.manage','availability.manage']);
    }
}
