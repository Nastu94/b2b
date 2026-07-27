<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingBridgeHmacAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private string $bridgeKey = 'test-legacy-key';
    private string $hmacSecret = 'test-hmac-secret';

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'booking_bridge.key' => $this->bridgeKey,
            'booking_bridge.inbound_key' => $this->bridgeKey,
            'booking_bridge.hmac_secret_inbound' => $this->hmacSecret,
        ]);
    }

    private function generateHmacHeaders(string $method, string $path, array $payload, int $timestampOffset = 0, ?string $nonce = null, ?string $overrideSecret = null): array
    {
        $timestamp = (string) (time() + $timestampOffset);
        $nonce = $nonce ?? (string) Str::uuid();
        $secret = $overrideSecret ?? $this->hmacSecret;
        $body = json_encode($payload);

        $canonical = implode("\n", [
            $timestamp,
            $nonce,
            strtoupper($method),
            $path,
            hash('sha256', $body)
        ]);
        
        $signature = hash_hmac('sha256', $canonical, $secret);

        return [
            'X-Booking-Bridge-Timestamp' => $timestamp,
            'X-Booking-Bridge-Nonce' => $nonce,
            'X-Booking-Bridge-Signature' => $signature,
            'X-Booking-Bridge-Version' => '2',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function test_legacy_mode_accepts_valid_legacy_key()
    {
        config(['booking_bridge.hmac_mode' => 'off']);
        
        $response = $this->getJson('/api/vendors/search', [
            'X-Booking-Bridge-Key' => $this->bridgeKey,
            'Accept' => 'application/json',
        ]);
        
        // 422 because of missing parameters, but not 401
        $this->assertNotEquals(401, $response->status());
    }

    public function test_legacy_mode_rejects_invalid_legacy_key()
    {
        config(['booking_bridge.hmac_mode' => 'off']);
        
        $response = $this->getJson('/api/vendors/search', [
            'X-Booking-Bridge-Key' => 'wrong-key',
            'Accept' => 'application/json',
        ]);
        
        $response->assertStatus(401);
    }

    public function test_optional_mode_accepts_valid_legacy_key()
    {
        config(['booking_bridge.hmac_mode' => 'optional']);
        
        $response = $this->getJson('/api/vendors/search', [
            'X-Booking-Bridge-Key' => $this->bridgeKey,
            'Accept' => 'application/json',
        ]);
        
        $this->assertNotEquals(401, $response->status());
    }

    public function test_optional_mode_accepts_valid_hmac()
    {
        config(['booking_bridge.hmac_mode' => 'optional']);
        
        $headers = $this->generateHmacHeaders('GET', '/api/vendors/search', []);
        
        $response = $this->getJson('/api/vendors/search', $headers);
        
        $this->assertNotEquals(401, $response->status());
    }

    public function test_optional_mode_rejects_invalid_hmac_even_if_legacy_key_is_valid()
    {
        // If they send a signature, it MUST be valid
        config(['booking_bridge.hmac_mode' => 'optional']);
        
        $headers = $this->generateHmacHeaders('GET', '/api/vendors/search', [], 0, null, 'wrong-secret');
        $headers['X-Booking-Bridge-Key'] = $this->bridgeKey; // Valid legacy key
        
        $response = $this->getJson('/api/vendors/search', $headers);
        
        $response->assertStatus(401);
    }

    public function test_required_mode_rejects_valid_legacy_key()
    {
        config(['booking_bridge.hmac_mode' => 'required']);
        
        $response = $this->getJson('/api/vendors/search', [
            'X-Booking-Bridge-Key' => $this->bridgeKey,
            'Accept' => 'application/json',
        ]);
        
        $response->assertStatus(401);
    }

    public function test_required_mode_accepts_valid_hmac()
    {
        config(['booking_bridge.hmac_mode' => 'required']);
        
        $headers = $this->generateHmacHeaders('GET', '/api/vendors/search', []);
        
        $response = $this->getJson('/api/vendors/search', $headers);
        
        $this->assertNotEquals(401, $response->status());
    }

    public function test_hmac_rejects_expired_timestamp()
    {
        config(['booking_bridge.hmac_mode' => 'required']);
        
        $headers = $this->generateHmacHeaders('GET', '/api/vendors/search', [], -400); // Exceeds 300s window
        
        $response = $this->getJson('/api/vendors/search', $headers);
        
        $response->assertStatus(401);
    }
    
    public function test_hmac_rejects_future_timestamp()
    {
        config(['booking_bridge.hmac_mode' => 'required']);
        
        $headers = $this->generateHmacHeaders('GET', '/api/vendors/search', [], 400); // Exceeds 300s window
        
        $response = $this->getJson('/api/vendors/search', $headers);
        
        $response->assertStatus(401);
    }

    public function test_hmac_rejects_replay_attack()
    {
        config(['booking_bridge.hmac_mode' => 'required']);
        
        $nonce = (string) Str::uuid();
        $headers = $this->generateHmacHeaders('GET', '/api/vendors/search', [], 0, $nonce);
        
        // First request is accepted
        $response1 = $this->getJson('/api/vendors/search', $headers);
        $this->assertNotEquals(401, $response1->status());
        
        // Second request with same nonce is rejected
        $response2 = $this->getJson('/api/vendors/search', $headers);
        $response2->assertStatus(401);
    }
}
