<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Offering;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use App\Models\VendorLeadTime;
use App\Models\VendorBlackout;
use App\Models\VendorOfferingProfile;
use App\Models\SlotLock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DemoVendorsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('PULIZIA COMPLETA DATABASE VENDOR...');

        // Lista email vendor demo da proteggere
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
        ];

        // PRIMA: Prendi gli ID dei vendor demo
        $demoVendorUserIds = User::whereIn('email', $demoVendorEmails)->pluck('id')->toArray();
        $demoVendorIds = VendorAccount::whereIn('user_id', $demoVendorUserIds)->pluck('id')->toArray();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('Cancellazione vendor offering profiles...');
        if (!empty($demoVendorIds)) {
            VendorOfferingProfile::whereIn('vendor_account_id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione vendor offerings (pivot)...');
        if (!empty($demoVendorIds)) {
            DB::table('vendor_offerings')->whereIn('vendor_account_id', $demoVendorIds)->delete();
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
            VendorAccount::whereIn('id', $demoVendorIds)->delete();
        }

        $this->command->info('Cancellazione users vendor demo...');
        $deletedUsers = User::whereIn('email', $demoVendorEmails)->delete();
        $this->command->info("Cancellati {$deletedUsers} users vendor");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Database pulito (admin protetto)!\n');

        $this->command->info('Creazione 1 vendor per categoria (Puglia) + 2 offering cards...');

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
                'category_slug' => 'animazione-teen-party',
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
        ];

        $count = 0;

        foreach ($vendors as $data) {
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
            $this->createVendorOfferings($vendor, $category);

            $this->command->info("{$category->name}: {$data['company_name']} ({$data['city']})");
            $count++;
        }

        $this->command->info("\n Creati {$count} vendor con offerings!");
    }

    private function createSlots(VendorAccount $vendor): void
    {
        $slots = [
            ['slug' => 'morning', 'label' => 'Mattina', 'start' => '09:00:00', 'end' => '13:00:00', 'order' => 10],
            ['slug' => 'afternoon', 'label' => 'Pomeriggio', 'start' => '14:00:00', 'end' => '18:00:00', 'order' => 20],
            ['slug' => 'evening', 'label' => 'Sera', 'start' => '19:00:00', 'end' => '23:00:00', 'order' => 30],
        ];

        foreach ($slots as $s) {
            VendorSlot::create([
                'vendor_account_id' => $vendor->id,
                'slug' => $s['slug'],
                'label' => $s['label'],
                'start_time' => $s['start'],
                'end_time' => $s['end'],
                'is_active' => true,
                'sort_order' => $s['order'],
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

    /**
     * CREA 2 OFFERING CARDS PER VENDOR
     */
    private function createVendorOfferings(VendorAccount $vendor, Category $category): void
    {
        // Prendi 2 offerings dalla categoria
        $offerings = Offering::where('category_id', $category->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(2)
            ->get();

        if ($offerings->count() < 2) {
            $this->command->warn("Meno di 2 offerings per categoria");
            return;
        }

        $descriptions = $this->getDescriptions($category->slug);

        foreach ($offerings as $index => $offering) {
            // 1. Crea relazione pivot
            DB::table('vendor_offerings')->insert([
                'vendor_account_id' => $vendor->id,
                'offering_id' => $offering->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Crea profile con descrizioni
            VendorOfferingProfile::create([
                'vendor_account_id' => $vendor->id,
                'offering_id' => $offering->id,
                'title' => $offering->name,
                'short_description' => $descriptions['short'][$index] ?? 'Servizio professionale per eventi',
                'description' => $descriptions['long'][$index] ?? 'Descrizione dettagliata del servizio.',
                'cover_image_path' => null,
                'is_published' => true,
            ]);
        }

        $this->command->info("Creati {$offerings->count()} offering profiles");
    }

    /**
     * DESCRIZIONI COMPLETE PER OGNI CATEGORIA
     */
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
            'animazione-teen-party' => [
                'short' => [
                    'DJ set professionale e intrattenimento musicale per feste teen',
                    'Party musicale con animazione interattiva per adolescenti',
                ],
                'long' => [
                    'DJ set professionale con playlist completamente personalizzate in base ai gusti musicali dei ragazzi. Sistema di luci LED professionali, effetti speciali scenografici e animazione interattiva che coinvolge tutti i partecipanti. Organizziamo karaoke con basi professionali, silent disco con cuffie wireless, e giochi di gruppo moderni per rendere unica e memorabile la tua festa. Esperienza pluriennale nell\'intrattenimento giovani con attrezzature audio e video di ultima generazione.',
                    'Party indimenticabile con musica dal vivo, giochi coinvolgenti e tanto divertimento garantito. DJ professionista con esperienza ventennale, impianto audio di qualità superiore e show interattivi personalizzati. Organizziamo schiuma party all\'aperto, neon party fluorescenti, e tornei di videogiochi competitivi. Massima sicurezza con personale qualificato, attrezzature certificate e assicurazione RC professionale. Esperienza con eventi fino a 200 persone.',
                ],
            ],
            'animazione-adulti-feste-private' => [
                'short' => [
                    'DJ set e intrattenimento professionale per feste private adulti',
                    'Musica live e animazione di qualità per eventi privati esclusivi',
                ],
                'long' => [
                    'Servizio completo di intrattenimento per feste private, compleanni milestone e ricorrenze speciali. DJ set con playlist personalizzate su tutti i generi musicali, live band professioniste, musicisti di alto livello e performer spettacolari. Attrezzature audio professionali e sistema luci di ultima generazione con effetti scenografici. Esperienza ventennale nel settore eventi privati con migliaia di feste organizzate in tutta Italia e all\'estero.',
                    'Rendiamo unica e indimenticabile la tua festa con intrattenimento di qualità superiore e servizio impeccabile. DJ professionista con repertorio internazionale, musica dal vivo con band selezionate, show spettacolari e performance su misura. Personalizziamo ogni singolo dettaglio in base ai tuoi gusti musicali, alle tue esigenze logistiche e al budget disponibile. Preventivo gratuito dettagliato e sopralluogo senza impegno. Consulenza completa per la scelta della location.',
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
                    'Servizi professionali completi per eventi aziendali di ogni dimensione: team building esperienziale personalizzato, convention nazionali e internazionali, meeting strategici e feste corporate esclusive. Attività personalizzate studiate su misura, workshop interattivi con formatori certificati e simulazioni VR immersive. Speaker motivazionali di alto profilo e entertainment di qualità superiore. Gestione completa dell\'evento con project management dedicato e report finale dettagliato con KPI misurabili.',
                    'Rendiamo speciale e produttivo il tuo evento aziendale con soluzioni innovative completamente su misura. Team building esperienziali con metodologie certificate, cooking class con chef stellati, escape room aziendali tematizzate e quiz interattivi multimediali. Tecnologie all\'avanguardia con realtà virtuale e aumentata, staff qualificato con esperienza decennale nel corporate. ROI garantito sulla soddisfazione partecipanti con questionari di feedback post-evento e analisi dettagliata dei risultati.',
                ],
            ],
            'compleanni-adulti' => [
                'short' => [
                    'Organizzazione compleanno adulti con intrattenimento professionale di qualità',
                    'Festa di compleanno personalizzata e indimenticabile su misura per te',
                ],
                'long' => [
                    'Organizziamo il tuo compleanno da sogno con ogni dettaglio curato nei minimi particolari! DJ set professionale con musica personalizzata, karaoke con basi professionali, dinner show esclusivo e party a tema completamente personalizzato. Location esclusive selezionate, catering gourmet personalizzato secondo le tue preferenze e intrattenimento professionale con artisti selezionati. Pacchetti completi all-inclusive dalla cena gourmet allo spettacolo finale con fuochi d\'artificio, con possibilità di servizio fotografico professionale e video reportage.',
                    'Il tuo compleanno merita di essere davvero speciale e memorabile. Ci occupiamo di tutto con professionalità ventennale: dalla selezione della location perfetta all\'intrattenimento esclusivo, dal catering stellato agli allestimenti scenografici spettacolari. Pool party estivo in villa privata, party elegante in rooftop panoramico o festa esclusiva in location storica. Esperienza ventennale consolidata nel settore con oltre 1000 eventi organizzati in tutta Italia e testimonial soddisfatti.',
                ],
            ],
            'matrimoni-ed-eventi-eleganti' => [
                'short' => [
                    'Intrattenimento elegante e raffinato per matrimoni e cerimonie esclusive',
                    'Musica live professionale e animazione di classe per il tuo matrimonio da sogno',
                ],
                'long' => [
                    'Rendiamo magico e indimenticabile il giorno più importante della vostra vita con servizio impeccabile. Musica live professionale per cerimonia religiosa e civile e ricevimento elegante, DJ set raffinato con playlist personalizzata, animazione bambini professionale e discreta, spettacoli esclusivi selezionati. Effetti speciali scenografici, sparkular per taglio torta, fontane fredde luminose e giochi di luci coreografici. Wedding planner professionisti disponibili per la pianificazione completa con timeline dettagliata minuto per minuto.',
                    'Matrimonio da sogno con intrattenimento raffinato, elegante e professionale. Quartetto d\'archi classico per la cerimonia religiosa, band live jazz per l\'aperitivo di benvenuto, DJ set internazionale per il ricevimento e after party. Sistema luci scenografiche professionali, effetti speciali pirotecnici autorizzati e coordinamento perfetto con tutti i fornitori. Oltre 200 matrimoni organizzati con successo in location esclusive e recensioni eccellenti con valutazione media 5 stelle. Portfolio completo disponibile.',
                ],
            ],
            'servizi-di-supporto' => [
                'short' => [
                    'Noleggio professionale attrezzature audio, luci e palco per eventi',
                    'Servizi tecnici professionali completi e assistenza on-site garantita H24',
                ],
                'long' => [
                    'Noleggio professionale certificato di impianti audio line array, sistemi luci intelligenti, palchi modulari e allestimenti scenografici spettacolari. Attrezzature di ultima generazione completamente certificate con tecnici audio/luci qualificati e assistenza on-site garantita per tutta la durata dell\'evento. Preventivi gratuiti dettagliati e sopralluoghi tecnici senza impegno. Esperienza ventennale consolidata nel settore rental con parco attrezzature sempre aggiornato alle ultime tecnologie disponibili sul mercato.',
                    'Forniamo tutto il supporto tecnico professionale necessario per il tuo evento di successo: impianti audio professionali certificati, sistemi luci DMX intelligenti, videoproiezione HD e 4K, palchi modulari certificati e strutture espositive personalizzate. Service professionale completo con tecnici certificati disponibili H24 per assistenza e troubleshooting. Effetti speciali pirotecnici, macchine del fumo basso e pesante, led wall modulari ad alta risoluzione e proiettori laser professionali. Noleggio giornaliero o plurigiornaliero con formule flessibili e scontistiche dedicate.',
                ],
            ],
            'format-premium-esperienze-esclusive' => [
                'short' => [
                    'Eventi esclusivi in location da sogno, ville storiche e yacht privati',
                    'Esperienze luxury completamente su misura con servizio impeccabile 5 stelle',
                ],
                'long' => [
                    'Organizziamo eventi esclusivi irripetibili in location da sogno accuratamente selezionate: ville storiche d\'epoca con affreschi originali, yacht privati di lusso con equipaggio dedicato, rooftop panoramici con vista mozzafiato e location segrete ad accesso riservato. Servizio impeccabile di altissimo livello con chef stellati Michelin, sommelier professionisti certificati AIS e intrattenimento live di alto profilo internazionale. Ogni dettaglio curato maniacalmente nei minimi particolari per un\'esperienza sensoriale indimenticabile e totalizzante.',
                    'Esperienze luxury completamente personalizzate su misura per clienti esigenti e raffinati. Party esclusivi su yacht di lusso con DJ internazionali di fama mondiale, cene private gourmet in ville d\'epoca storiche, eventi in location esclusive con accesso riservato solo su invito e servizi VIP dedicati. Concierge personale H24, servizio fotografico professionale con fotografo di moda, possibilità di live streaming in alta definizione e video reportage cinematografico. Discrezione assoluta garantita con NDA firmati. Clientela internazionale selezionata.',
                ],
            ],
        ];

        return $all[$slug] ?? [
            'short' => ['Servizio professionale di qualità', 'Esperienza garantita e professionalità'],
            'long' => ['Descrizione dettagliata del servizio offerto con qualità superiore.', 'Servizio di qualità con esperienza pluriennale nel settore.'],
        ];
    }
}
