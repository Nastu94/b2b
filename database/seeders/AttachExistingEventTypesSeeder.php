<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\EventType;

class AttachExistingEventTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Collegamento Event Types alle Offerings in corso...');

        // Mappa delle associazioni: Category Slug -> Array di Event Type Names
        $mapping = [
            'artisti-e-performer' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Evento Aziendale', 'Cena di Gala', 'Festa Privata', 'Evento Pubblico'],
            'spettacoli-per-adulti' => ['Addio al Celibato', 'Addio al Nubilato', 'Compleanno Adulti', '18 Anni', 'Festa Privata'],
            'hostess-modelle-e-promoter' => ['Evento Aziendale', 'Lancio Prodotto', 'Cena di Gala', 'Evento Pubblico', 'Matrimonio'],
            'fotografia-e-video' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Evento Aziendale', 'Lancio Prodotto', 'Cena di Gala', 'Festa Privata'],
            'location' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Addio al Celibato', 'Addio al Nubilato', 'Evento Aziendale', 'Cena di Gala', 'Lancio Prodotto', 'Festa Privata', 'Evento in Location'],
            'food-beverage' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Evento Aziendale', 'Cena di Gala', 'Festa Privata', 'Evento in Location'],
            'trasporti-e-noleggi' => ['Matrimonio', 'Addio al Celibato', 'Addio al Nubilato', 'Evento Aziendale', 'Cena di Gala', 'Festa Privata', 'Festa in Barca'],
            'allestimenti-e-service' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Evento Aziendale', 'Cena di Gala', 'Lancio Prodotto', 'Evento Pubblico', 'Evento in Location'],
            'organizzazione-eventi' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Addio al Celibato', 'Addio al Nubilato', 'Evento Aziendale', 'Cena di Gala', 'Lancio Prodotto', 'Festa Privata', 'Team Building', 'Evento in Location'],
            'esperienze-e-attivita' => ['Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Addio al Celibato', 'Addio al Nubilato', 'Evento Aziendale', 'Team Building', 'Festa Privata'],
            'benessere-e-beauty' => ['Matrimonio', 'Compleanno Adulti', '18 Anni', 'Addio al Nubilato', 'Cena di Gala', 'Festa Privata'],
            'servizi-professionali' => ['Matrimonio', 'Evento Aziendale', 'Cena di Gala', 'Lancio Prodotto', 'Evento Pubblico', 'Evento in Location'],
        ];

        // Mappa EventType Name -> ID
        $eventTypesMap = EventType::where('is_active', true)->pluck('id', 'name')->toArray();

        $vendorsUpdated = 0;
        $vendors = \App\Models\VendorAccount::with('category')->get();

        foreach ($vendors as $vendor) {
            $categorySlug = $vendor->category?->slug;
            
            if (!$categorySlug || !isset($mapping[$categorySlug])) {
                continue;
            }

            // Otteniamo gli ID degli event types validi per questa categoria
            $eventTypeIdsToSync = [];
            foreach ($mapping[$categorySlug] as $name) {
                if (isset($eventTypesMap[$name])) {
                    $eventTypeIdsToSync[] = $eventTypesMap[$name];
                }
            }

            $vendor->eventTypes()->sync($eventTypeIdsToSync);
            $vendorsUpdated++;
        }

        $this->command->info("Aggiornati i collegamenti EventType per {$vendorsUpdated} vendor.");
    }
}