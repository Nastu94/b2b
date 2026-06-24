<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Offering;
use Illuminate\Support\Str;

class OfferingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disattiva tutte le offering esistenti per non creare conflitti
        Offering::query()->update(['is_active' => false]);

        $offeringsData = [
            'artisti-e-performer' => [
                'DJ', 'Cantanti', 'Musicisti', 'Band', 'Animatori', 'Cabarettisti', 'Maghi', 'Artisti di strada'
            ],
            'spettacoli-per-adulti' => [
                'Spogliarellisti', 'Spogliarelliste', 'Burlesque', 'Pole Dance', 'Intrattenimento per addii al celibato e nubilato'
            ],
            'hostess-modelle-e-promoter' => [
                'Hostess eventi', 'Steward', 'Modelle', 'Promoter'
            ],
            'fotografia-e-video' => [
                'Fotografi', 'Videomaker', 'Drone operator', 'Photo booth', '360 booth'
            ],
            'location' => [
                'Ville', 'Sale eventi', 'Loft', 'Rooftop', 'Agriturismi', 'Hotel', 'Discoteche', 'Beach club'
            ],
            'food-beverage' => [
                'Catering', 'Chef privati', 'Bartender', 'Open bar', 'Cake designer'
            ],
            'trasporti-e-noleggi' => [
                'NCC', 'Limousine', 'Party Bus', 'Auto sportive', "Auto d'epoca", 'Yacht e barche'
            ],
            'allestimenti-e-service' => [
                'Service audio', 'Service luci', 'Ledwall', 'Palchi', 'Decorazioni', 'Balloon art', 'Flower design'
            ],
            'organizzazione-eventi' => [
                'Event Planner', 'Wedding Planner', 'Party Planner', 'Coordinatori eventi'
            ],
            'esperienze-e-attivita' => [
                'Tour', 'Escursioni', 'Attività sportive', 'Team building', 'Esperienze adrenaliniche'
            ],
            'benessere-e-beauty' => [
                'Make-up artist', 'Hair stylist', 'Estetiste', 'Massaggiatori'
            ],
            'servizi-professionali' => [
                'Sicurezza privata', 'Vigilanza', 'Traduttori', 'Personale di supporto'
            ]
        ];

        $totalAdded = 0;

        foreach ($offeringsData as $categorySlug => $offerings) {
            $category = Category::where('slug', $categorySlug)->first();
            
            if (!$category) {
                $this->command->warn("Categoria non trovata: {$categorySlug}");
                continue;
            }

            foreach ($offerings as $index => $offeringName) {
                $offeringSlug = Str::slug($offeringName);
                $fullSlug = "{$categorySlug}-{$offeringSlug}";

                Offering::updateOrCreate(
                    ['slug' => $fullSlug],
                    [
                        'category_id' => $category->id,
                        'name' => $offeringName,
                        'is_active' => true,
                        'sort_order' => ($index + 1) * 10,
                        'status' => Offering::STATUS_APPROVED,
                        'is_custom' => false,
                    ]
                );
                
                $totalAdded++;
            }
        }

        $this->command->info("Creati/Aggiornati {$totalAdded} offerings attivi.");
    }
}