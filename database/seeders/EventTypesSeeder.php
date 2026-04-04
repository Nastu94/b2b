<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creazione Event Types base (Globali)...');

        \Illuminate\Support\Facades\DB::table('event_types')->delete();

        $types = [
            'Battesimo',
            'Comunione',
            'Cresima',
            'Matrimonio',
            'Nozze d\'Argento/Oro',
            'Festa di Compleanno Bambini',
            'Festa di Compleanno Adulti',
            '18 Anni',
            'Festa di Laurea',
            'Addio al Celibato',
            'Addio al Nubilato',
            'Festa Aziendale',
            'Cena di Gala',
            'Lancio Prodotto',
            'Festa in Barca',
            'Evento in Piazza',
            'Festa Privata (Generica)',
        ];

        foreach ($types as $type) {
            EventType::firstOrCreate(
                ['name' => $type],
                ['is_active' => true]
            );
        }
        
        $this->command->info('Event Types base globali creati con successo.');
    }
}
