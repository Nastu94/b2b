<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Offering;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfferingsSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Animazione Bambini' => [
                'Animatore / Truccabimbi',
                'Sculture di palloncini',
                'Baby dance e giochi di gruppo',
                'Caccia al tesoro',
                'Mascotte a tema',
                'Spettacolo di magia',
                'Teatro dei burattini',
                'Laboratori creativi (slime, cucina, arte)',
                'Feste a tema personalizzate',
                'Mini escape room',
                'Gonfiabili e strutture ludiche',
                'Bolle giganti e spettacoli interattivi',
            ],
            'Animazione Teen Party' => [
                'DJ set con animatore',
                'Karaoke',
                'Silent disco',
                'Schiuma party',
                'Glow / Neon party',
                'Pool party',
                'Talent show',
                'Laser game',
                'Tornei PlayStation e Just Dance',
            ],
            'Animazione Adulti - Feste Private' => [
                'DJ set personalizzato',
                'Live band',
                'Sax / Violino elettrico con DJ',
                'Percussionista live',
                'Spogliarellista uomo/donna',
                'Burlesque',
                'Drag queen show',
                'Cabaret e mentalista',
                'Danza del ventre',
                'Casino night',
                'Cena con delitto',
            ],
            'Addio al Celibato / Nubilato' => [
                'Spogliarellista personalizzato',
                'Show su misura per festeggiato/a',
                'Limousine / Limobus / Bus inglese',
                'Luxury bus',
                'Yacht party',
                'Cena con spettacolo',
                'Apericena + show',
                'Caccia al tesoro urbana',
                'Corso pole dance / lap dance',
                'Corso di cocktail',
            ],
            'Eventi Aziendali' => [
                'Presentatore / Speaker',
                'DJ corporate',
                'Live band elegante',
                'Performer LED',
                'Magician corporate',
                'Team building (cooking challenge, escape room, quiz)',
                'Photo booth / 360° booth',
                'Simulatori VR / F1',
                'Allestimenti personalizzati',
            ],
            'Compleanni Adulti' => [
                'DJ set',
                'Karaoke party',
                'Pool party',
                'Dinner show',
                'Party in villa',
                'Noleggio sala privata',
            ],
            'Matrimoni ed Eventi Eleganti' => [
                'DJ matrimonio',
                'Musica live cerimonia',
                'Animazione bambini matrimonio',
                'Effetti speciali (fumo basso, fontane fredde)',
                'Sparkular',
                'Led wall',
                'Open bar show',
            ],
            'Servizi di Supporto' => [
                'Noleggio impianto audio',
                'Noleggio luci',
                'Noleggio palco',
                'Allestimenti scenografici',
                'Effetti speciali (neve, fumo, bolle)',
                'Torte personalizzate',
                'Catering',
                'Security',
                'Hostess e steward',
            ],
            'Format Premium / Esperienze Esclusive' => [
                'Party in villa privata',
                'Party su yacht',
                'Rooftop party',
                'Beach party',
                'Evento in discoteca riservata',
                'Secret party location',
            ],
        ];

        foreach ($catalog as $categoryName => $offers) {
            $category = Category::where('slug', Str::slug($categoryName))->firstOrFail();

            $sort = 10;

            foreach ($offers as $offerName) {
                $slug = Str::slug($categoryName . ' ' . $offerName);

                Offering::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $offerName,
                        'is_active' => true,
                        'sort_order' => $sort,
                    ]
                );

                $sort += 10;
            }
        }
    }
}