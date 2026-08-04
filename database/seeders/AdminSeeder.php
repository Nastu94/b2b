<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('SEED_ADMIN_EMAIL'));
        $name = trim((string) env('SEED_ADMIN_NAME'));
        $pass = (string) env('SEED_ADMIN_PASSWORD');

        if ($email === '' || $name === '' || $pass === '') {
            $this->command?->warn('Credenziali admin non configurate: creazione admin saltata.');
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('SEED_ADMIN_EMAIL non è un indirizzo email valido.');
        }

        if (strlen($pass) < 12 || preg_match('/^(admin|password|changeme)/i', $pass)) {
            throw new \RuntimeException('SEED_ADMIN_PASSWORD deve avere almeno 12 caratteri e non può essere una password comune.');
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($pass)]
        );

        // assegna ruolo admin
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command?->info('Utente admin verificato.');
    }
}
