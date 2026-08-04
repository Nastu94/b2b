<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Offering;
use App\Models\SlotLock;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Models\VendorSlot;
use App\Models\Conversation;
use App\Models\Message;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingBridgeCurrentContractTest extends TestCase
{
    use RefreshDatabase;

    private VendorAccount $vendor;
    private VendorSlot $slot;
    private Offering $offering;
    private string $bridgeKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bridgeKey = config('booking_bridge.key', 'test-key');
        if (empty(config('booking_bridge.key'))) {
            config(['booking_bridge.key' => $this->bridgeKey]);
        }

        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);

        $this->vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'business_name' => 'Test Vendor',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);

        $this->slot = VendorSlot::create([
            'vendor_account_id' => $this->vendor->id,
            'slug' => 'slot-1',
            'label' => 'Slot 1',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);

        $this->offering = Offering::create([
            'category_id' => $category->id,
            'name' => 'Test Offering',
            'slug' => 'test-offering',
            'is_active' => true,
        ]);

        $this->vendor->offerings()->attach($this->offering->id, ['is_active' => true]);

        VendorOfferingProfile::create([
            'vendor_account_id' => $this->vendor->id,
            'offering_id' => $this->offering->id,
            'is_published' => true,
            'is_approved' => true,
        ]);

        $this->vendor->weeklySchedules()->create([
            'vendor_slot_id' => $this->slot->id,
            'day_of_week' => CarbonImmutable::now()->addDays(2)->dayOfWeek,
            'is_open' => true,
        ]);
        $this->vendor->leadTimes()->create([
            'day_of_week' => CarbonImmutable::now()->addDays(2)->dayOfWeek,
            'min_notice_hours' => 0,
        ]);

        $this->app->bind(\App\Services\BookingPricingService::class, function() {
            return new class extends \App\Services\BookingPricingService {
                public function __construct() {}
                public function resolveForBooking(...$args): array {
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

        // Mock CommissionResolver
        $this->mock(\App\Services\CommissionResolver::class, function ($mock) {
            $mock->shouldReceive('resolve')->andReturn([
                'is_commission_based' => true,
                'commission_rate' => 10.0,
            ]);
        });
    }

    private function bridgeHeaders(?string $idempotencyKey = null): array
    {
        $headers = [
            'X-Booking-Bridge-Key' => $this->bridgeKey,
            'Accept' => 'application/json',
        ];
        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }
        return $headers;
    }

    public function test_auth_missing_key_returns_401()
    {
        $res = $this->getJson('/api/vendors/search');
        $res->assertStatus(401)
            ->assertJson(['success' => false, 'error' => 'Unauthorized']);
    }

    public function test_vendor_search_contract()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $res = $this->getJson("/api/vendors/search?date={$date}&guests=2&city=TestCity", $this->bridgeHeaders());
        
        $res->assertStatus(200);
    }

    public function test_availability_contract()
    {
        $fromDate = CarbonImmutable::now()->format('Y-m-d');
        $toDate = CarbonImmutable::now()->addDays(7)->format('Y-m-d');
        
        $res = $this->getJson("/api/availability?vendor_account_id={$this->vendor->id}&from={$fromDate}&to={$toDate}", $this->bridgeHeaders());
        $res->assertStatus(200);
    }

    public function test_hold_and_confirm_idempotency_contract()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $idempotencyKey = str_repeat('a', 32);

        // 1. Hold
        $holdRes = $this->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
        ], $this->bridgeHeaders($idempotencyKey));

        $holdRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'hold_token',
                    'expires_at',
                    'pricing' => [
                        'final_price',
                        'currency',
                    ]
                ]
            ]);
            
        $holdToken = $holdRes->json('data.hold_token');

        // 2. Second Hold (Idempotent)
        $holdRes2 = $this->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
        ], $this->bridgeHeaders($idempotencyKey));

        $holdRes2->assertStatus(200)
            ->assertJsonPath('data.hold_token', $holdToken);

        // 3. Confirm
        $confirmRes = $this->postJson('/api/slots/confirm', [
            'hold_token' => $holdToken,
            'prestashop_order_id' => 'ORD-123',
            'prestashop_order_line_id' => 'LINE-1',
        ], $this->bridgeHeaders($idempotencyKey));

        $confirmRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'BOOKED');

        // 4. Double Confirm (Idempotent)
        $confirmRes2 = $this->postJson('/api/slots/confirm', [
            'hold_token' => $holdToken,
            'prestashop_order_id' => 'ORD-123',
            'prestashop_order_line_id' => 'LINE-1',
        ], $this->bridgeHeaders($idempotencyKey));

        $confirmRes2->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'BOOKED');
    }

    public function test_release_contract()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $idempotencyKey = str_repeat('c', 32);

        $holdRes = $this->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
        ], $this->bridgeHeaders($idempotencyKey));

        $holdToken = $holdRes->json('data.hold_token');

        $releaseRes = $this->postJson('/api/slots/release', [
            'hold_token' => $holdToken,
        ], $this->bridgeHeaders($idempotencyKey));

        $releaseRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'CANCELLED');

        // Terminal release repeated
        $releaseRes2 = $this->postJson('/api/slots/release', [
            'hold_token' => $holdToken,
        ], $this->bridgeHeaders($idempotencyKey));

        $releaseRes2->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELLED');
    }

    public function test_chat_contract()
    {
        $this->markTestSkipped('Richiede factory di conversazione/messaggi pronti.');
    }
}
