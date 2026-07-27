<?php

namespace Tests\Feature\Api;

use App\Models\ConversationThread;
use App\Models\VendorAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class BookingBridgeChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('booking_bridge.hmac_mode', 'off'); // test everything without HMAC enforcement for simplicity
        
        // Reset rate limiter
        RateLimiter::clear('bookingbridge-chat');
    }

    protected function createConversation(int $customerId, int $vendorId): ConversationThread
    {
        return ConversationThread::create([
            'vendor_account_id' => $vendorId,
            'prestashop_customer_id' => $customerId,
            'status' => 'open',
            'customer_unread_count' => 0,
            'vendor_unread_count' => 0,
            'admin_unread_count' => 0,
            'last_message_at' => Carbon::now(),
        ]);
    }

    public function test_conversations_index_returns_paginated_and_legacy_data()
    {
        $vendor = VendorAccount::create(['user_id' => \App\Models\User::factory()->create()->id, 'company_name' => 'Test Vendor', 'status' => 'active']);
        $customerId = 999;
        
        // Create 25 conversations
        for ($i = 0; $i < 25; $i++) {
            $this->createConversation($customerId, $vendor->id);
        }

        $response = $this->getJson("/api/conversations?prestashop_customer_id={$customerId}&per_page=10");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'conversations', // legacy
                     'data',          // new paginated
                     'meta' => [      // pagination meta
                         'current_page',
                         'last_page',
                         'per_page',
                         'total'
                     ]
                 ]);

        // Assert pagination works
        $this->assertCount(10, $response->json('data'));
        $this->assertCount(10, $response->json('conversations'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
    }

    public function test_conversation_messages_returns_cursor_paginated_and_legacy_data()
    {
        $vendor = VendorAccount::create(['user_id' => \App\Models\User::factory()->create()->id, 'company_name' => 'Test Vendor', 'status' => 'active']);
        $customerId = 888;
        $conversation = $this->createConversation($customerId, $vendor->id);

        // Add 60 messages
        for ($i = 0; $i < 60; $i++) {
            $conversation->messages()->create([
                'sender_type' => 'vendor',
                'body_original' => "Message {$i}",
                'body_filtered' => "Message {$i}",
                'moderation_status' => 'clean',
                'created_at' => Carbon::now()->addSeconds($i), // Ensure order
            ]);
        }

        $response = $this->getJson("/api/conversations/{$conversation->id}/messages?prestashop_customer_id={$customerId}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'messages',    // legacy
                     'data',        // new paginated
                     'next_cursor',
                     'prev_cursor',
                 ]);

        // Assert cursor pagination works (default 50)
        $this->assertCount(50, $response->json('data'));
        $this->assertCount(50, $response->json('messages'));
        $this->assertNotNull($response->json('next_cursor'));
    }

    public function test_conversation_ownership_prevents_unauthorized_access()
    {
        $vendor = VendorAccount::create(['user_id' => \App\Models\User::factory()->create()->id, 'company_name' => 'Test Vendor', 'status' => 'active']);
        $ownerId = 123;
        $strangerId = 456;
        $conversation = $this->createConversation($ownerId, $vendor->id);

        // Accessing messages as stranger
        $response = $this->getJson("/api/conversations/{$conversation->id}/messages?prestashop_customer_id={$strangerId}");
        $response->assertStatus(403);

        // Posting message as stranger
        $response2 = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'prestashop_customer_id' => $strangerId,
            'message' => 'Hello',
        ]);
        $response2->assertStatus(403);
    }

    public function test_bookingbridge_chat_rate_limiting_applies()
    {
        $vendor = VendorAccount::create(['user_id' => \App\Models\User::factory()->create()->id, 'company_name' => 'Test Vendor', 'status' => 'active']);
        $customerId = 777;

        // Limiter is 30 per minute
        for ($i = 0; $i < 30; $i++) {
            $response = $this->getJson("/api/conversations?prestashop_customer_id={$customerId}");
            $response->assertStatus(200);
        }

        // 31st request should be blocked
        $response = $this->getJson("/api/conversations?prestashop_customer_id={$customerId}");
        $response->assertStatus(429);
    }
}
