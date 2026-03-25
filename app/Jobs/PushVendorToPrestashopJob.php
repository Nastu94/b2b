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

    public function handle(PrestashopWebhookService $service, \App\Services\PrestashopProductSyncService $productSync)
    {
        // 1. Sync full PrestaShop native product (per scaricare/aggiornare l'immagine fisicamente in PS)
        try {
            $productSync->sync($this->vendor);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PushVendorToPrestashopJob (Product Sync) failed: ' . $e->getMessage());
        }

        // 2. Sync JSON data bypassando il prodotto per il frontend React/Smarty veloce
        $service->pushVendor($this->vendor);
    }
}
