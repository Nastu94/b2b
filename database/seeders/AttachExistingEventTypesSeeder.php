<?php

namespace Database\Seeders;

use App\Models\VendorAccount;
use App\Models\EventType;
use Illuminate\Database\Seeder;

class AttachExistingEventTypesSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'animazione-bambini' => ['Battesimo', 'Comunione', 'Cresima', 'Festa di Compleanno Bambini'],
            'giochi-e-intrattenimento' => ['Festa di Compleanno Bambini', 'Festa di Compleanno Adulti', '18 Anni', 'Evento in Piazza'],
            'animazione-adulti-feste-private' => ['Festa di Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Festa Privata (Generica)', 'Addio al Celibato', 'Addio al Nubilato'],
            'addio-al-celibato-nubilato' => ['Addio al Celibato', 'Addio al Nubilato'],
            'eventi-aziendali' => ['Festa Aziendale', 'Cena di Gala', 'Lancio Prodotto'],
            'compleanni-adulti' => ['Festa di Compleanno Adulti', '18 Anni', 'Festa di Laurea', 'Festa Privata (Generica)'],
            'matrimoni-ed-eventi-eleganti' => ['Matrimonio', 'Nozze d\'Argento/Oro', 'Cena di Gala'],
            'servizi-di-supporto' => ['Matrimonio', 'Evento in Piazza', 'Festa Aziendale', 'Lancio Prodotto'],
            'format-premium-esperienze-esclusive' => ['Addio al Celibato', 'Addio al Nubilato', 'Festa Privata (Generica)', '18 Anni', 'Festa in Barca'],
            'artisti' => ['Battesimo', 'Comunione', 'Cresima', '18 Anni', 'Festa di Laurea', 'Festa Privata (Generica)', 'Matrimonio', 'Nozze d\'Argento/Oro', 'Festa Aziendale', 'Cena di Gala'],
            'ristoranti' => ['Battesimo', 'Comunione', 'Cresima', 'Matrimonio', 'Nozze d\'Argento/Oro', 'Festa di Compleanno Adulti', 'Festa Aziendale', 'Cena di Gala', 'Festa Privata (Generica)'],
        ];

        $vendors = VendorAccount::with('category')->get();
        $updated = 0;
        $skipped = 0;

        foreach ($vendors as $vendor) {
            $slug = $vendor->category?->slug;

            if (!$slug || !isset($map[$slug])) {
                $skipped++;
                continue;
            }

            $ids = EventType::whereIn('name', $map[$slug])->pluck('id')->toArray();

            if (!empty($ids)) {
                $vendor->eventTypes()->sync($ids);
                $updated++;
            }
        }

        $this->command->info("Aggiornati {$updated} vendor con event types per categoria.");
        
        if ($skipped > 0) {
            $this->command->warn("Saltati {$skipped} vendor senza categoria mappata.");
        }
    }
}