<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Offering;
use App\Models\SlotLock;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Models\VendorSlot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdempotencyAndValidationTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    private VendorAccount $vendor;
    private VendorSlot $slot;
    private Offering $offering;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Setup base schedule
        $this->vendor->weeklySchedules()->create([
            'vendor_slot_id' => $this->slot->id,
            'day_of_week' => CarbonImmutable::now()->addDays(2)->dayOfWeekIso,
            'is_open' => true,
        ]);
        $this->vendor->leadTimes()->create([
            'day_of_week' => CarbonImmutable::now()->addDays(2)->dayOfWeekIso,
            'min_notice_hours' => 0,
        ]);

        // Mock BookingPricingService
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
    }

    public function test_invalid_idempotency_key_format()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'invalid-key']);

        $res->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_IDEMPOTENCY_KEY');
    }

    public function test_distance_km_normalization()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $user = User::factory()->create();
        $idempotencyKey = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        // 1. Hold with distance_km 10
        $res1 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
            'distance_km' => 10,
        ], ['Idempotency-Key' => $idempotencyKey]);

        $res1->assertStatus(201);

        // 2. Retry with distance_km 10.00
        $res2 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
            'distance_km' => 10.00,
        ], ['Idempotency-Key' => $idempotencyKey]);

        $res2->assertStatus(200) // Replay success
            ->assertJsonPath('data.hold_token', $res1->json('data.hold_token'));
    }

    public function test_retry_on_expired_lock_returns_lock_terminated()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $user = User::factory()->create();
        $idempotencyKey = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

        $res1 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
        ], ['Idempotency-Key' => $idempotencyKey]);

        $res1->assertStatus(201);
        
        $lock = SlotLock::first();
        $lock->update(['expires_at' => CarbonImmutable::now()->subMinutes(5)]);

        $res2 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->vendor->id,
            'vendor_slot_id' => $this->slot->id,
            'offering_id' => $this->offering->id,
            'date' => $date,
        ], ['Idempotency-Key' => $idempotencyKey]);

        $res2->assertStatus(409)
            ->assertJsonPath('code', 'LOCK_TERMINATED');
    }

    public function test_availability_without_offering_for_inactive_vendor()
    {
        $this->vendor->update(['status' => 'PENDING']);
        
        $user = User::factory()->create();
        $fromDate = CarbonImmutable::now()->format('Y-m-d');
        $toDate = CarbonImmutable::now()->addDays(7)->format('Y-m-d');

        $res = $this->actingAs($user)->getJson("/api/availability?vendor_account_id={$this->vendor->id}&from={$fromDate}&to={$toDate}");

        $res->assertStatus(422)
            ->assertJsonPath('error', 'Vendor non trovato, inattivo o eliminato');
    }
}
