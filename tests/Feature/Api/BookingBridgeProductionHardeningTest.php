<?php

namespace Tests\Feature\Api;

use App\Models\VendorAccount;
use App\Models\VendorSlot;
use App\Models\SlotLock;
use App\Models\Booking;
use App\Models\Offering;
use App\Models\VendorOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class BookingBridgeProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['booking_bridge.hmac_mode' => 'off']); // Disable HMAC for tests unless specifically testing it
    }

    public function test_double_hold_prevents_duplicate_slot_locks()
    {
        $vendor = VendorAccount::create(['user_id' => \App\Models\User::factory()->create()->id, 'company_name' => 'Test Vendor', 'status' => 'active']);
        $slot = VendorSlot::create(['vendor_account_id' => $vendor->id, 'label' => 'Morning', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);
        $offering = Offering::create(['name' => 'Test Offering']);
        VendorOffering::create(['vendor_account_id' => $vendor->id, 'offering_id' => $offering->id, 'price' => 100]);

        $idempotencyKey = Str::uuid()->toString();

        $payload = [
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'date' => CarbonImmutable::now()->addDays(5)->format('Y-m-d'),
            'offering_id' => $offering->id,
            'guests' => 2,
            'distance_km' => 10,
            'event_city' => 'Roma'
        ];

        // First hold should succeed
        $response1 = $this->postJson('/api/slots/hold', $payload, [
            'Idempotency-Key' => $idempotencyKey
        ]);
        $response1->assertStatus(200);

        // Second hold with same key should return same token
        $response2 = $this->postJson('/api/slots/hold', $payload, [
            'Idempotency-Key' => $idempotencyKey
        ]);
        $response2->assertStatus(200);
        $this->assertEquals($response1->json('data.hold_token'), $response2->json('data.hold_token'));

        // Third hold with different key on same slot should fail (Slot unavailable)
        $response3 = $this->postJson('/api/slots/hold', $payload, [
            'Idempotency-Key' => Str::uuid()->toString()
        ]);
        $response3->assertStatus(409);
    }

    public function test_double_confirm_prevents_duplicate_bookings()
    {
        $vendor = VendorAccount::create(['user_id' => \App\Models\User::factory()->create()->id, 'company_name' => 'Test Vendor', 'status' => 'active']);
        $slot = VendorSlot::create(['vendor_account_id' => $vendor->id, 'label' => 'Morning', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);
        
        $lock = SlotLock::create([
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'date' => CarbonImmutable::now()->addDays(5)->format('Y-m-d'),
            'hold_token' => Str::uuid()->toString(),
            'status' => 'HOLD',
            'expires_at' => CarbonImmutable::now()->addMinutes(15),
            'is_active' => true,
            'active_slot_key' => 'key123',
            'idempotency_key' => Str::uuid()->toString()
        ]);

        $payload = [
            'hold_token' => $lock->hold_token,
            'prestashop_order_id' => 'ORDER-123',
            'prestashop_order_line_id' => 'LINE-1',
            'customer_email' => 'test@example.com'
        ];

        // First confirm
        $response1 = $this->postJson('/api/slots/confirm', $payload, [
            'Idempotency-Key' => $lock->idempotency_key
        ]);
        $response1->assertStatus(200);

        // Second confirm for same order returns 200 (idempotent)
        $response2 = $this->postJson('/api/slots/confirm', $payload, [
            'Idempotency-Key' => $lock->idempotency_key
        ]);
        $response2->assertStatus(200);
        
        $this->assertEquals(1, Booking::where('prestashop_order_id', 'ORDER-123')->count());
        
        // Third confirm for DIFFERENT order returns 409
        $payload['prestashop_order_id'] = 'ORDER-999';
        $response3 = $this->postJson('/api/slots/confirm', $payload, [
            'Idempotency-Key' => $lock->idempotency_key
        ]);
        $response3->assertStatus(409);
    }

    public function test_release_after_expiration_fails()
    {
        $vendor = VendorAccount::create(['user_id' => \App\Models\User::factory()->create()->id, 'company_name' => 'Test Vendor', 'status' => 'active']);
        $slot = VendorSlot::create(['vendor_account_id' => $vendor->id, 'label' => 'Morning', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);
        
        $lock = SlotLock::create([
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'date' => CarbonImmutable::now()->addDays(5)->format('Y-m-d'),
            'hold_token' => Str::uuid()->toString(),
            'status' => 'HOLD',
            'expires_at' => CarbonImmutable::now()->subMinutes(5), // EXPIRED
            'is_active' => true,
            'active_slot_key' => 'key123',
            'idempotency_key' => Str::uuid()->toString()
        ]);

        $response = $this->postJson('/api/slots/release', [
            'hold_token' => $lock->hold_token
        ], [
            'Idempotency-Key' => $lock->idempotency_key
        ]);

        $response->assertStatus(200);
        $this->assertEquals('EXPIRED', $response->json('data.status'));
    }
}
