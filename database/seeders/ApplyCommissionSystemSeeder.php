<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Models\Booking;

class ApplyCommissionSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Inizio processo di sanatoria e upgrade...');

        // 1. Mappatura intelligente commissioni sulle Categorie esistenti
        $commissions = [
            'animazione-bambini' => 12,
            'giochi-e-intrattenimento' => 10,
            'animazione-adulti-feste-private' => 15,
            'addio-al-celibato-nubilato' => 15,
            'eventi-aziendali' => 12,
            'compleanni-adulti' => 15,
            'matrimoni-ed-eventi-eleganti' => 20,
            'servizi-di-supporto' => 12,
            'format-premium-esperienze-esclusive' => 20,
            'ristoranti' => 15,
            'artisti' => 12,
        ];

        $categories = Category::all();
        $updatedCats = 0;
        foreach ($categories as $category) {
            $rate = 10.00; // Valore di default standard "sicuro"
            foreach ($commissions as $keyword => $val) {
                if (stripos($category->name, $keyword) !== false || stripos($category->slug, $keyword) !== false) {
                    $rate = $val;
                    break;
                }
            }
            $category->update(['commission_rate' => $rate]);
            $updatedCats++;
        }
        $this->command->info("Aggiornate commissioni per {$updatedCats} categorie.");

        // 2. Sanatoria Fornitori storici (Status ACTIVE e piano COMMISSION per non bloccarli)
        $updatedVendors = VendorAccount::query()->update([
            'status' => 'ACTIVE',
            'payment_model' => 'COMMISSION'
        ]);
        $this->command->info("Sbloccati {$updatedVendors} accounts Vendor in stato ACTIVE.");

        // 3. Approvazione massiva dei vecchi servizi (Retrocompatibilità)
        $updatedOfferings = VendorOfferingProfile::query()->update([
            'is_approved' => true
        ]);
        $this->command->info("Approvati {$updatedOfferings} servizi/offerings storici.");

        // 4. Booking storici (Congelamento policy commissioni default storiche)
        $updatedBookings = Booking::query()->update([
            'is_commission_based' => true,
            'vendor_payment_status' => 'UNPAID'
        ]);
        $this->command->info("Marcate {$updatedBookings} vecchie prenotazioni sotto regime di Commissioni.");

        $this->command->info('Perfetto! Il database è ora compatibile.');
    }
}
