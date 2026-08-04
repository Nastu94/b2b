<?php

namespace App\Console\Commands;

use App\Models\VendorAccount;
use App\Services\PrestashopWebhookService;
use Illuminate\Console\Command;

class PushBookingBridgeWebhooks extends Command
{
    protected $signature = 'vendors:push-webhooks {--vendor=}';
    protected $description = 'Esegue il push dei dati Vendor collegati al modulo Booking Bridge su PrestaShop';

    public function handle(): int
    {
        $query = VendorAccount::query()
            ->whereNull('deleted_at')
            ->whereNotNull('prestashop_product_id');

        if ($vendorId = $this->option('vendor')) {
            $query->whereKey((int) $vendorId);
        }

        $vendors = $query->get();

        if ($vendors->isEmpty()) {
            $this->info('Nessun vendor collegato a PrestaShop trovato.');
            return self::SUCCESS;
        }

        $this->info("Trovati {$vendors->count()} vendor collegati a PrestaShop. Avvio l'invio sincrono dei Webhook...");

        $bar = $this->output->createProgressBar(count($vendors));
        $bar->start();

        $webhookService = app(PrestashopWebhookService::class);
        $successes = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($vendors as $vendor) {
            try {
                \App\Jobs\PushVendorToPrestashopJob::dispatchSync($vendor);
                $vendor->refresh();
                if ($vendor->prestashop_sync_error_code) {
                    $errors++;
                    $this->error("\nErrore sincronizzando Vendor #{$vendor->id}: " . $vendor->prestashop_sync_error_code);
                } else {
                    $successes++;
                }

                // removed old result logic
            } catch (\Throwable $e) {
                $errors++;
                $this->error("\nErrore sincronizzando Vendor #{$vendor->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($errors > 0) {
            $this->warn("Completato con {$successes} successi, {$skipped} ignorati e {$errors} errori. Controlla i file di log di Laravel (storage/logs/laravel.log) per i dettagli su URL o chiave API errati.");
            return self::FAILURE;
        } else {
            $this->info("Sincronizzazione sincrona completata con successo ({$successes} successi, {$skipped} ignorati).");
            return self::SUCCESS;
        }
    }
}
