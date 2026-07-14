<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $maxRate = config('bookingbridge.commission.maximum_rate');
        if ($maxRate === null || !is_numeric($maxRate) || $maxRate <= 0) {
            throw new \RuntimeException("Configurazione limite commerciale commissioni mancante o errata.");
        }

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

        \Illuminate\Support\Facades\DB::transaction(function () use ($commissions, $maxRate) {
            // Preflight 1: override null/zero/negativo/oltre limite su vendor COMMISSION (check validità override se presenti)
            $badOverrides = \Illuminate\Support\Facades\DB::table('vendor_accounts')
                ->where('payment_model', 'COMMISSION')
                ->whereNotNull('custom_commission_rate')
                ->where(function($q) use ($maxRate) {
                    $q->where('custom_commission_rate', '<=', 0)
                      ->orWhere('custom_commission_rate', '>', $maxRate);
                })->exists();

            if ($badOverrides) {
                throw new \RuntimeException("Preflight Fallito: Trovati vendor con override commissione <= 0 o > $maxRate.");
            }
            
            // Preflight 2: categorie fuori range
            $badCats = \Illuminate\Support\Facades\DB::table('categories')
                ->where(function($q) use ($maxRate) {
                    $q->where('commission_rate', '<', 0)
                      ->orWhere('commission_rate', '>', $maxRate);
                })->exists();
                
            if ($badCats) {
                throw new \RuntimeException("Preflight Fallito: Trovate categorie con commission_rate < 0 o > $maxRate.");
            }
            
            // Aggiornamenti: mappare solo slug conosciuti, non sovrascrivere rate positive valide
            foreach ($commissions as $slug => $rate) {
                if ($rate > $maxRate) {
                    throw new \RuntimeException("Il rate hardcoded per $slug supera il massimo configurato.");
                }
                
                \Illuminate\Support\Facades\DB::table('categories')
                    ->where('slug', $slug)
                    ->where(function($query) {
                        $query->whereNull('commission_rate')
                              ->orWhere('commission_rate', '<=', 0);
                    })
                    ->update(['commission_rate' => $rate]);
            }

            // Verifica Finale: ogni vendor COMMISSION deve avere override valido o rate categoria valido
            $invalidVendorsCount = \Illuminate\Support\Facades\DB::table('vendor_accounts')
                ->leftJoin('categories', 'vendor_accounts.category_id', '=', 'categories.id')
                ->where('payment_model', 'COMMISSION')
                ->where(function ($q) use ($maxRate) {
                    $q->where(function ($q2) use ($maxRate) {
                        $q2->whereNull('custom_commission_rate')
                           ->orWhere('custom_commission_rate', '<=', 0)
                           ->orWhere('custom_commission_rate', '>', $maxRate);
                    })->where(function ($q3) use ($maxRate) {
                        $q3->whereNull('categories.commission_rate')
                           ->orWhere('categories.commission_rate', '<=', 0)
                           ->orWhere('categories.commission_rate', '>', $maxRate);
                    });
                })->count();

            if ($invalidVendorsCount > 0) {
                throw new \RuntimeException("Verifica Finale Fallita: $invalidVendorsCount vendor in regime COMMISSION non hanno un rate valido (né override né categoria).");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrazione irreversibile. Valori preesistenti non recuperabili.
    }
};
