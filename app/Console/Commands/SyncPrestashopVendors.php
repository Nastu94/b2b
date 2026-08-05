<?php

namespace App\Console\Commands;

use App\Models\VendorAccount;
use App\Services\PrestashopProductSyncService;
use Illuminate\Console\Command;

class SyncPrestashopVendors extends Command
{
    protected $signature = 'vendors:sync-prestashop {--vendor_id=} {--only-missing}';
    protected $description = 'Sincronizza i vendor esistenti verso PrestaShop';

    public function handle(): int
    {
        if (! config('services.prestashop.vendor_sync_enabled', false)) {
            $this->warn('Sincronizzazione vendor PrestaShop disabilitata da PRESTASHOP_VENDOR_SYNC_ENABLED.');

            return self::SUCCESS;
        }

        $query = VendorAccount::query();

        if ($vendorId = $this->option('vendor_id')) {
            $query->whereKey((int) $vendorId);
        }

        if ($this->option('only-missing')) {
            $query->whereNull('prestashop_product_id');
        }

        $vendors = $query->get();

        if ($vendors->isEmpty()) {
            $this->warn('Nessun vendor trovato.');
            return self::SUCCESS;
        }

        $processed = 0;
        $success = 0;
        $errors = 0;

        $this->info('Avvio sincronizzazione vendor verso PrestaShop...');

        foreach ($vendors as $vendor) {
            $processed++;

            try {
                \App\Jobs\PushVendorToPrestashopJob::dispatchSync($vendor);
                $vendor->refresh();
                $this->info("Vendor {$vendor->id} sincronizzato (v{$vendor->prestashop_sync_version}, hash: " . substr((string)$vendor->prestashop_payload_hash, 0, 8) . ").");
                $success++;
            } catch (\Throwable $e) {
                $this->error("Vendor {$vendor->id} errore: {$e->getMessage()}");
                report($e);
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Processati: {$processed}");
        $this->info("Sincronizzati correttamente: {$success}");

        if ($errors > 0) {
            $this->warn("Errori: {$errors}");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
