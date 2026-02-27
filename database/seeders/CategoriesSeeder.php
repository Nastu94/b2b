<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $macros = [
            'Animazione Bambini',
            'Animazione Teen Party',
            'Animazione Adulti - Feste Private',
            'Addio al Celibato / Nubilato',
            'Eventi Aziendali',
            'Compleanni Adulti',
            'Matrimoni ed Eventi Eleganti',
            'Servizi di Supporto',
            'Format Premium / Esperienze Esclusive',
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