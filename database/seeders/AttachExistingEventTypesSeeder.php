<?php

namespace Database\Seeders;

use App\Models\EventType;
use App\Models\VendorAccount;
use Illuminate\Database\Seeder;

class AttachExistingEventTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Collegamento Event Types ai Vendor in corso...');

        $mapping = [
            'artisti-e-performer' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Evento Aziendale', 'Cena di Gala', 'Festa Privata', 'Evento Pubblico'],
            'spettacoli-per-adulti' => ['Addio al Celibato', 'Addio al Nubilato', 'Compleanno Adulti', '18 Anni', 'Festa Privata'],
            'hostess-modelle-e-promoter' => ['Evento Aziendale', 'Lancio Prodotto', 'Cena di Gala', 'Evento Pubblico', 'Matrimonio'],
            'fotografia-e-video' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Evento Aziendale', 'Lancio Prodotto', 'Cena di Gala', 'Festa Privata'],
            'location' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Addio al Celibato', 'Addio al Nubilato', 'Evento Aziendale', 'Cena di Gala', 'Lancio Prodotto', 'Festa Privata', 'Evento in Location'],
            'food-beverage' => null,
            'trasporti-e-noleggi' => ['Matrimonio', 'Addio al Celibato', 'Addio al Nubilato', 'Evento Aziendale', 'Cena di Gala', 'Festa Privata', 'Festa in Barca', '18 Anni'],
            'allestimenti-e-service' => ['Matrimonio', 'Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Evento Aziendale', 'Cena di Gala', 'Lancio Prodotto', 'Evento Pubblico', 'Evento in Location'],
            'organizzazione-eventi' => null,
            'esperienze-e-attivita' => ['Compleanno Bambini', 'Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Addio al Celibato', 'Addio al Nubilato', 'Evento Aziendale', 'Team Building', 'Festa Privata'],
            'benessere-e-beauty' => ['Matrimonio', 'Compleanno Adulti', '18 Anni', 'Addio al Nubilato', 'Cena di Gala', 'Festa Privata'],
            'servizi-professionali' => ['Matrimonio', 'Evento Aziendale', 'Cena di Gala', 'Lancio Prodotto', 'Evento Pubblico', 'Evento in Location', 'Team Building', 'Festa Privata'],
        ];

        $activeEventTypesByName = EventType::query()
            ->where('is_active', true)
            ->pluck('id', 'name')
            ->toArray();

        $allActiveEventTypeIds = array_values($activeEventTypesByName);

        $vendorsUpdated = 0;

        $vendors = VendorAccount::query()
            ->with('category')
            ->get();

        foreach ($vendors as $vendor) {
            $categorySlug = $vendor->category?->slug;

            if (! $categorySlug || ! array_key_exists($categorySlug, $mapping)) {
                continue;
            }

            $eventTypeIds = [];

            if ($mapping[$categorySlug] === null) {
                $eventTypeIds = $allActiveEventTypeIds;
            } else {
                foreach ($mapping[$categorySlug] as $eventTypeName) {
                    if (isset($activeEventTypesByName[$eventTypeName])) {
                        $eventTypeIds[] = $activeEventTypesByName[$eventTypeName];
                    }
                }
            }

            if (empty($eventTypeIds)) {
                $this->command->warn("Nessun event type attivo trovato per vendor {$vendor->company_name}");
                continue;
            }

            $vendor->eventTypes()->syncWithoutDetaching($eventTypeIds);

            $vendorsUpdated++;
        }

        $this->command->info("Aggiornati i collegamenti EventType per {$vendorsUpdated} vendor.");
    }
}