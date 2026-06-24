<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class ApplyCommissionSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Inizio aggiornamento commissioni macro-categorie...');

        $commissions = [
            'artisti-e-performer' => 12,
            'spettacoli-per-adulti' => 15,
            'hostess-modelle-e-promoter' => 12,
            'fotografia-e-video' => 12,
            'location' => 15,
            'food-beverage' => 15,
            'trasporti-e-noleggi' => 15,
            'allestimenti-e-service' => 12,
            'organizzazione-eventi' => 20,
            'esperienze-e-attivita' => 15,
            'benessere-e-beauty' => 12,
            'servizi-professionali' => 12,
        ];

        $updatedCats = 0;
        foreach ($commissions as $slug => $rate) {
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                $category->update(['commission_rate' => $rate]);
                $updatedCats++;
            }
        }

        $this->command->info("Aggiornate commissioni per {$updatedCats} macro-categorie.");
    }
}
