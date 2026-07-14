<?php

namespace Tests\Feature;

use App\Models\ConversationThread;
use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConversationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_chat_message_email_is_queued_after_commit()
    {
        Queue::fake();

        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create();
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\BookingBridgeAuth::class);

        $response = $this->postJson('/api/conversations/start', [
            'vendor_account_id' => $vendor->id,
            'customer_email' => 'customer@example.com',
            'customer_name' => 'John',
            'prestashop_customer_id' => 123,
            'message' => 'Hello',
        ]);

        $response->assertStatus(200);

        Queue::assertPushed(\App\Jobs\SendVendorChatMessageEmail::class);
    }
}
