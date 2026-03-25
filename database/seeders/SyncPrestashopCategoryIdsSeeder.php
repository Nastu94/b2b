<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPrestashopCategoryIdsSeeder extends Seeder
{
    public function run(): void
    {
        // ======================================================================================
        // ISTRUZIONI PER PRODUZIONE
        // ======================================================================================
        // Poiché PrestaShop genera automaticamente gli ID delle categorie in modo incrementale,
        // questi ID saranno diversi su ogni installazione pulita di PrestaShop.
        // 
        // COSA FARE:
        // 1. Crea le categorie su PrestaShop (es. "Animazione Bambini", "Ristoranti", ecc.)
        // 2. Annota l'ID che PrestaShop ha assegnato a ciascuna categoria.
        // 3. Aggiorna l'elenco $mapping qui sotto inserendo l'ID corretto accanto al rispettivo slug.
        // 4. Lancia il seeder: `php artisan db:seed` (o solo per questo file: `php artisan db:seed --class=SyncPrestashopCategoryIdsSeeder`)
        // ======================================================================================

        // Mapping categorie Laravel (slug) -> PrestaShop category ID
        $mapping = [
            'animazione-bambini' => 20,
            'giochi-e-intrattenimento' => 21,
            'animazione-adulti-feste-private' => 22,
            'addio-al-celibato-nubilato' => 23,
            'eventi-aziendali' => 24,
            'compleanni-adulti' => 25,
            'matrimoni-ed-eventi-eleganti' => 26,
            'servizi-di-supporto' => 27,
            'format-premium-esperienze-esclusive' => 28,
            'ristoranti' => 30,
            'artisti' => 31,
        ];

        // Svuota tutti gli ID esistenti per evitare conflitti di unicità
        DB::table('categories')->update(['prestashop_category_id' => null]);

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