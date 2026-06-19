<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\VendorAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoVendorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creazione/Aggiornamento Demo Vendors (Provincia di Bari)...');

        $demoVendors = [
            [
                'name' => 'Bari DJ Experience',
                'email' => 'demo.dj@partylegacy.it',
                'category_slug' => 'artisti-e-performer',
                'offering' => 'DJ',
                'city' => 'Bari',
                'postal_code' => '70121',
                'address' => 'Via Sparano 100',
                'latitude' => 41.1253,
                'longitude' => 16.8667,
            ],
            [
                'name' => 'Bari Burlesque Show',
                'email' => 'demo.burlesque@partylegacy.it',
                'category_slug' => 'spettacoli-per-adulti',
                'offering' => 'Burlesque',
                'city' => 'Molfetta',
                'postal_code' => '70056',
                'address' => 'Corso Umberto I 25',
                'latitude' => 41.2012,
                'longitude' => 16.5984,
            ],
            [
                'name' => 'Hostess Elite Bari',
                'email' => 'demo.hostess@partylegacy.it',
                'category_slug' => 'hostess-modelle-e-promoter',
                'offering' => 'Hostess eventi',
                'city' => 'Modugno',
                'postal_code' => '70026',
                'address' => 'Piazza Sedile 5',
                'latitude' => 41.0843,
                'longitude' => 16.7837,
            ],
            [
                'name' => 'Studio Foto Eventi Bari',
                'email' => 'demo.fotografia@partylegacy.it',
                'category_slug' => 'fotografia-e-video',
                'offering' => 'Fotografi',
                'city' => 'Bitonto',
                'postal_code' => '70032',
                'address' => 'Piazza Cavour 10',
                'latitude' => 41.1101,
                'longitude' => 16.6900,
            ],
            [
                'name' => 'Villa Aurora Events',
                'email' => 'demo.location@partylegacy.it',
                'category_slug' => 'location',
                'offering' => 'Ville',
                'city' => 'Polignano a Mare',
                'postal_code' => '70044',
                'address' => 'Via Roma 33',
                'latitude' => 40.9958,
                'longitude' => 17.2194,
            ],
            [
                'name' => 'Mediterranean Catering',
                'email' => 'demo.catering@partylegacy.it',
                'category_slug' => 'food-beverage',
                'offering' => 'Catering',
                'city' => 'Monopoli',
                'postal_code' => '70043',
                'address' => 'Piazza Garibaldi 12',
                'latitude' => 40.9525,
                'longitude' => 17.2986,
            ],
            [
                'name' => 'Luxury Limousine Bari',
                'email' => 'demo.limousine@partylegacy.it',
                'category_slug' => 'trasporti-e-noleggi',
                'offering' => 'Limousine',
                'city' => 'Bari',
                'postal_code' => '70124',
                'address' => 'Via Amendola 170',
                'latitude' => 41.1036,
                'longitude' => 16.8797,
            ],
            [
                'name' => 'Service Audio Pro Bari',
                'email' => 'demo.service@partylegacy.it',
                'category_slug' => 'allestimenti-e-service',
                'offering' => 'Service audio',
                'city' => 'Triggiano',
                'postal_code' => '70019',
                'address' => 'Via Casalino 20',
                'latitude' => 41.0658,
                'longitude' => 16.9237,
            ],
            [
                'name' => 'Event Planner Bari',
                'email' => 'demo.planner@partylegacy.it',
                'category_slug' => 'organizzazione-eventi',
                'offering' => 'Event Planner',
                'city' => 'Conversano',
                'postal_code' => '70014',
                'address' => 'Corso Domenico Morea 30',
                'latitude' => 40.9684,
                'longitude' => 17.1132,
            ],
            [
                'name' => 'Murgia Adventure Tour',
                'email' => 'demo.tour@partylegacy.it',
                'category_slug' => 'esperienze-e-attivita',
                'offering' => 'Tour',
                'city' => 'Altamura',
                'postal_code' => '70022',
                'address' => 'Piazza Duomo 8',
                'latitude' => 40.8266,
                'longitude' => 16.5495,
            ],
            [
                'name' => 'Beauty Bridal Team',
                'email' => 'demo.beauty@partylegacy.it',
                'category_slug' => 'benessere-e-beauty',
                'offering' => 'Make-up artist',
                'city' => 'Putignano',
                'postal_code' => '70017',
                'address' => 'Corso Umberto I 40',
                'latitude' => 40.8511,
                'longitude' => 17.1210,
            ],
            [
                'name' => 'Security Event Control',
                'email' => 'demo.security@partylegacy.it',
                'category_slug' => 'servizi-professionali',
                'offering' => 'Sicurezza privata',
                'city' => 'Gioia del Colle',
                'postal_code' => '70023',
                'address' => 'Piazza Plebiscito 15',
                'latitude' => 40.7997,
                'longitude' => 16.9220,
            ]
        ];

        foreach ($demoVendors as $data) {
            $category = Category::where('slug', $data['category_slug'])->first();
            if (!$category) {
                $this->command->warn("Categoria non trovata per il vendor {$data['name']} ({$data['category_slug']}). Skipping.");
                continue;
            }

            // 1. User
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                ]
            );

            // Assegna ruolo e permessi se non li ha
            if (!$user->hasRole('vendor')) {
                $user->assignRole('vendor');
            }
            if (!$user->hasPermissionTo('vendor.access')) {
                $user->givePermissionTo('vendor.access');
            }

            // 2. Vendor Account
            $vendor = VendorAccount::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'category_id' => $category->id,
                    'company_name' => $data['name'],
                    'account_type' => 'company',
                    'status' => 'ACTIVE',
                    'legal_country' => 'IT',
                    'legal_region' => 'Puglia',
                    'legal_city' => $data['city'],
                    'legal_postal_code' => $data['postal_code'],
                    'legal_address_line1' => $data['address'],
                    'legal_lat' => $data['latitude'],
                    'legal_lng' => $data['longitude'],
                    'operational_same_as_legal' => true,
                    'operational_country' => 'IT',
                    'operational_region' => 'Puglia',
                    'operational_city' => $data['city'],
                    'operational_postal_code' => $data['postal_code'],
                    'operational_address_line1' => $data['address'],
                    'operational_lat' => $data['latitude'],
                    'operational_lng' => $data['longitude'],
                ]
            );

            // 3. Offering & Profile
            if (!empty($data['offering'])) {
                $offering = \App\Models\Offering::where('category_id', $category->id)
                    ->where('name', $data['offering'])
                    ->first();

                if ($offering) {
                    $vendor->offerings()->syncWithoutDetaching([
                        $offering->id => ['is_active' => true]
                    ]);

                    \App\Models\VendorOfferingProfile::updateOrCreate(
                        [
                            'vendor_account_id' => $vendor->id,
                            'offering_id' => $offering->id,
                        ],
                        [
                            'title' => 'Servizio ' . $data['offering'],
                            'service_mode' => 'MOBILE',
                            'is_published' => true,
                            'is_approved' => true,
                            'short_description' => 'Servizio premium offerto da ' . $data['name'],
                        ]
                    );
                } else {
                    $this->command->warn("Offering '{$data['offering']}' non trovato per il vendor {$data['name']}.");
                }
            }
            // 4. Slots e Weekly Schedule
            $slotsData = [
                ['slug' => 'mattina', 'label' => 'Mattina', 'start' => '09:00:00', 'end' => '13:00:00', 'sort' => 2],
                ['slug' => 'pomeriggio', 'label' => 'Pomeriggio', 'start' => '14:00:00', 'end' => '20:00:00', 'sort' => 3],
                ['slug' => 'sera', 'label' => 'Sera', 'start' => '20:00:00', 'end' => '23:00:00', 'sort' => 4],
            ];

            foreach ($slotsData as $slotData) {
                $slot = \App\Models\VendorSlot::updateOrCreate(
                    [
                        'vendor_account_id' => $vendor->id,
                        'slug' => $slotData['slug']
                    ],
                    [
                        'label' => $slotData['label'],
                        'start_time' => $slotData['start'],
                        'end_time' => $slotData['end'],
                        'sort_order' => $slotData['sort'],
                        'is_active' => true,
                    ]
                );

                for ($day = 0; $day <= 6; $day++) {
                    \App\Models\VendorWeeklySchedule::updateOrCreate(
                        [
                            'vendor_account_id' => $vendor->id,
                            'vendor_slot_id' => $slot->id,
                            'day_of_week' => $day,
                        ],
                        [
                            'is_open' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('Demo Vendors creati/aggiornati con successo!');
    }
}