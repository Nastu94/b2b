<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessPrestashopBookingBridgeOutboxTest extends TestCase
{
    public function test_it_calls_prestashop_with_the_cron_token_in_a_header(): void
    {
        $url = 'https://prestashop.test/module/bookingbridge/cron';
        $token = 'test-cron-token-with-at-least-32-characters';

        config()->set('services.prestashop.bookingbridge_cron_url', $url);
        config()->set('services.prestashop.bookingbridge_cron_token', $token);
        config()->set('services.prestashop.bookingbridge_cron_timeout', 10);

        Http::fake([
            $url => Http::response(['success' => true, 'processed' => 2]),
        ]);

        $this->artisan('prestashop:process-bookingbridge-outbox')
            ->expectsOutput('Cron BookingBridge completato. Righe elaborate: 2.')
            ->assertSuccessful();

        Http::assertSent(function (Request $request) use ($url, $token): bool {
            return $request->url() === $url
                && $request->hasHeader('X-BookingBridge-Cron-Token', $token)
                && ! str_contains($request->url(), $token);
        });
    }

    public function test_it_fails_without_sending_a_request_when_configuration_is_missing(): void
    {
        config()->set('services.prestashop.bookingbridge_cron_url', null);
        config()->set('services.prestashop.bookingbridge_cron_token', null);
        Http::preventStrayRequests();

        $this->artisan('prestashop:process-bookingbridge-outbox')
            ->expectsOutput('Cron BookingBridge non configurato: URL o token mancante.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_it_fails_when_prestashop_rejects_the_request(): void
    {
        $url = 'https://prestashop.test/module/bookingbridge/cron';

        config()->set('services.prestashop.bookingbridge_cron_url', $url);
        config()->set('services.prestashop.bookingbridge_cron_token', 'test-cron-token-with-at-least-32-characters');

        Http::fake([
            $url => Http::response(['success' => false], 403),
        ]);

        $this->artisan('prestashop:process-bookingbridge-outbox')
            ->expectsOutput('Cron BookingBridge fallito con stato HTTP 403.')
            ->assertFailed();
    }
}
