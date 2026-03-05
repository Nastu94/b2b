<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPrestashopCategoryIdsSeeder extends Seeder
{
    public function run(): void
    {
        // Mapping categorie Laravel (slug) -> PrestaShop category ID
        $mapping = [
            'animazione-bambini' => 12,
            'animazione-teen-party' => 13,
            'animazione-adulti-feste-private' => 14,
            'addio-al-celibato-nubilato' => 15,
            'eventi-aziendali' => 16,
            'compleanni-adulti' => 17,
            'matrimoni-ed-eventi-eleganti' => 18,
            'servizi-di-supporto' => 19,
            'format-premium-esperienze-esclusive' => 20,
        ];

        foreach ($mapping as $slug => $prestashopId) {
            $updated = DB::table('categories')
                ->where('slug', $slug)
                ->update(['prestashop_category_id' => $prestashopId]);

            if ($updated === 0) {
                // Registra un warning se non è stato trovato alcun record da aggiornare
                Log::warning('SyncPrestashopCategoryIdsSeeder: slug non trovato', [
                    'slug' => $slug,
                    'prestashop_category_id' => $prestashopId,
                ]);
            }
        }
    }
}