<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Offering;
use App\Models\SlotLock;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorBlackout;
use App\Models\VendorLeadTime;
use App\Models\VendorOfferingPricing;
use App\Models\VendorOfferingPricingRule;
use App\Models\VendorOfferingProfile;
use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoVendorsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('PULIZIA COMPLETA DATABASE VENDOR...');

        $demoVendorEmails = [
            'mario.rossi@partylegacy.it',
            'luca.bianchi@partylegacy.it',
            'giuseppe.verdi@partylegacy.it',
            'antonio.greco@partylegacy.it',
            'francesca.romano@partylegacy.it',
            'alessandro.ferrara@partylegacy.it',
            'valentina.esposito@partylegacy.it',
            'marco.santoro@partylegacy.it',
            'claudia.marino@partylegacy.it',
            'david.guetta@partylegacy.it',
            'ristorante.baia@partylegacy.it',
            'all.you.can.eat@partylegacy.it',
        ];

        $demoVendorUserIds = User::whereIn('email', $demoVendorEmails)->pluck('id')->toArray();
        $demoVendorIds = VendorAccount::withTrashed()->whereIn('user_id', $demoVendorUserIds)->pluck('id')->toArray();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('Cancellazione pricing rules...');
        if (!empty($demoVendorIds)) {
            $pricingIds = VendorOfferingPricing::whereIn('vendor_account_id', $demoVendorIds)
                ->pluck('id')
                ->toArray();

            if (!empty($pricingIds)) {
                VendorOfferingPricingRule::whereIn('vendor_offering_pricing_id', $pricingIds)->delete();
            }
        }

        $this->command->info('Cancellazione vendor pricings...');
        if (!empty($demoVendorIds)) {
            VendorOfferingPricing::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione bookings...');
        if (!empty($demoVendorIds)) {
            Booking::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione vendor offering profiles...');
        if (!empty($demoVendorIds)) {
            VendorOfferingProfile::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione vendor offerings (pivot)...');
        if (!empty($demoVendorIds)) {
            DB::table('vendor_offerings')->whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione vendor event types (pivot)...');
        if (!empty($demoVendorIds)) {
            DB::table('event_type_vendor_account')->whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione slot locks...');
        if (!empty($demoVendorIds)) {
            SlotLock::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione blackouts...');
        if (!empty($demoVendorIds)) {
            VendorBlackout::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione lead times...');
        if (!empty($demoVendorIds)) {
            VendorLeadTime::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione weekly schedules...');
        if (!empty($demoVendorIds)) {
            VendorWeeklySchedule::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione vendor slots...');
        if (!empty($demoVendorIds)) {
            VendorSlot::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione vendor accounts...');
        if (!empty($demoVendorIds)) {
            // Forziamo il physical delete per evitare che cloni SoftDeleted saturino l'ambiente demo
            VendorAccount::withTrashed()->whereIn('id', $demoVendorIds)->forceDelete();
        }

        $this->command->info('Cancellazione users vendor demo...');
        $deletedUsers = DB::table('users')->whereIn('email', $demoVendorEmails)->delete();
        $this->command->info("Cancellati {$deletedUsers} users vendor");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Database pulito (admin protetto)');
        $this->command->info('Creazione vendor demo con offering coerenti, modalità realistiche e pricing demo...');

        $vendors = [
            [
                'category_slug' => 'animazione-bambini',
                'user_name' => 'Mario Rossi',
                'email' => 'mario.rossi@partylegacy.it',
                'company_name' => 'Feste Magiche SRL',
                'vat_number' => 'IT07123456789',
                'tax_code' => 'RSSMRA75D15F205X',
                'phone' => '+39 080 123 4567',
                'city' => 'Bari',
                'address' => 'Via Sparano 100',
                'postal_code' => '70121',
                'province' => 'BA',
                'latitude' => 41.1171,
                'longitude' => 16.8719,
            ],
            [
                'category_slug' => 'giochi-e-intrattenimento',
                'user_name' => 'Luca Bianchi',
                'email' => 'luca.bianchi@partylegacy.it',
                'company_name' => 'Teen Party Pro SNC',
                'vat_number' => 'IT07234567890',
                'tax_code' => 'BNCLCU82A10A662F',
                'phone' => '+39 0832 234 567',
                'city' => 'Lecce',
                'address' => 'Piazza Sant\'Oronzo 15',
                'postal_code' => '73100',
                'province' => 'LE',
                'latitude' => 40.3515,
                'longitude' => 18.1750,
            ],
            [
                'category_slug' => 'animazione-adulti-feste-private',
                'user_name' => 'Giuseppe Verdi',
                'email' => 'giuseppe.verdi@partylegacy.it',
                'company_name' => 'Elite Entertainment SRL',
                'vat_number' => 'IT07345678901',
                'tax_code' => 'VRDGPP78M12L447K',
                'phone' => '+39 0881 345 678',
                'city' => 'Foggia',
                'address' => 'Corso Roma 45',
                'postal_code' => '71100',
                'province' => 'FG',
                'latitude' => 41.4621,
                'longitude' => 15.5444,
            ],
            [
                'category_slug' => 'addio-al-celibato-nubilato',
                'user_name' => 'Antonio Greco',
                'email' => 'antonio.greco@partylegacy.it',
                'company_name' => 'Addio Party SRL',
                'vat_number' => 'IT07456789012',
                'tax_code' => 'GRCNTN85C20L049B',
                'phone' => '+39 099 456 7890',
                'city' => 'Taranto',
                'address' => 'Via D\'Aquino 200',
                'postal_code' => '74123',
                'province' => 'TA',
                'latitude' => 40.4761,
                'longitude' => 17.2303,
            ],
            [
                'category_slug' => 'eventi-aziendali',
                'user_name' => 'Francesca Romano',
                'email' => 'francesca.romano@partylegacy.it',
                'company_name' => 'Corporate Events Pro SRL',
                'vat_number' => 'IT07567890123',
                'tax_code' => 'RMNFNC90D45E038M',
                'phone' => '+39 0831 567 890',
                'city' => 'Brindisi',
                'address' => 'Corso Garibaldi 88',
                'postal_code' => '72100',
                'province' => 'BR',
                'latitude' => 40.6327,
                'longitude' => 17.9369,
            ],
            [
                'category_slug' => 'compleanni-adulti',
                'user_name' => 'Alessandro Ferrara',
                'email' => 'alessandro.ferrara@partylegacy.it',
                'company_name' => 'Birthday Stars',
                'vat_number' => 'IT07678901234',
                'tax_code' => 'FRRLSN88H15A285R',
                'phone' => '+39 0883 678 901',
                'city' => 'Andria',
                'address' => 'Piazza Catuma 22',
                'postal_code' => '76123',
                'province' => 'BT',
                'latitude' => 41.2275,
                'longitude' => 16.2956,
            ],
            [
                'category_slug' => 'matrimoni-ed-eventi-eleganti',
                'user_name' => 'Valentina Esposito',
                'email' => 'valentina.esposito@partylegacy.it',
                'company_name' => 'Wedding Dreams SRL',
                'vat_number' => 'IT07789012345',
                'tax_code' => 'SPSVNT92B50H501L',
                'phone' => '+39 080 789 0123',
                'city' => 'Polignano a Mare',
                'address' => 'Via Roma 33',
                'postal_code' => '70044',
                'province' => 'BA',
                'latitude' => 40.9967,
                'longitude' => 17.2208,
            ],
            [
                'category_slug' => 'servizi-di-supporto',
                'user_name' => 'Marco Santoro',
                'email' => 'marco.santoro@partylegacy.it',
                'company_name' => 'Total Service SRL',
                'vat_number' => 'IT07890123456',
                'tax_code' => 'SNTMRC80A12F839N',
                'phone' => '+39 0831 890 1234',
                'city' => 'Ostuni',
                'address' => 'Corso Mazzini 56',
                'postal_code' => '72017',
                'province' => 'BR',
                'latitude' => 40.7306,
                'longitude' => 17.5783,
            ],
            [
                'category_slug' => 'format-premium-esperienze-esclusive',
                'user_name' => 'Claudia Marino',
                'email' => 'claudia.marino@partylegacy.it',
                'company_name' => 'Luxury Events SRL',
                'vat_number' => 'IT07901234567',
                'tax_code' => 'MRNCLD87L55F152P',
                'phone' => '+39 080 901 2345',
                'city' => 'Monopoli',
                'address' => 'Piazza Garibaldi 12',
                'postal_code' => '70043',
                'province' => 'BA',
                'latitude' => 40.9530,
                'longitude' => 17.3020,
            ],
            [
                'category_slug' => 'artisti',
                'user_name' => 'David Guetta',
                'email' => 'david.guetta@partylegacy.it',
                'company_name' => 'DJ King SRL',
                'vat_number' => 'IT07901234568',
                'tax_code' => 'GTTDVD67A01Z110R',
                'phone' => '+39 080 901 9999',
                'city' => 'Bari',
                'address' => 'Via Sparano 50',
                'postal_code' => '70121',
                'province' => 'BA',
                'latitude' => 41.1210,
                'longitude' => 16.8680,
            ],
            [
                'category_slug' => 'ristoranti',
                'user_name' => 'Luigi Neri',
                'email' => 'ristorante.baia@partylegacy.it',
                'company_name' => 'Ristorante La Baia SRL',
                'vat_number' => 'IT08012345678',
                'tax_code' => 'NRLLGI75M12H501C',
                'phone' => '+39 080 111 2222',
                'city' => 'Polignano a Mare',
                'address' => 'Lungomare Nazario Sauro 1',
                'postal_code' => '70044',
                'province' => 'BA',
                'latitude' => 40.9950,
                'longitude' => 17.2250,
            ],
            [
                'category_slug' => 'ristoranti',
                'user_name' => 'Simone Gialli',
                'email' => 'all.you.can.eat@partylegacy.it',
                'company_name' => 'Paradise Buffet SRL',
                'vat_number' => 'IT08123456789',
                'tax_code' => 'GLLSMN80A01H501A',
                'phone' => '+39 080 222 3333',
                'city' => 'Bari',
                'address' => 'Viale Unità d\'Italia 10',
                'postal_code' => '70125',
                'province' => 'BA',
                'latitude' => 41.1100,
                'longitude' => 16.8780,
            ],
        ];

        $count = 0;

        foreach ($vendors as $data) {
            $count++;
            $category = Category::where('slug', $data['category_slug'])->first();

            if (!$category) {
                $this->command->warn("Categoria '{$data['category_slug']}' non trovata");
                continue;
            }

            $user = User::create([
                'name' => $data['user_name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);

            $vendor = VendorAccount::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'account_type' => 'COMPANY',
                'company_name' => $data['company_name'],
                'vat_number' => $data['vat_number'],
                'tax_code' => $data['tax_code'] ?? null,
                'legal_entity_type' => 'SRL',
                'phone' => $data['phone'] ?? null,
                'billing_email' => $data['email'],
                'contact_name' => $data['user_name'],
                'legal_country' => 'IT',
                'legal_region' => 'Puglia',
                'legal_city' => $data['city'],
                'legal_postal_code' => $data['postal_code'],
                'legal_address_line1' => $data['address'],
                'legal_lat' => $data['latitude'],
                'legal_lng' => $data['longitude'],
                'operational_same_as_legal' => true,
                'status' => 'ACTIVE',
                'activated_at' => now(),
            ]);

            $user->assignRole('vendor');

            $this->createSlots($vendor);
            $this->createSchedule($vendor);
            $this->createLeadTime($vendor);
            $createdProfiles = $this->createVendorOfferings($vendor, $category);

            $isFixedLocation = false;
            foreach ($createdProfiles as $p) {
                if ($p->service_mode === 'FIXED_LOCATION') {
                    $isFixedLocation = true;
                    break;
                }
            }

            $this->assignEventTypes($vendor, $isFixedLocation);

            foreach ($createdProfiles as $profile) {
                $radiusLabel = $profile->service_mode === 'MOBILE'
                    ? ($profile->service_radius_km . ' km')
                    : 'n/a';

                $maxGuestsLabel = $profile->max_guests !== null
                    ? $profile->max_guests
                    : 'n/a';

                $pricing = VendorOfferingPricing::query()
                    ->where('vendor_account_id', $vendor->id)
                    ->where('offering_id', $profile->offering_id)
                    ->first();

                $basePriceLabel = $pricing ? number_format((float) $pricing->base_price, 2, ',', '.') . ' EUR' : 'n/a';

                $this->command->info(
                    "{$category->name}: {$data['company_name']} - {$profile->offering->name} - mode: {$profile->service_mode}, radius: {$radiusLabel}, max_guests: {$maxGuestsLabel}, base_price: {$basePriceLabel}"
                );
            }

            $count++;
        }

        $this->command->info("Creati {$count} vendor con offerings e pricing");
    }

    private function assignEventTypes(VendorAccount $vendor, bool $isFixedLocation): void
    {
        $categorySlug = $vendor->category->slug;

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

        $allowedNames = $map[$categorySlug] ?? [];
        
        if (empty($allowedNames)) {
            // Fallback per categorie non censite: Tutti (location) o 5 random (mobile)
            if ($isFixedLocation) {
                $eventTypeIds = \App\Models\EventType::pluck('id')->toArray();
            } else {
                $eventTypeIds = \App\Models\EventType::inRandomOrder()->take(5)->pluck('id')->toArray();
            }
        } else {
            $eventTypeIds = \App\Models\EventType::whereIn('name', $allowedNames)->pluck('id')->toArray();
        }

        if (!empty($eventTypeIds)) {
            $vendor->eventTypes()->sync($eventTypeIds);
        }
    }

    private function createSlots(VendorAccount $vendor): void
    {
        $slots = [
            ['slug' => 'morning', 'label' => 'Mattina', 'start' => '09:00:00', 'end' => '13:00:00', 'order' => 10],
            ['slug' => 'afternoon', 'label' => 'Pomeriggio', 'start' => '14:00:00', 'end' => '18:00:00', 'order' => 20],
            ['slug' => 'evening', 'label' => 'Sera', 'start' => '19:00:00', 'end' => '23:00:00', 'order' => 30],
        ];

        foreach ($slots as $slot) {
            VendorSlot::create([
                'vendor_account_id' => $vendor->id,
                'slug' => $slot['slug'],
                'label' => $slot['label'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'is_active' => true,
                'sort_order' => $slot['order'],
            ]);
        }
    }

    private function createSchedule(VendorAccount $vendor): void
    {
        $slots = VendorSlot::where('vendor_account_id', $vendor->id)->get();

        foreach ($slots as $slot) {
            for ($day = 1; $day <= 6; $day++) {
                VendorWeeklySchedule::create([
                    'vendor_account_id' => $vendor->id,
                    'vendor_slot_id' => $slot->id,
                    'day_of_week' => $day,
                    'is_open' => true,
                ]);
            }
        }
    }

    private function createLeadTime(VendorAccount $vendor): void
    {
        for ($day = 0; $day <= 6; $day++) {
            VendorLeadTime::create([
                'vendor_account_id' => $vendor->id,
                'day_of_week' => $day,
                'min_notice_hours' => 48,
                'cutoff_time' => '18:00:00',
            ]);
        }
    }

    private function createVendorOfferings(VendorAccount $vendor, Category $category): array
    {
        $definitions = $this->getOfferingDefinitionsForCategory($category->slug, $vendor->user->email);

        if (count($definitions) < 1) {
            $this->command->warn("Meno di 2 definizioni offering per categoria '{$category->slug}'");
            return [];
        }

        $descriptions = $this->getDescriptions($category->slug);
        $createdProfiles = [];

        foreach ($definitions as $index => $definition) {
            $offering = Offering::where('category_id', $category->id)
                ->where('name', $definition['offering_name'])
                ->first();

            if (!$offering) {
                $this->command->warn(
                    "Offering '{$definition['offering_name']}' non trovata per categoria '{$category->slug}'"
                );
                continue;
            }

            DB::table('vendor_offerings')->insert([
                'vendor_account_id' => $vendor->id,
                'offering_id' => $offering->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $profile = VendorOfferingProfile::create([
                'vendor_account_id' => $vendor->id,
                'offering_id' => $offering->id,
                'title' => $offering->name,
                'short_description' => $definition['short_description'] ?? ($descriptions['short'][$index] ?? 'Servizio professionale per eventi'),
                'description' => $definition['description'] ?? ($descriptions['long'][$index] ?? 'Descrizione dettagliata del servizio.'),
                'cover_image_path' => null,
                'service_mode' => $definition['service_mode'],
                'service_radius_km' => $definition['service_radius_km'],
                'max_guests' => $definition['max_guests'] ?? null,
                'is_published' => true,
            ]);

            $profile->load('offering');

            $this->createPricingForProfile($vendor, $profile, $category->slug);

            $createdProfiles[] = $profile;
        }

        $this->command->info("Creati " . count($createdProfiles) . " offering profiles");

        return $createdProfiles;
    }

    private function createPricingForProfile(VendorAccount $vendor, VendorOfferingProfile $profile, string $categorySlug): void
    {
        $basePrice = $this->resolveBasePriceForOffering(
            $categorySlug,
            $profile->offering?->name ?? ''
        );

        $pricing = VendorOfferingPricing::create([
            'vendor_account_id' => $vendor->id,
            'offering_id' => $profile->offering_id,
            'is_active' => true,
            'price_type' => 'FIXED',
            'base_price' => $basePrice,
            'currency' => 'EUR',
            'service_radius_km' => $profile->isMobileService() ? $profile->service_radius_km : null,
            'distance_pricing_mode' => $profile->isMobileService()
                ? 'NOT_AVAILABLE_OUTSIDE_RADIUS'
                : 'INCLUDED',
            'notes_internal' => 'Seeder demo',
        ]);

        $this->createWeekendSurchargeRule($pricing);
    }

    private function createWeekendSurchargeRule(VendorOfferingPricing $pricing): void
    {
        VendorOfferingPricingRule::create([
            'vendor_offering_pricing_id' => $pricing->id,
            'name' => 'Maggiorazione weekend',
            'is_active' => true,
            'priority' => 10,
            'rule_type' => 'SURCHARGE',
            'adjustment_type' => 'PERCENT',
            'adjustment_value' => 20.00,
            'override_price' => null,
            'starts_at' => null,
            'ends_at' => null,
            'weekdays' => [5, 6], // venerdì e sabato
            'min_quantity' => null,
            'max_quantity' => null,
            'is_exclusive' => false,
            'conditions' => [],
            'notes_internal' => 'Seeder demo: +20% nel weekend',
        ]);
    }

    private function resolveBasePriceForOffering(string $categorySlug, string $offeringName): float
    {
        $prices = [
            'animazione-bambini' => [
                'Animatore / Truccabimbi' => 180.00,
                'Gonfiabili e strutture ludiche' => 250.00,
            ],
            'giochi-e-intrattenimento' => [
                'Schiuma party' => 260.00,
                'Silent disco' => 320.00,
            ],
            'animazione-adulti-feste-private' => [
                'Live band' => 500.00,
                'Cena con delitto' => 700.00,
            ],
            'addio-al-celibato-nubilato' => [
                'Spogliarellista personalizzato' => 300.00,
                'Yacht party' => 1500.00,
            ],
            'eventi-aziendali' => [
                'Presentatore / Speaker' => 500.00,
                'Live band elegante' => 800.00,
            ],
            'compleanni-adulti' => [
                'Dinner show' => 450.00,
                'Noleggio sala privata' => 900.00,
            ],
            'matrimoni-ed-eventi-eleganti' => [
                'Musica live cerimonia' => 400.00,
                'Open bar show' => 1200.00,
            ],
            'servizi-di-supporto' => [
                'Noleggio palco' => 800.00,
                'Noleggio impianto audio' => 450.00,
            ],
            'format-premium-esperienze-esclusive' => [
                'Party su yacht' => 2500.00,
                'Rooftop party' => 1800.00,
            ],
            'artisti' => [
                'DJ' => 300.00,
            ],
            'ristoranti' => [
                'Menu Pesce' => 60.00,
                'Menu carne' => 45.00,
                'Catering' => 70.00,
                'Menu all you can eat' => 25.00,
            ],
        ];

        return (float) ($prices[$categorySlug][$offeringName] ?? 250.00);
    }

    private function getOfferingDefinitionsForCategory(string $categorySlug, string $vendorEmail): array
    {
        $map = [
            'animazione-bambini' => [
                [
                    'offering_name' => 'Animatore / Truccabimbi',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 25,
                    'max_guests' => null,
                ],
                [
                    'offering_name' => 'Gonfiabili e strutture ludiche',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 25,
                    'max_guests' => null,
                ],
            ],
            'giochi-e-intrattenimento' => [
                [
                    'offering_name' => 'Schiuma party',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 35,
                    'max_guests' => null,
                ],
                [
                    'offering_name' => 'Silent disco',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 35,
                    'max_guests' => null,
                ],
            ],
            'animazione-adulti-feste-private' => [
                [
                    'offering_name' => 'Live band',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 120,
                    'max_guests' => null,
                ],
                [
                    'offering_name' => 'Cena con delitto',
                    'service_mode' => 'FIXED_LOCATION',
                    'service_radius_km' => null,
                    'max_guests' => 60,
                ],
            ],
            'addio-al-celibato-nubilato' => [
                [
                    'offering_name' => 'Spogliarellista personalizzato',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 40,
                    'max_guests' => null,
                ],
                [
                    'offering_name' => 'Yacht party',
                    'service_mode' => 'FIXED_LOCATION',
                    'service_radius_km' => null,
                    'max_guests' => 18,
                ],
            ],
            'eventi-aziendali' => [
                [
                    'offering_name' => 'Presentatore / Speaker',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 80,
                    'max_guests' => null,
                ],
                [
                    'offering_name' => 'Live band elegante',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 80,
                    'max_guests' => null,
                ],
            ],
            'compleanni-adulti' => [
                [
                    'offering_name' => 'Dinner show',
                    'service_mode' => 'FIXED_LOCATION',
                    'service_radius_km' => null,
                    'max_guests' => 100,
                ],
                [
                    'offering_name' => 'Noleggio sala privata',
                    'service_mode' => 'FIXED_LOCATION',
                    'service_radius_km' => null,
                    'max_guests' => 70,
                ],
            ],
            'matrimoni-ed-eventi-eleganti' => [
                [
                    'offering_name' => 'Musica live cerimonia',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 120,
                    'max_guests' => null,
                ],
                [
                    'offering_name' => 'Open bar show',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 120,
                    'max_guests' => null,
                ],
            ],
            'servizi-di-supporto' => [
                [
                    'offering_name' => 'Noleggio palco',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 90,
                    'max_guests' => null,
                ],
                [
                    'offering_name' => 'Noleggio impianto audio',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 60,
                    'max_guests' => null,
                ],
            ],
            'format-premium-esperienze-esclusive' => [
                [
                    'offering_name' => 'Party su yacht',
                    'service_mode' => 'FIXED_LOCATION',
                    'service_radius_km' => null,
                    'max_guests' => 20,
                ],
                [
                    'offering_name' => 'Rooftop party',
                    'service_mode' => 'FIXED_LOCATION',
                    'service_radius_km' => null,
                    'max_guests' => 80,
                ],
            ],
            'artisti' => [
                [
                    'offering_name' => 'DJ',
                    'service_mode' => 'MOBILE',
                    'service_radius_km' => 100,
                    'max_guests' => null,
                ],
            ],
        ];

        if ($categorySlug === 'ristoranti') {
            if ($vendorEmail === 'ristorante.baia@partylegacy.it') {
                return [
                    [
                        'offering_name' => 'Menu Pesce',
                        'service_mode' => 'FIXED_LOCATION',
                        'service_radius_km' => null,
                        'max_guests' => 150,
                        'short_description' => 'Specialità di mare freschissime e crudités',
                        'description' => 'La nostra cucina propone menu a base di pescato del giorno, ostriche e crudités. Una qualità impareggiabile per soddisfare anche i palati più esigenti, perfetta per cerimonie e cene esclusive in location suggestiva.'
                    ],
                    [
                        'offering_name' => 'Menu carne',
                        'service_mode' => 'FIXED_LOCATION',
                        'service_radius_km' => null,
                        'max_guests' => 150,
                        'short_description' => 'Selezioni di carni pregiate e grigliate',
                        'description' => 'Menu raffinati a base di tagli di carne selezionati, serviti con contorni di stagione e abbinati ai migliori vini della nostra cantina. Un\'esperienza culinaria di altissimo livello per i tuoi ospiti.'
                    ]
                ];
            } else {
                return [
                    [
                        'offering_name' => 'Menu all you can eat',
                        'service_mode' => 'FIXED_LOCATION',
                        'service_radius_km' => null,
                        'max_guests' => 300,
                        'short_description' => 'Buffet illimitato con formula fissa',
                        'description' => 'Divertimento e gusto senza limiti con il nostro grand buffet: pizza, primi, secondi e dolci a volontà. Specializzati per soddisfare numeri altissimi e grandissime tavolate a prezzi imbattibili e convenienti.'
                    ],
                    [
                        'offering_name' => 'Catering',
                        'service_mode' => 'MOBILE',
                        'service_radius_km' => 50,
                        'max_guests' => null,
                        'short_description' => 'Catering completo per il tuo evento',
                        'description' => 'Il servizio catering ideale per grandi numeri. Portiamo la magia dei nostri menu abbondanti e sfiziosi direttamente nella location del tuo evento, garantendo qualità e varietà senza mai tralasciare il servizio.'
                    ]
                ];
            }
        }

        return $map[$categorySlug] ?? [];
    }

    private function getDescriptions(string $slug): array
    {
        $all = [
            'animazione-bambini' => [
                'short' => [
                    'Animazione professionale per feste di compleanno e eventi per bambini',
                    'Intrattenimento su misura con animatori esperti certificati',
                ],
                'long' => [
                    'Offriamo servizi di animazione completa per feste di compleanno, battesimi e eventi per bambini di tutte le età. I nostri animatori professionisti intratterranno i piccoli ospiti con giochi interattivi, musica coinvolgente, balli divertenti e attività creative. Disponibilità di truccabimbi artistico, sculture di palloncini colorati e mascotte personalizzate per rendere unica e indimenticabile la vostra festa. Esperienza pluriennale nel settore con centinaia di eventi organizzati con successo.',
                    'Intrattenimento garantito per bambini di tutte le età con programmi studiati su misura. Organizziamo giochi di gruppo educativi, caccia al tesoro avventurosa, laboratori creativi di arte e cucina, e mini olimpiadi sportive. Tutte le attrezzature sono professionali, certificate e sicure. I nostri animatori sono certificati con esperienza pluriennale e formazione continua. Pacchetti completamente personalizzabili in base alle esigenze specifiche della festa e all\'età dei partecipanti.',
                ],
            ],
            'giochi-e-intrattenimento' => [
                'short' => [
                    'Giochi interattivi e intrattenimento musicale per feste teen',
                    'Party musicale con animazione interattiva per adolescenti',
                ],
                'long' => [
                    'Intrattenimento professionale con giochi strutturati e playlist completamente personalizzate in base ai gusti musicali dei ragazzi. Sistema di luci LED professionali, effetti speciali scenografici e animazione interattiva che coinvolge tutti i partecipanti. Organizziamo karaoke con basi professionali, silent disco con cuffie wireless, e giochi di gruppo moderni per rendere unica e memorabile la tua festa. Esperienza pluriennale nell\'intrattenimento giovani con attrezzature audio e video a disposizione.',
                    'Party indimenticabile con musica, giochi coinvolgenti e tanto divertimento garantito. Intrattenimento professionale con esperienza ventennale, impianto audio di qualità superiore e show interattivi personalizzati. Organizziamo schiuma party all\'aperto, neon party fluorescenti, e tornei di videogiochi competitivi. Massima sicurezza con personale qualificato, attrezzature certificate e assicurazione RC professionale. Esperienza con eventi fino a 200 persone.',
                ],
            ],
            'animazione-adulti-feste-private' => [
                'short' => [
                    'Live band e intrattenimento professionale per feste private adulti',
                    'Musica live e animazione di qualità per eventi privati esclusivi',
                ],
                'long' => [
                    'Servizio completo di intrattenimento per feste private, compleanni milestone e ricorrenze speciali. Live band professioniste, musicisti di alto livello e performer spettacolari per tutti i generi musicali. Attrezzature audio professionali e sistema luci di ultima generazione con effetti scenografici. Esperienza ventennale nel settore eventi privati con migliaia di feste organizzate in tutta Italia e all\'estero.',
                    'Rendiamo unica e indimenticabile la tua festa con intrattenimento di qualità superiore e servizio impeccabile. Musica dal vivo con band selezionate, show spettacolari e performance su misura come cene con delitto. Personalizziamo ogni singolo dettaglio in base alle tue esigenze logistiche e al budget disponibile. Preventivo gratuito dettagliato e sopralluogo senza impegno. Consulenza completa per la scelta della location.',
                ],
            ],
            'addio-al-celibato-nubilato' => [
                'short' => [
                    'Organizzazione completa addio al celibato e nubilato su misura',
                    'Party esclusivo personalizzato per i festeggiati con esperienza unica',
                ],
                'long' => [
                    'Organizziamo addio al celibato e nubilato indimenticabili con attenzione maniacale ad ogni dettaglio. Show personalizzati e spettacoli esclusivi, tour in limousine di lusso o party su yacht privato, cene spettacolo in location esclusive con intrattenimento live. Intrattenimento garantito con performer professionisti e artisti selezionati. Pacchetti all-inclusive con transfer privati, location esclusive riservate e servizio fotografico professionale incluso. Discrezione assoluta garantita.',
                    'L\'addio al celibato o nubilato che non dimenticherai mai! Spettacoli completamente su misura studiati per il festeggiato, tour esclusivo in limousine stretch o Hummer, aperitivi gourmet in location panoramiche e tanto divertimento con animazione dedicata. Organizziamo ogni singolo dettaglio dalla A alla Z con la massima professionalità e discrezione assoluta. Professionalità ventennale e personalizzazione totale garantite per rendere speciale questo momento unico e irripetibile.',
                ],
            ],
            'eventi-aziendali' => [
                'short' => [
                    'Team building esperienziale e intrattenimento per eventi corporate',
                    'Organizzazione eventi aziendali chiavi in mano con formula full service',
                ],
                'long' => [
                    'Servizi professionali completi per eventi aziendali di ogni dimensione: team building esperienziale personalizzato, convention nazionali e internazionali, meeting strategici e feste corporate esclusive. Attività personalizzate studiate su misura, workshop interattivi con formatori certificati e presentatori di alto profilo. Speaker motivazionali e entertainment di qualità superiore. Gestione completa dell\'evento con project management dedicato e report finale dettagliato con KPI misurabili.',
                    'Rendiamo speciale e produttivo il tuo evento aziendale con soluzioni innovative completamente su misura. Intrattenimento aziendale, live band eleganti per cene di gala, e quiz interattivi multimediali. Tecnologie all\'avanguardia, staff qualificato con esperienza decennale nel corporate. ROI garantito sulla soddisfazione partecipanti con questionari di feedback post-evento e analisi dettagliata dei risultati.',
                ],
            ],
            'compleanni-adulti' => [
                'short' => [
                    'Organizzazione compleanno adulti con intrattenimento professionale di qualità',
                    'Festa di compleanno personalizzata e indimenticabile su misura per te',
                ],
                'long' => [
                    'Organizziamo il tuo compleanno da sogno con ogni dettaglio curato nei minimi particolari! Intrattenimento professionale con cena spettacolo esclusiva e party a tema completamente personalizzato. Location esclusive selezionate, catering gourmet personalizzato secondo le tue preferenze e noleggio di sale private selezionate. Pacchetti completi all-inclusive dalla cena gourmet allo spettacolo finale, con possibilità di servizio fotografico professionale.',
                    'Il tuo compleanno merita di essere davvero speciale e memorabile. Ci occupiamo di tutto con professionalità ventennale: dalla selezione della location perfetta all\'intrattenimento esclusivo, dal catering stellato agli allestimenti scenografici. Pool party estivo in villa privata, dinner show elegante o festa esclusiva in location storica. Esperienza ventennale consolidata nel settore con oltre 1000 eventi organizzati in tutta Italia.',
                ],
            ],
            'matrimoni-ed-eventi-eleganti' => [
                'short' => [
                    'Intrattenimento elegante e raffinato per matrimoni e cerimonie esclusive',
                    'Musica live professionale e animazione di classe per il tuo matrimonio da sogno',
                ],
                'long' => [
                    'Rendiamo magico e indimenticabile il giorno più importante della vostra vita con servizio impeccabile. Musica live professionale per cerimonia religiosa e civile e ricevimento elegante con spettacoli esclusivi selezionati come open bar acrobatici. Effetti speciali scenografici, sparkular per taglio torta, fontane fredde luminose e giochi di luci coreografici. Wedding planner professionisti disponibili per la pianificazione completa con timeline dettagliata.',
                    'Matrimonio da sogno con intrattenimento raffinato, elegante e professionale. Musica dal vivo con band dal vivo per l\'aperitivo di benvenuto, ricevimento e after party. Sistema luci scenografiche professionali, effetti speciali pirotecnici autorizzati e coordinamento perfetto con tutti i fornitori. Oltre 200 matrimoni organizzati con successo in location esclusive e recensioni eccellenti con valutazione media 5 stelle. Portfolio completo disponibile.',
                ],
            ],
            'servizi-di-supporto' => [
                'short' => [
                    'Noleggio professionale attrezzature audio, luci e palco per eventi',
                    'Servizi tecnici professionali completi e assistenza on-site garantita H24',
                ],
                'long' => [
                    'Noleggio professionale certificato di impianti audio line array, sistemi luci intelligenti, palchi modulari e allestimenti scenografici spettacoli. Attrezzature di ultima generazione completamente certificate con tecnici audio/luci qualificati e assistenza on-site garantita per tutta la durata dell\'evento. Preventivi gratuiti dettagliati e sopralluoghi tecnici senza impegno. Esperienza ventennale consolidata nel settore rental.',
                    'Forniamo tutto il supporto tecnico professionale necessario per il tuo evento di successo: impianti audio professionali certificati, sistemi luci DMX intelligenti, palchi modulari certificati e noleggio impianti audio completi. Service professionale completo con tecnici certificati disponibili H24 per assistenza e troubleshooting. Noleggio giornaliero o plurigiornaliero con formule flessibili e scontistiche dedicate.',
                ],
            ],
            'format-premium-esperienze-esclusive' => [
                'short' => [
                    'Eventi esclusivi in location da sogno, ville storiche e yacht privati',
                    'Esperienze luxury completamente su misura con servizio impeccabile 5 stelle',
                ],
                'long' => [
                    'Organizziamo eventi esclusivi irripetibili in location da sogno accuratamente selezionate: ville storiche d\'epoca con affreschi originali, yacht privati di lusso con equipaggio dedicato, rooftop panoramici con vista mozzafiato e location segrete ad accesso riservato. Servizio impeccabile di altissimo livello con chef stellati Michelin, sommelier professionisti certificati AIS e intrattenimento live di alto profilo internazionale. Ogni dettaglio curato maniacalmente nei minimi particolari per un\'esperienza sensoriale indimenticabile e totalizzante.',
                    'Esperienze luxury completamente personalizzate su misura per clienti esigenti e raffinati. Party esclusivi su yacht di lusso, cene private gourmet in ville d\'epoca storiche, eventi in location esclusive con accesso riservato solo su invito e servizi VIP dedicati. Concierge personale H24, servizio fotografico professionale con fotografo di moda, possibilità di live streaming in alta definizione e video reportage cinematografico. Discrezione assoluta garantita con NDA firmati. Clientela internazionale selezionata.',
                ],
            ],
            'artisti' => [
                'short' => [
                    'DJ professionista per eventi, matrimoni e feste private',
                ],
                'long' => [
                    'DJ di caratura internazionale disponibile per eventi aziendali, matrimoni di lusso e party in discoteche esclusive. Impianto audio di altissima qualità in dotazione e attrezzatura Pioneer top di gamma. Possibilità di accompagnamento con voce o strumenti live su richiesta. Oltre 15 anni di esperienza nei migliori club d\'Italia.',
                ],
            ],
            'ristoranti' => [
                'short' => [
                    'Catering e menù per eventi aziendali e cerimonie eleganti',
                    'Ristorazione di alto livello per ogni tipo di celebrazione',
                ],
                'long' => [
                    'La nostra cucina offre menù completi a base di carne e pesce, ideati per soddisfare i palati più esigenti. Selezioniamo solo materie prime di altissima qualità, con possibilità di adattare le ricette a intolleranze e preferenze specifiche. Perfetto per cerimonie, cene di gala ed eventi esclusivi. Servizio in location fissa con sala o mobile tramite formula catering curata in ogni dettaglio.',
                    'Specializzati in formula All You Can Eat e servizio Catering di alta gamma per soddisfare grandi numeri senza mai tralasciare la qualità del cibo curato in modo stellato. Buffet spettacolari, finger food gourmet e corner show cooking per stupire gli ospiti del tuo evento aziendale, matrimonio o compleanno speciale. Lo staff preparato farà si che tutto giri a meraviglia prestando massima accortezza.',
                ],
            ],
        ];

        return $all[$slug] ?? [
            'short' => [
                'Servizio professionale di qualità',
                'Esperienza garantita e professionalità',
            ],
            'long' => [
                'Descrizione dettagliata del servizio offerto con qualità superiore.',
                'Servizio di qualità con esperienza pluriennale nel settore.',
            ],
        ];
    }
}