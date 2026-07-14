<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Offering;
use App\Models\SlotLock;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Models\VendorSlot;
use App\Models\VendorWeeklySchedule;
use App\Models\VendorLeadTime;
use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class SlotCapacityModeTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    private VendorAccount $djVendor;
    private VendorAccount $noleggioVendor;
    private VendorAccount $locationVendor;

    private VendorSlot $djSlot;
    private VendorSlot $noleggioSlot;
    private VendorSlot $locationSlot;

    private Offering $djSetOffering;
    private Offering $djMatrimonioOffering;
    private Offering $limousineOffering;
    private Offering $partyBusOffering;
    private Offering $salaPremiumOffering;
    private Offering $salaRealeOffering;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true, 'commission_rate' => 15]);
        $user1 = User::factory()->create();

        // 1. DJ Vendor (single_resource)
        $this->djVendor = VendorAccount::create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);
        $this->djSlot = VendorSlot::create([
            'vendor_account_id' => $this->djVendor->id,
            'slug' => 'dj-18',
            'label' => 'Ore 18',
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);
        $this->djSetOffering = Offering::create(['category_id' => $category->id, 'name' => 'DJ Set', 'slug' => 'dj-set', 'is_active' => true]);
        $this->djMatrimonioOffering = Offering::create(['category_id' => $category->id, 'name' => 'DJ Matrimonio', 'slug' => 'dj-matrimonio', 'is_active' => true]);
        $this->djVendor->offerings()->attach($this->djSetOffering->id, ['is_active' => true]);
        $this->djVendor->offerings()->attach($this->djMatrimonioOffering->id, ['is_active' => true]);
        VendorOfferingProfile::create(['vendor_account_id' => $this->djVendor->id, 'offering_id' => $this->djSetOffering->id, 'is_published' => true, 'is_approved' => true]);
        VendorOfferingProfile::create(['vendor_account_id' => $this->djVendor->id, 'offering_id' => $this->djMatrimonioOffering->id, 'is_published' => true, 'is_approved' => true]);
        $this->setupSchedules($this->djVendor, $this->djSlot);

        // 2. Noleggio Vendor (multiple_by_offering)
        $user2 = User::factory()->create();
        $this->noleggioVendor = VendorAccount::create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_MULTIPLE_BY_OFFERING,
        ]);
        $this->noleggioSlot = VendorSlot::create([
            'vendor_account_id' => $this->noleggioVendor->id,
            'slug' => 'noleggio-18',
            'label' => 'Ore 18',
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);
        $this->limousineOffering = Offering::create(['category_id' => $category->id, 'name' => 'Limousine', 'slug' => 'limousine', 'is_active' => true]);
        $this->partyBusOffering = Offering::create(['category_id' => $category->id, 'name' => 'Party Bus', 'slug' => 'party-bus', 'is_active' => true]);
        $this->noleggioVendor->offerings()->attach($this->limousineOffering->id, ['is_active' => true]);
        $this->noleggioVendor->offerings()->attach($this->partyBusOffering->id, ['is_active' => true]);
        VendorOfferingProfile::create(['vendor_account_id' => $this->noleggioVendor->id, 'offering_id' => $this->limousineOffering->id, 'is_published' => true, 'is_approved' => true]);
        VendorOfferingProfile::create(['vendor_account_id' => $this->noleggioVendor->id, 'offering_id' => $this->partyBusOffering->id, 'is_published' => true, 'is_approved' => true]);
        $this->setupSchedules($this->noleggioVendor, $this->noleggioSlot);

        // 3. Location Vendor (multiple_by_offering)
        $user3 = User::factory()->create();
        $this->locationVendor = VendorAccount::create([
            'user_id' => $user3->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_MULTIPLE_BY_OFFERING,
        ]);
        $this->locationSlot = VendorSlot::create([
            'vendor_account_id' => $this->locationVendor->id,
            'slug' => 'location-20',
            'label' => 'Ore 20',
            'start_time' => '20:00:00',
            'end_time' => '23:59:59',
            'is_active' => true,
        ]);
        $this->salaPremiumOffering = Offering::create(['category_id' => $category->id, 'name' => 'Sala Premium', 'slug' => 'sala-premium', 'is_active' => true]);
        $this->salaRealeOffering = Offering::create(['category_id' => $category->id, 'name' => 'Sala Reale', 'slug' => 'sala-reale', 'is_active' => true]);
        $this->locationVendor->offerings()->attach($this->salaPremiumOffering->id, ['is_active' => true]);
        $this->locationVendor->offerings()->attach($this->salaRealeOffering->id, ['is_active' => true]);
        VendorOfferingProfile::create(['vendor_account_id' => $this->locationVendor->id, 'offering_id' => $this->salaPremiumOffering->id, 'is_published' => true, 'is_approved' => true]);
        VendorOfferingProfile::create(['vendor_account_id' => $this->locationVendor->id, 'offering_id' => $this->salaRealeOffering->id, 'is_published' => true, 'is_approved' => true]);
        $this->setupSchedules($this->locationVendor, $this->locationSlot);

        // Mock BookingPricingService in the container so it returns a fixed price
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

    private function setupSchedules(VendorAccount $vendor, VendorSlot $slot): void
    {
        for ($i = 0; $i <= 6; $i++) {
            VendorWeeklySchedule::create([
                'vendor_account_id' => $vendor->id,
                'vendor_slot_id' => $slot->id,
                'day_of_week' => $i,
                'is_open' => true,
            ]);
            VendorLeadTime::create([
                'vendor_account_id' => $vendor->id,
                'day_of_week' => $i,
                'min_notice_hours' => 0,
            ]);
        }
    }

    public function test_dj_single_resource_capacity_mode()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $user = User::factory()->create();

        // 1. DJ Set ore 18 OK
        $res1 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->djVendor->id,
            'vendor_slot_id' => $this->djSlot->id,
            'offering_id' => $this->djSetOffering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']);
        if ($res1->status() !== 201) { dump($res1->json()); }
        $res1->assertStatus(201);
        $this->assertEquals(SlotLock::STATUS_HOLD, SlotLock::first()->status);

        // 2. DJ Matrimonio ore 18 KO (bloccato dal primo hold perché single_resource)
        $res2 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->djVendor->id,
            'vendor_slot_id' => $this->djSlot->id,
            'offering_id' => $this->djMatrimonioOffering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb']);
        
        $res2->assertStatus(409); // Let's expect 409 because SlotUnavailableException returns 409
    }

    public function test_noleggio_multiple_by_offering_capacity_mode()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $user = User::factory()->create();

        // 1. Limousine ore 18 OK
        $res1 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->noleggioVendor->id,
            'vendor_slot_id' => $this->noleggioSlot->id,
            'offering_id' => $this->limousineOffering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'cccccccccccccccccccccccccccccccc']);
        $res1->assertStatus(201);

        // 2. Party Bus ore 18 OK
        $res2 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->noleggioVendor->id,
            'vendor_slot_id' => $this->noleggioSlot->id,
            'offering_id' => $this->partyBusOffering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'dddddddddddddddddddddddddddddddd']);
        $res2->assertStatus(201); // Works because it's a different offering

        // 3. Limousine ore 18 seconda volta (Idempotency Replay)
        // Riceve 200 con lo stesso hold_token, poiché l'API non distingue cart_id o client_id diversi.
        // E' il comportamento corretto e desiderato per le retry di rete di PrestaShop.
        $res3 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->noleggioVendor->id,
            'vendor_slot_id' => $this->noleggioSlot->id,
            'offering_id' => $this->limousineOffering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'cccccccccccccccccccccccccccccccc']);
        
        $res3->assertStatus(200); 
        $this->assertCount(2, SlotLock::where('vendor_account_id', $this->noleggioVendor->id)->get());
        $this->assertEquals($res1->json('data.hold_token'), $res3->json('data.hold_token'));
    }

    public function test_location_multiple_by_offering_capacity_mode()
    {
        $date = CarbonImmutable::now()->addDays(2)->format('Y-m-d');
        $user = User::factory()->create();

        // 1. Sala Premium ore 20 OK
        $res1 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->locationVendor->id,
            'vendor_slot_id' => $this->locationSlot->id,
            'offering_id' => $this->salaPremiumOffering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee']);
        $res1->assertStatus(201);

        // 2. Sala Reale ore 20 OK
        $res2 = $this->actingAs($user)->postJson('/api/slots/hold', [
            'vendor_account_id' => $this->locationVendor->id,
            'vendor_slot_id' => $this->locationSlot->id,
            'offering_id' => $this->salaRealeOffering->id,
            'date' => $date,
        ], ['Idempotency-Key' => 'ffffffffffffffffffffffffffffffff']);
        $res2->assertStatus(201);

        // 3. Confirm order retains offering_id in active_slot_key
        $holdData = $res1->json('data');
        $lock = SlotLock::find($holdData['lock_id']);
        $expectedKey = $this->locationVendor->id . ':' . $this->locationSlot->id . ':' . $date . ':' . $this->salaPremiumOffering->id;
        
        $this->assertEquals($expectedKey, $lock->active_slot_key);

        $res3 = $this->actingAs($user)->postJson('/api/slots/confirm', [
            'hold_token' => $holdData['hold_token'],
            'prestashop_order_id' => '12345',
            'prestashop_order_line_id' => '1',
        ], ['Idempotency-Key' => 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee']);
        $res3->assertStatus(200);

        $lock->refresh();
        $this->assertTrue($lock->isBooked());
        $this->assertEquals($expectedKey, $lock->active_slot_key);
    }
}
