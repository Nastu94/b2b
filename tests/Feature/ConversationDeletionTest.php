<?php

namespace Tests\Feature;

use App\Models\ConversationThread;
use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ConversationDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_delete_conversation()
    {
        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create();
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);
        
        $thread = ConversationThread::create([
            'vendor_account_id' => $vendor->id,
        ]);

        $this->actingAs($user);

        $thread->deleteForVendor();

        $this->assertNotNull($thread->fresh()->vendor_deleted_at);
        $this->assertCount(0, ConversationThread::visibleToVendor()->get());
    }

    public function test_admin_can_delete_conversation()
    {
        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test']);
        $vendorUser = User::factory()->create();
        $vendor = VendorAccount::create([
            'user_id' => $vendorUser->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);
        
        $thread = ConversationThread::create([
            'vendor_account_id' => $vendor->id,
        ]);

        $admin = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $thread->deleteForAdmin();

        $this->assertNotNull($thread->fresh()->admin_deleted_at);
        $this->assertCount(0, ConversationThread::visibleToAdmin()->get());
    }

    public function test_conversation_is_restored_on_new_message()
    {
        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test']);
        $vendorUser = User::factory()->create();
        $vendor = VendorAccount::create([
            'user_id' => $vendorUser->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);

        $thread = ConversationThread::create([
            'vendor_account_id' => $vendor->id,
            'prestashop_customer_id' => 123,
            'vendor_deleted_at' => now(),
            'admin_deleted_at' => now(),
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\BookingBridgeAuth::class);

        $response = $this->postJson('/api/conversations/'.$thread->id.'/messages', [
            'message' => 'New message',
            'prestashop_customer_id' => 123,
        ]);

        $response->assertStatus(200);
        
        $thread->refresh();
        $this->assertNull($thread->vendor_deleted_at);
        $this->assertNull($thread->admin_deleted_at);
    }
}
