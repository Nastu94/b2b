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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlotStatusReconciliationTest extends TestCase
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
        
        config(['booking_bridge.distance_mode' => 'legacy']);
        config(['booking_bridge.hmac_mode' => 'legacy']);

        $category = Category::create(['name' => 'Test', 'slug' => 'test-' . uniqid(), 'is_active' => true]);

        $this->vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'business_name' => 'Test Vendor',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);

        $this->slot = VendorSlot::create([
            'vendor_account_id' => $this->vendor->id,
            'slug' => 'slot-' . uniqid(),
            'label' => 'Slot 1',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);

        $this->offering = Offering::create([
            'category_id' => $category->id,
            'name' => 'Test Offering',
            'slug' => 'test-offering-' . uniqid(),
            'is_active' => true,
        ]);

        $this->vendor->offerings()->attach($this->offering->id, ['is_active' => true]);

        VendorOfferingProfile::create([
            'vendor_account_id' => $this->vendor->id,
            'offering_id' => $this->offering->id,
            'is_published' => true,
            'is_approved' => true,
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

        $this->mock(\App\Services\CommissionResolver::class, function ($mock) {
            $mock->shouldReceive('resolve')->andReturn([
                'is_commission_based' => true,
                'commission_rate' => 10.0,
            ]);
        });
    }

    private function bridgeHeaders(): array
    {
        return [
            'X-Booking-Bridge-Key' => $this->bridgeKey,
            'Accept' => 'application/json',
        ];
    }

    private function createLock($status = SlotLock::STATUS_HOLD, $hoursOffset = 0): SlotLock
    {
        $now = CarbonImmutable::now();
        return SlotLock::create([
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $now->addDays(2)->format('Y-m-d'),
            'hold_token' => (string) Str::uuid(),
            'status' => $status,
            'is_active' => $status === SlotLock::STATUS_HOLD || $status === SlotLock::STATUS_BOOKED,
            'expires_at' => $now->addHours($hoursOffset),
            'quoted_amount' => 100,
            'currency' => 'EUR',
            'active_slot_key' => 'test-key-' . uniqid(),
        ]);
    }

    public function test_lookup_active_hold()
    {
        $lock = $this->createLock(SlotLock::STATUS_HOLD, 1);

        $res = $this->getJson("/api/slots/status?hold_token={$lock->hold_token}", $this->bridgeHeaders());
        $res->assertStatus(200)
            ->assertJsonPath('status', 'HOLD')
            ->assertJsonStructure(['expires_at']);
    }

    public function test_lookup_expired_hold()
    {
        $lock = $this->createLock(SlotLock::STATUS_EXPIRED, -1);

        $res = $this->getJson("/api/slots/status?hold_token={$lock->hold_token}", $this->bridgeHeaders());
        $res->assertStatus(200)->assertJsonPath('status', 'EXPIRED');
    }

    public function test_lookup_cancelled_hold()
    {
        $lock = $this->createLock(SlotLock::STATUS_CANCELLED, 1);

        $res = $this->getJson("/api/slots/status?hold_token={$lock->hold_token}", $this->bridgeHeaders());
        $res->assertStatus(200)->assertJsonPath('status', 'CANCELLED');
    }

    public function test_lookup_booked()
    {
        $lock = $this->createLock(SlotLock::STATUS_BOOKED, 1);
        $booking = Booking::create([
            'slot_lock_id' => $lock->id,
            'vendor_account_id' => $this->vendor->id,
            'offering_id' => $this->offering->id,
            'vendor_slot_id' => $this->slot->id,
            'event_date' => $lock->date,
            'prestashop_order_id' => 'ORD-123',
            'prestashop_order_line_id' => 'LINE-1',
            'total_amount' => 100,
            'currency' => 'EUR',
            'status' => Booking::STATUS_PENDING_VENDOR_CONFIRMATION,
            'is_commission_based' => true,
            'commission_rate' => 10.0,
            'commission_amount' => 10,
        ]);

        $res = $this->getJson("/api/slots/status?hold_token={$lock->hold_token}&prestashop_order_id=ORD-123", $this->bridgeHeaders());
        $res->assertStatus(200)
            ->assertJsonPath('status', 'BOOKED')
            ->assertJsonPath('booking_id', $booking->id);
    }

    public function test_lookup_booked_wrong_order_reference()
    {
        $lock = $this->createLock(SlotLock::STATUS_BOOKED, 1);
        Booking::create([
            'slot_lock_id' => $lock->id,
            'vendor_account_id' => $this->vendor->id,
            'offering_id' => $this->offering->id,
            'vendor_slot_id' => $this->slot->id,
            'event_date' => $lock->date,
            'prestashop_order_id' => 'ORD-123',
            'prestashop_order_line_id' => 'LINE-1',
            'total_amount' => 100,
            'currency' => 'EUR',
            'status' => Booking::STATUS_PENDING_VENDOR_CONFIRMATION,
            'is_commission_based' => true,
            'commission_rate' => 10.0,
            'commission_amount' => 10,
        ]);

        $res = $this->getJson("/api/slots/status?hold_token={$lock->hold_token}&prestashop_order_id=ORD-999", $this->bridgeHeaders());
        $res->assertStatus(409)
            ->assertJsonPath('code', 'ORDER_REFERENCE_MISMATCH');
    }

    public function test_reacquire_disabled_fails()
    {
        config(['booking_bridge.allow_expired_hold_reacquire' => false]);
        $lock = $this->createLock(SlotLock::STATUS_HOLD, -1); // Scaduto nel tempo
        $lock->status = SlotLock::STATUS_EXPIRED;
        $lock->save();

        $res = $this->postJson('/api/slots/confirm', [
            'hold_token' => $lock->hold_token,
            'prestashop_order_id' => 'ORD-1',
            'prestashop_order_line_id' => 'LINE-1',
        ], $this->bridgeHeaders());

        $res->assertStatus(409) // LockTerminatedException
            ->assertJsonPath('code', 'LOCK_TERMINATED');
    }

    public function test_reacquire_enabled_and_slot_free()
    {
        config(['booking_bridge.allow_expired_hold_reacquire' => true]);
        $lock = $this->createLock(SlotLock::STATUS_EXPIRED, -1);

        // Mock availability (libero)
        $this->mock(\App\Services\AvailabilityService::class, function ($mock) {
            $mock->shouldReceive('assertSlotBookable')->andReturn(true);
        });

        $res = $this->postJson('/api/slots/confirm', [
            'hold_token' => $lock->hold_token,
            'prestashop_order_id' => 'ORD-1',
            'prestashop_order_line_id' => 'LINE-1',
        ], $this->bridgeHeaders());

        $res->assertStatus(200)
            ->assertJsonPath('data.status', 'BOOKED');
    }

    public function test_reacquire_enabled_but_slot_occupied()
    {
        config(['booking_bridge.allow_expired_hold_reacquire' => true]);
        $lock = $this->createLock(SlotLock::STATUS_EXPIRED, -1);

        // Mock availability (occupato)
        $this->mock(\App\Services\AvailabilityService::class, function ($mock) {
            $mock->shouldReceive('assertSlotBookable')->andThrow(new \App\Exceptions\SlotUnavailableException());
        });

        $res = $this->postJson('/api/slots/confirm', [
            'hold_token' => $lock->hold_token,
            'prestashop_order_id' => 'ORD-1',
            'prestashop_order_line_id' => 'LINE-1',
        ], $this->bridgeHeaders());

        $res->assertStatus(409)
            ->assertJsonPath('code', 'PAID_ORDER_SLOT_UNAVAILABLE');
    }
}
