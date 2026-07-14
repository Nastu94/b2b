<?php

namespace App\Jobs;

use App\Models\VendorAccount;
use App\Services\PrestashopWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushVendorToPrestashopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $vendor;

    public function __construct(VendorAccount $vendor)
    {
        $this->vendor = $vendor;
    }

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function handle(PrestashopWebhookService $service, \App\Services\PrestashopProductSyncService $productSync)
    {
        // 1. Sync full PrestaShop native product (per scaricare/aggiornare l'immagine fisicamente in PS)
        $productSync->sync($this->vendor);

        // 2. Sync JSON data bypassando il prodotto per il frontend React/Smarty veloce
        $result = $service->pushVendor($this->vendor);

        if ($result === PrestashopWebhookService::RESULT_ERROR) {
            throw new \RuntimeException("Errore di configurazione Webhook o errore fatale (verificare i log).");
        }
        // Se RESULT_SKIPPED, non fa nulla e il job finisce con successo.
    }
}
