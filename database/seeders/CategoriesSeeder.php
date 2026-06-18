<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disattiva tutte le categorie esistenti per non creare conflitti (soft "delete" operativo)
        Category::query()->update(['is_active' => false]);

        $categories = [
            ['name' => 'Artisti e Performer', 'slug' => 'artisti-e-performer'],
            ['name' => 'Spettacoli per Adulti', 'slug' => 'spettacoli-per-adulti'],
            ['name' => 'Hostess, Modelle e Promoter', 'slug' => 'hostess-modelle-e-promoter'],
            ['name' => 'Fotografia e Video', 'slug' => 'fotografia-e-video'],
            ['name' => 'Location', 'slug' => 'location'],
            ['name' => 'Food & Beverage', 'slug' => 'food-beverage'],
            ['name' => 'Trasporti e Noleggi', 'slug' => 'trasporti-e-noleggi'],
            ['name' => 'Allestimenti e Service', 'slug' => 'allestimenti-e-service'],
            ['name' => 'Organizzazione Eventi', 'slug' => 'organizzazione-eventi'],
            ['name' => 'Esperienze e Attività', 'slug' => 'esperienze-e-attivita'],
            ['name' => 'Benessere e Beauty', 'slug' => 'benessere-e-beauty'],
            ['name' => 'Servizi Professionali', 'slug' => 'servizi-professionali'],
        ];

        $sort = 10;
        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'is_active' => true,
                    'sort_order' => $sort,
                ]
            );
            $sort += 10;
        }

        $this->command->info('Categorie macro attivate con successo (12 record).');
    }
}