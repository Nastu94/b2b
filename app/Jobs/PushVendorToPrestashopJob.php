<?php

namespace App\Jobs;

use App\Models\VendorAccount;
use App\Services\PrestashopProductSyncService;
use App\Services\PrestashopVendorPayloadFactory;
use App\Services\PrestashopWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        if (! config('services.prestashop.vendor_sync_enabled', false)) {
            return;
        }

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
        $payloadHash = $payloadFactory->contentHash($payload);
        $samePayload = is_string($vendor->prestashop_payload_hash)
            && $vendor->prestashop_payload_hash !== ''
            && hash_equals($vendor->prestashop_payload_hash, $payloadHash);

        if ($samePayload && $vendor->prestashop_sync_error_code === null) {
            return;
        }

        // Un errore del webhook non deve ripetere la fase prodotto e
        // reimportare nuovamente la copertina.
        $productPhaseCompleted = $samePayload
            && Str::startsWith((string) $vendor->prestashop_sync_error_code, '[webhook]');
        $phase = 'product';

        try {
            if (! $productPhaseCompleted) {
                $productSyncResult = $productSync->sync($vendor, $payload);

                if ($productSyncResult === PrestashopProductSyncService::RESULT_CATEGORY_MAPPING_MISSING) {
                    $vendor->forceFill([
                        'prestashop_sync_version' => $syncVersion,
                        'prestashop_sync_error_code' => "[product] Categoria PrestaShop mancante per vendor {$vendor->id}.",
                        'prestashop_sync_error_at' => now(),
                    ])->saveQuietly();

                    return;
                }

                // Salviamo il completamento della fase prodotto prima del
                // webhook, così anche un crash non può duplicare immagini.
                $vendor->forceFill([
                    'prestashop_sync_version' => $syncVersion,
                    'prestashop_payload_hash' => $payloadHash,
                    'prestashop_sync_error_code' => '[webhook] Invio dati strutturati in attesa.',
                    'prestashop_sync_error_at' => now(),
                ])->saveQuietly();
            }

            $vendor->refresh()->loadMissing(['category', 'vendorOfferingProfiles.images']);
            $payload = $payloadFactory->buildPayload($vendor, $syncVersion);

            $phase = 'webhook';
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
        } catch (Throwable $exception) {
            $state = [
                'prestashop_sync_version' => $syncVersion,
                'prestashop_sync_error_code' => $this->syncErrorMessage($phase, $exception),
                'prestashop_sync_error_at' => now(),
            ];

            if ($phase === 'webhook') {
                $state['prestashop_payload_hash'] = $payloadHash;
            }

            $vendor->forceFill($state)->saveQuietly();

            throw $exception;
        }
    }

    private function syncErrorMessage(string $phase, Throwable $exception): string
    {
        $message = preg_replace('#https?://[^\s)]+#i', '[url]', $exception->getMessage())
            ?: $exception->getMessage();

        return Str::limit("[{$phase}] {$message}", 250);
    }
}
