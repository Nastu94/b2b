<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        DB::transaction(function () use ($commissions) {
            $categories = DB::table('categories')->get();
            $unmapped = [];
            foreach ($categories as $cat) {
                if (!array_key_exists($cat->slug, $commissions)) {
                    $unmapped[] = $cat->slug;
                }
            }

            if (!empty($unmapped)) {
                // Check if any COMMISSION vendor uses these unmapped categories
                $affectedVendors = DB::table('vendor_accounts')
                    ->where('payment_model', 'COMMISSION')
                    ->whereNull('custom_commission_rate')
                    ->whereIn('category_id', function ($query) use ($unmapped) {
                        $query->select('id')->from('categories')->whereIn('slug', $unmapped);
                    })
                    ->count();

                if ($affectedVendors > 0) {
                    throw new \RuntimeException("Esistono categorie non mappate (" . implode(', ', $unmapped) . ") usate da vendor in regime COMMISSION senza override. Impossibile procedere.");
                }
            }

            foreach ($commissions as $slug => $rate) {
                DB::table('categories')
                    ->where('slug', $slug)
                    ->where(function($query) {
                        $query->whereNull('commission_rate')
                              ->orWhere('commission_rate', 0.00);
                    })
                    ->update(['commission_rate' => $rate]);
            }
        });
    }

    public function down(): void
    {
        // Migrazione irreversibile. Valori preesistenti non recuperabili.
    }
};
