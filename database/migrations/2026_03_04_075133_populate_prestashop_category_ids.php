<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mapping categorie Laravel → PrestaShop - DEPRECATED
        // $mapping = [
        //     'animazione-bambini' => 12,
        //     'animazione-teen-party' => 13,
        //     'animazione-adulti-feste-private' => 14,
        //     'addio-al-celibato-nubilato' => 15,
        //     'eventi-aziendali' => 16,
        //     'compleanni-adulti' => 17,
        //     'matrimoni-ed-eventi-eleganti' => 18,
        //     'servizi-di-supporto' => 19,
        //     'format-premium-esperienze-esclusive' => 20,
        // ];

        // foreach ($mapping as $slug => $prestashopId) {
        //     DB::table('categories')
        //         ->where('slug', $slug)
        //         ->update(['prestashop_category_id' => $prestashopId]);
        // }
    }

    public function down(): void
    {
        // DB::table('categories')
        //     ->update(['prestashop_category_id' => null]);
    }
};