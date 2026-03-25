<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Pulizia/Migrazione vecchi dati se il seeder viene lanciato senza refresh
        \App\Models\Category::where('slug', 'animazione-teen-party')->update([
            'slug' => 'giochi-e-intrattenimento',
            'name' => 'Giochi e Intrattenimento'
        ]);

        \App\Models\Offering::whereIn('name', [
            'DJ set con animatore',
            'DJ set personalizzato',
            'DJ corporate',
            'DJ set',
            'DJ matrimonio'
        ])->delete();

        $macros = [
            'Animazione Bambini',
            'Giochi e Intrattenimento',
            'Animazione Adulti - Feste Private',
            'Addio al Celibato / Nubilato',
            'Eventi Aziendali',
            'Compleanni Adulti',
            'Matrimoni ed Eventi Eleganti',
            'Servizi di Supporto',
            'Format Premium / Esperienze Esclusive',
            'Artisti',
            'Ristoranti',
        ];

        $order = 10;

        foreach ($macros as $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );

            $order += 10;
        }
    }
}