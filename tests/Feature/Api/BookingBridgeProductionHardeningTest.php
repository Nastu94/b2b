<?php

namespace Tests\Feature\Api;

use App\Models\VendorAccount;
use App\Models\VendorSlot;
use App\Models\SlotLock;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Offering;
use App\Models\User;
use App\Models\VendorOfferingProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class BookingBridgeProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private string $bridgeKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bridgeKey = 'test-key';
        config([
            'booking_bridge.hmac_mode' => 'off',
            'booking_bridge.key' => $this->bridgeKey,
            'booking_bridge.inbound_key' => $this->bridgeKey,
        ]);

        $this->category = Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'is_active' => true,
        ]);

        $this->app->bind(\App\Services\BookingPricingService::class, function () {
            return new class extends \App\Services\BookingPricingService {
                public function __construct() {}

                public function resolveForBooking(...$args): array
                {
                    return [
                        'pricing_id' => 1,
                        'base_price' => 100,
                        'final_price' => 100,
                        'currency' => 'EUR',
                        'matched_rule_ids' => [],
                        'notes' => [],
                        'ignored_rules' => [],
                        'breakdown' => [],
                    ];
                }
            };
        });

        $this->mock(\App\Services\CommissionResolver::class, function ($mock) {
            $mock->shouldReceive('resolve')->andReturn([
                'is_commission_based' => true,
                'commission_rate' => 10.0,
            ]);
        });
    }

    private function bridgeHeaders(string $idempotencyKey): array
    {
        return [
            'X-Booking-Bridge-Key' => $this->bridgeKey,
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json',
        ];
    }

    /**
     * @return array{VendorAccount, VendorSlot, Offering}
     */
    private function createBookableFixture(CarbonImmutable $date): array
    {
        $vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $this->category->id,
            'business_name' => 'Test Vendor',
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);
        $slot = VendorSlot::create([
            'vendor_account_id' => $vendor->id,
            'slug' => 'morning-1000-1200',
            'label' => 'Morning',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);
        $offering = Offering::create([
            'category_id' => $this->category->id,
            'name' => 'Test Offering',
            'slug' => 'test-offering-'.Str::lower(Str::random(8)),
            'is_active' => true,
        ]);

        $vendor->offerings()->attach($offering->id, ['is_active' => true]);
        VendorOfferingProfile::create([
            'vendor_account_id' => $vendor->id,
            'offering_id' => $offering->id,
            'is_published' => true,
            'is_approved' => true,
        ]);
        $vendor->weeklySchedules()->create([
            'vendor_slot_id' => $slot->id,
            'day_of_week' => $date->dayOfWeek,
            'is_open' => true,
        ]);
        $vendor->leadTimes()->create([
            'day_of_week' => $date->dayOfWeek,
            'min_notice_hours' => 0,
        ]);

        return [$vendor, $slot, $offering];
    }

    public function test_double_hold_prevents_duplicate_slot_locks()
    {
        $date = CarbonImmutable::now()->addDays(5);
        [$vendor, $slot, $offering] = $this->createBookableFixture($date);

        $idempotencyKey = str_repeat('a', 32);

        $payload = [
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'date' => $date->format('Y-m-d'),
            'offering_id' => $offering->id,
            'guests' => 2,
            'distance_km' => 10,
            'event_city' => 'Roma'
        ];

        // First hold should succeed
        $response1 = $this->postJson('/api/slots/hold', $payload, $this->bridgeHeaders($idempotencyKey));
        $response1->assertStatus(201);

        // Second hold with same key should return same token
        $response2 = $this->postJson('/api/slots/hold', $payload, $this->bridgeHeaders($idempotencyKey));
        $response2->assertStatus(200);
        $this->assertEquals($response1->json('data.hold_token'), $response2->json('data.hold_token'));

        // Third hold with different key on same slot should fail (Slot unavailable)
        $response3 = $this->postJson('/api/slots/hold', $payload, $this->bridgeHeaders(str_repeat('b', 32)));
        $response3->assertStatus(409);
    }

    public function test_double_confirm_prevents_duplicate_bookings()
    {
        $date = CarbonImmutable::now()->addDays(5);
        [$vendor, $slot, $offering] = $this->createBookableFixture($date);
        
        $lock = SlotLock::create([
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'offering_id' => $offering->id,
            'date' => $date->format('Y-m-d'),
            'hold_token' => Str::uuid()->toString(),
            'status' => 'HOLD',
            'expires_at' => CarbonImmutable::now()->addMinutes(15),
            'is_active' => true,
            'active_slot_key' => 'key123',
            'idempotency_key' => str_repeat('c', 32),
            'quoted_amount' => 100,
            'currency' => 'EUR',
            'pricing_breakdown' => [],
        ]);

        $payload = [
            'hold_token' => $lock->hold_token,
            'prestashop_order_id' => 'ORDER-123',
            'prestashop_order_line_id' => 'LINE-1',
            'customer_email' => 'test@example.com'
        ];

        // First confirm
        $response1 = $this->postJson('/api/slots/confirm', $payload, $this->bridgeHeaders($lock->idempotency_key));
        $response1->assertStatus(200);

        // Second confirm for same order returns 200 (idempotent)
        $response2 = $this->postJson('/api/slots/confirm', $payload, $this->bridgeHeaders($lock->idempotency_key));
        $response2->assertStatus(200);
        
        $this->assertEquals(1, Booking::where('prestashop_order_id', 'ORDER-123')->count());
        
        // Third confirm for DIFFERENT order returns 409
        $payload['prestashop_order_id'] = 'ORDER-999';
        $response3 = $this->postJson('/api/slots/confirm', $payload, $this->bridgeHeaders($lock->idempotency_key));
        $response3->assertStatus(409);
    }

    public function test_release_after_expiration_fails()
    {
        $date = CarbonImmutable::now()->addDays(5);
        [$vendor, $slot, $offering] = $this->createBookableFixture($date);
        
        $lock = SlotLock::create([
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'offering_id' => $offering->id,
            'date' => $date->format('Y-m-d'),
            'hold_token' => Str::uuid()->toString(),
            'status' => 'HOLD',
            'expires_at' => CarbonImmutable::now()->subMinutes(5), // EXPIRED
            'is_active' => true,
            'active_slot_key' => 'key123',
            'idempotency_key' => str_repeat('d', 32)
        ]);

        $response = $this->postJson('/api/slots/release', [
            'hold_token' => $lock->hold_token,
        ], $this->bridgeHeaders($lock->idempotency_key));

        $response->assertStatus(200);
        $this->assertEquals('EXPIRED', $response->json('data.status'));
    }
}
