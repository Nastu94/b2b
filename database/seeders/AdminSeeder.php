<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@admin.it');
        $name  = env('SEED_ADMIN_NAME', 'Admin');
        $pass  = env('SEED_ADMIN_PASSWORD', 'Admin123');

        // crea o recupera
        $admin = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($pass)]
        );

        // assegna ruolo admin
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // output utile quando si esegue db:seed
        if ($this->command) {
            $this->command->info("Admin seed pronto!");
        }
    }
}
