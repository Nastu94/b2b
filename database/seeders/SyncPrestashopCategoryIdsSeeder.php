<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class SyncPrestashopCategoryIdsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Sincronizzazione ID PrestaShop fittizi o nulli sulle categorie...');

        // Mappa provvisoria: inserire qui gli ID reali di PrestaShop una volta creati
        $mapping = [
            'artisti-e-performer' => 32, // Sostituire con l'ID reale
            'spettacoli-per-adulti' => 33,
            'hostess-modelle-e-promoter' => 34,
            'fotografia-e-video' => 35,
            'location' => 36,
            'food-beverage' => 37,
            'trasporti-e-noleggi' => 38,
            'allestimenti-e-service' => 39,
            'organizzazione-eventi' => 40,
            'esperienze-e-attivita' => 41,
            'benessere-e-beauty' => 42,
            'servizi-professionali' => 43,
        ];

        $updatedCats = 0;
        foreach ($mapping as $slug => $prestashopId) {
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                // Impostiamo l'ID solo se valorizzato, altrimenti null
                $category->update(['prestashop_category_id' => $prestashopId]);
                $updatedCats++;
            }
        }

        $this->command->info("Aggiornati gli ID PrestaShop per {$updatedCats} categorie.");
    }
}