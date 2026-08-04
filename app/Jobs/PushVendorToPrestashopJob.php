<?php

namespace App\Jobs;

use App\Models\VendorAccount;
use App\Services\PrestashopProductSyncService;
use App\Services\PrestashopVendorPayloadFactory;
use App\Services\PrestashopWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class PushVendorToPrestashopJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $vendorAccountId;

    public int $uniqueFor = 300;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(VendorAccount|int $vendor)
    {
        $this->vendorAccountId = $vendor instanceof VendorAccount
            ? (int) $vendor->getKey()
            : (int) $vendor;
    }

    public function uniqueId(): string
    {
        return (string) $this->vendorAccountId;
    }

    public function handle(
        PrestashopWebhookService $webhookService,
        PrestashopProductSyncService $productSync,
        PrestashopVendorPayloadFactory $payloadFactory
    ): void {
        $vendor = VendorAccount::withTrashed()
            ->with([
                'category',
                'vendorOfferingProfiles' => fn ($query) => $query
                    ->with('images')
                    ->orderBy('id'),
            ])
            ->find($this->vendorAccountId);

        if (! $vendor) {
            return;
        }

        $syncVersion = ((int) $vendor->prestashop_sync_version) + 1;
        $payload = $payloadFactory->buildPayload($vendor, $syncVersion);

        // L'hash rappresenta solo i dati funzionali: versione, data di
        // generazione e ID prodotto non devono rendere ogni payload diverso.
        $payloadHash = $payloadFactory->contentHash($payload);

        if ($vendor->prestashop_payload_hash === $payloadHash && $vendor->prestashop_sync_error_code === null) {
            return;
        }

        try {
            $productSyncResult = $productSync->sync($vendor, $payload);

            if ($productSyncResult === PrestashopProductSyncService::RESULT_CATEGORY_MAPPING_MISSING) {
                $vendor->forceFill([
                    'prestashop_sync_version' => $syncVersion,
                    'prestashop_payload_hash' => $payloadHash,
                    'prestashop_sync_error_code' => "Categoria PrestaShop mancante per vendor {$vendor->id}.",
                    'prestashop_sync_error_at' => now(),
                ])->saveQuietly();

                return;
            }

            // La creazione prodotto può aver assegnato l'ID PrestaShop.
            // Ricostruiamo il payload senza cambiare la versione logica.
            $vendor->refresh()->loadMissing(['category', 'vendorOfferingProfiles.images']);
            $payload = $payloadFactory->buildPayload($vendor, $syncVersion);

            $result = $webhookService->pushVendor($vendor, $payload);

            if ($result === PrestashopWebhookService::RESULT_ERROR) {
                throw new \RuntimeException('Errore di configurazione webhook PrestaShop.');
            }

            $vendor->forceFill([
                'prestashop_sync_version' => $syncVersion,
                'prestashop_payload_hash' => $payloadHash,
                'prestashop_synced_at' => now(),
                'prestashop_sync_error_code' => null,
                'prestashop_sync_error_at' => null,
            ])->saveQuietly();
        } catch (Throwable $e) {
            $vendor->forceFill([
                'prestashop_sync_version' => $syncVersion,
                'prestashop_payload_hash' => $payloadHash,
                'prestashop_sync_error_code' => Str::limit($e->getMessage(), 250),
                'prestashop_sync_error_at' => now(),
            ])->saveQuietly();

            throw $e;
        }
    }
}
