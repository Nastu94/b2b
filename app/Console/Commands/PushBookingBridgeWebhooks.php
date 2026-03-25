<?php

namespace App\Console\Commands;

use App\Models\VendorAccount;
use App\Jobs\PushVendorToPrestashopJob;
use Illuminate\Console\Command;

class PushBookingBridgeWebhooks extends Command
{
    protected $signature = 'vendors:push-webhooks {--vendor=}';
    protected $description = 'Forza l\'invio massivo dei dati JSON dei vendor verso il Webhook di PrestaShop';

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
            $this->warn('Nessun vendor trovato da sincronizzare (assicurati che siano attivi e abbiano prestashop_product_id).');
            return self::SUCCESS;
        }

        $this->info("Trovati {$vendors->count()} vendor collegati a PrestaShop. Metto in coda gli aggiornamenti Webhook...");

        $bar = $this->output->createProgressBar(count($vendors));
        $bar->start();

        $webhookService = app(\App\Services\PrestashopWebhookService::class);
        $errors = 0;

        foreach ($vendors as $vendor) {
            try {
                $success = $webhookService->pushVendor($vendor);
                if (!$success) {
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("\nErrore fatale sincronizzando Vendor #{$vendor->id}: " . $e->getMessage());
                $errors++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($errors > 0) {
            $this->warn("Completato con {$errors} errori. Controlla i file di log di Laravel (storage/logs/laravel.log) per i dettagli su URL o chiave API errati.");
        } else {
            $this->info('✅ Sincronizzazione sincrona completata con successo! Tutti i vendor sono stati spinti al webhook PrestaShop.');
        }

        return self::SUCCESS;
    }
}
