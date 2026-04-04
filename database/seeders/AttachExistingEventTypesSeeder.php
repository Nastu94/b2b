<?php

namespace Database\Seeders;

use App\Models\VendorAccount;
use App\Models\EventType;
use Illuminate\Database\Seeder;

class AttachExistingEventTypesSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = VendorAccount::all();
        $count = 0;

        // Recuperiamo tutti gli ID degli EventTypes (ora sono globali)
        $allEventTypes = EventType::pluck('id')->toArray();

        foreach ($vendors as $vendor) {
            if (!empty($allEventTypes)) {
                // Usiamo syncWithoutDetaching per agganciare in modo safe nella tabella pivot 
                // senza far partire i Job di aggiornamento/creazione prodotto per PrestaShop (evita doppioni)
                $vendor->eventTypes()->syncWithoutDetaching($allEventTypes);
                $count++;
            }
        }

        $this->command->info("Aggiornati {$count} vendor assegnando tutti i Tipi di Evento globali di default.");
    }
}
