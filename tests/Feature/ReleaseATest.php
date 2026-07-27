<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\ConversationThread;
use App\Models\ConversationMessage;
use App\Models\VendorAccount;
use App\Models\User;
use App\Console\Commands\PushBookingBridgeWebhooks;
use App\Services\PrestashopWebhookService;
use Illuminate\Support\Carbon;

class ReleaseATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\BookingBridgeAuth::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_conversation_latest_message_relationship()
    {
        $vendorUser = User::factory()->create();
        $vendorAccount = VendorAccount::withoutEvents(function () use ($vendorUser) {
            return VendorAccount::create([
                'user_id' => $vendorUser->id,
                'company_name' => 'Test Company',
                'status' => 'ACTIVE',
            ]);
        });

        $thread = ConversationThread::create([
            'vendor_account_id' => $vendorAccount->id,
            'prestashop_customer_id' => 123,
            'status' => 'open',
        ]);

        $newestByTime = ConversationMessage::create([
            'conversation_thread_id' => $thread->id,
            'sender_type' => 'vendor',
            'body_original' => 'Newest by time',
            'created_at' => now()->subMinutes(5),
        ]);

        ConversationMessage::create([
            'conversation_thread_id' => $thread->id,
            'sender_type' => 'customer',
            'body_original' => 'Older by time but higher ID',
            'created_at' => now()->subMinutes(10),
        ]);

        $latest = $thread->fresh()->latestMessage;
        $this->assertNotNull($latest);
        $this->assertEquals($newestByTime->id, $latest->id);
    }

    public function test_push_webhooks_command_handles_errors()
    {
        $vendorUser = User::factory()->create();
        $vendorAccount = VendorAccount::withoutEvents(function () use ($vendorUser) {
            return VendorAccount::create([
                'user_id' => $vendorUser->id,
                'company_name' => 'Test Company',
                'status' => 'ACTIVE',
                'prestashop_product_id' => 123,
            ]);
        });

        $mockService = \Mockery::mock(PrestashopWebhookService::class);
        $mockService->shouldReceive('pushVendor')
                    ->andReturn(PrestashopWebhookService::RESULT_ERROR);
        
        $this->app->instance(PrestashopWebhookService::class, $mockService);

        $this->artisan('vendors:push-webhooks')
             ->assertExitCode(\Illuminate\Console\Command::FAILURE);
    }

    public function test_hold_endpoint_uses_europe_rome_for_today()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14 22:30:00', 'UTC'));
        $businessToday = '2026-07-15';
        $yesterday = '2026-07-14';

        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $vendorUser = User::factory()->create();
        $vendor = VendorAccount::withoutEvents(function () use ($vendorUser, $category) {
            return VendorAccount::create([
                'user_id' => $vendorUser->id,
                'category_id' => $category->id,
                'business_name' => 'Test',
                'status' => 'ACTIVE',
                'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
            ]);
        });
        $slot = \App\Models\VendorSlot::create([
            'vendor_account_id' => $vendor->id,
            'slug' => 's1',
            'label' => 'S1',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);
        $offering = \App\Models\Offering::create(['category_id' => $category->id, 'name' => 'Test', 'slug' => 't', 'is_active' => true]);
        
        $responseValid = $this->postJson('/api/slots/hold', [
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'offering_id' => $offering->id,
            'date' => $businessToday,
        ], ['Idempotency-Key' => md5('test2')]);
        
        $responseValid->assertJsonMissingValidationErrors(['date']);

        $responseInvalid = $this->postJson('/api/slots/hold', [
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'offering_id' => $offering->id,
            'date' => $yesterday,
        ], ['Idempotency-Key' => md5('test3')]);

        $responseInvalid->assertStatus(422);
        $responseInvalid->assertJsonValidationErrors(['date']);
    }

    public function test_customer_data_audit_logs_unknown_fields()
    {
        $logSpy = \Illuminate\Support\Facades\Log::spy();

        $response = $this->postJson('/api/slots/confirm', [
            'hold_token' => \Illuminate\Support\Str::uuid()->toString(),
            'prestashop_order_id' => '123',
            'prestashop_order_line_id' => '456',
            'customer_data' => [
                'id_customer' => 1,
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john@test.com',
                'prestashop_order_reference' => 'REF123',
                'delivery_address' => [
                    'company' => 'Comp',
                    'city' => 'Rome',
                    'unknown_address_field' => 'Value',
                ],
                'unknown_customer_field' => 'Value',
            ],
        ], ['Idempotency-Key' => md5('test')]);
        
        // Endpoint will return 404 because hold doesn't exist, which is fine
        // The important part is that validation passes and log is triggered
        
        $logSpy->shouldHaveReceived('warning')
               ->with('Unknown booking customer fields', \Mockery::on(function ($data) {
                   return in_array('unknown_customer_field', $data['keys']);
               }))->once();

        $logSpy->shouldHaveReceived('warning')
               ->with('Unknown booking delivery address fields', \Mockery::on(function ($data) {
                   return in_array('unknown_address_field', $data['keys']);
               }))->once();
    }
}
