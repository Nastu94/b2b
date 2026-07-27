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

    public function handle(
        PrestashopWebhookService $webhookService, 
        \App\Services\PrestashopProductSyncService $productSync,
        \App\Services\PrestashopVendorPayloadFactory $payloadFactory
    ) {
        $vendor = $this->vendor;
        $vendor->refresh();

        $syncVersion = $vendor->prestashop_sync_version + 1;
        $payload = $payloadFactory->buildPayload($vendor, $syncVersion);
        
        $payloadHash = hash('sha256', json_encode($payload));

        if ($vendor->prestashop_payload_hash === $payloadHash && $vendor->prestashop_sync_error_code === null) {
            // Already synced, identical payload, no errors previously
            return;
        }

        try {
            // 1. Sync full PrestaShop native product
            $productSync->sync($vendor, $payload);
            
            // Reload to get the product_id if it was just created
            if (!$vendor->prestashop_product_id) {
                $vendor->refresh();
                if ($vendor->prestashop_product_id) {
                    $payload['id_product'] = (int) $vendor->prestashop_product_id;
                    $payload['product_id'] = (int) $vendor->prestashop_product_id;
                    $payloadHash = hash('sha256', json_encode($payload));
                }
            }

            // 2. Sync JSON data bypassando il prodotto per il frontend React/Smarty veloce
            $result = $webhookService->pushVendor($vendor, $payload);

            if ($result === PrestashopWebhookService::RESULT_ERROR) {
                throw new \RuntimeException("Errore di configurazione Webhook o errore fatale (verificare i log).");
            }
            
            $vendor->update([
                'prestashop_sync_version' => $syncVersion,
                'prestashop_payload_hash' => $payloadHash,
                'prestashop_synced_at' => now(),
                'prestashop_sync_error_code' => null,
                'prestashop_sync_error_at' => null,
            ]);

        } catch (\Exception $e) {
            $vendor->update([
                'prestashop_sync_version' => $syncVersion,
                'prestashop_payload_hash' => $payloadHash,
                'prestashop_sync_error_code' => \Illuminate\Support\Str::limit($e->getMessage(), 250),
                'prestashop_sync_error_at' => now(),
            ]);
            
            throw $e;
        }
    }
}
