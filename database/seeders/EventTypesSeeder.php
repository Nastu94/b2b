<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventType;
use Illuminate\Support\Str;

class EventTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disattiva tutti gli event types esistenti
        EventType::query()->update(['is_active' => false]);

        $eventTypes = [
            'Matrimonio',
            'Compleanno Bambini',
            'Compleanno Adulti',
            '18 Anni',
            'Festa di Laurea',
            'Addio al Celibato',
            'Addio al Nubilato',
            'Evento Aziendale',
            'Cena di Gala',
            'Lancio Prodotto',
            'Festa Privata',
            'Festa in Barca',
            'Evento Pubblico',
            'Team Building',
            'Evento in Location',
            'Battesimo',
            'Comunione',
            'Cresima',
        ];

        foreach ($eventTypes as $name) {
            EventType::updateOrCreate(
                ['name' => $name],
                [
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Event Types attivati con successo (18 record).');
    }
}
