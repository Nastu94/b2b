<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPrestashopBookingBridgeOutbox extends Command
{
    protected $signature = 'prestashop:process-bookingbridge-outbox';

    protected $description = 'Richiede a PrestaShop di elaborare le conferme BookingBridge in outbox';

    public function handle(): int
    {
        $url = trim((string) config('services.prestashop.bookingbridge_cron_url'));
        $token = trim((string) config('services.prestashop.bookingbridge_cron_token'));
        $timeout = max(1, (int) config('services.prestashop.bookingbridge_cron_timeout', 60));

        if ($url === '' || $token === '') {
            $this->error('Cron BookingBridge non configurato: URL o token mancante.');

            return self::FAILURE;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-BookingBridge-Cron-Token' => $token])
                ->timeout($timeout)
                ->get($url);
        } catch (Throwable $exception) {
            Log::error('Richiesta al cron BookingBridge PrestaShop fallita.', [
                'exception' => $exception->getMessage(),
            ]);
            $this->error('Cron BookingBridge non raggiungibile.');

            return self::FAILURE;
        }

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload) || ($payload['success'] ?? false) !== true) {
            Log::error('Il cron BookingBridge PrestaShop ha restituito un errore.', [
                'status' => $response->status(),
            ]);
            $this->error('Cron BookingBridge fallito con stato HTTP '.$response->status().'.');

            return self::FAILURE;
        }

        $this->info('Cron BookingBridge completato. Righe elaborate: '.(int) ($payload['processed'] ?? 0).'.');

        return self::SUCCESS;
    }
}
