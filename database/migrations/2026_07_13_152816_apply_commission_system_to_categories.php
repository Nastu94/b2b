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

        $defaultCommissionRate = 20;

        DB::transaction(function () use ($commissions, $defaultCommissionRate) {
            $categories = DB::table('categories')
                ->where(function ($query) {
                    $query->whereNull('commission_rate')
                        ->orWhere('commission_rate', '<=', 0);
                })
                ->get();

            foreach ($categories as $category) {
                if (!array_key_exists($category->slug, $commissions)) {
                    \Illuminate\Support\Facades\Log::info("Categoria usa commissione default", [
                        'category' => $category->slug,
                        'rate' => $defaultCommissionRate
                    ]);
                }

                $rate = $commissions[$category->slug] ?? $defaultCommissionRate;

                DB::table('categories')
                    ->where('id', $category->id)
                    ->where(function ($query) {
                        $query->whereNull('commission_rate')
                            ->orWhere('commission_rate', '<=', 0);
                    })
                    ->update([
                        'commission_rate' => $rate
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Migrazione irreversibile. Valori preesistenti non recuperabili.
    }
};
