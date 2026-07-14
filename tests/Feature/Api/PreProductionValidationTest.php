<?php

namespace Tests\Feature\Api;

use App\Models\ConversationThread;
use App\Models\VendorAccount;
use App\Models\User;
use App\Models\Category;
use App\Models\SlotLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class PreProductionValidationTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    public function test_vendor_registration_success()
    {
        Role::create(['name' => 'vendor']);

        $response = $this->postJson('/register', [
            'account_type' => 'COMPANY',
            'company_name' => 'Test Vendor',
            'vat_number' => '12345678901',
            'legal_city' => 'Rome',
            'legal_postal_code' => '00100',
            'legal_address_line1' => 'Via Roma 1',
            'name' => 'Mario Rossi',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'vendor_test_reg@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'privacy_accepted' => true,
            'contract_accepted' => true,
            'booking_capacity_mode' => 'single_resource',
            'category_id' => Category::create(['name' => 'Test', 'slug' => 'test-reg', 'is_active' => true, 'commission_rate' => 15])->id,
            'event_type_ids' => [\App\Models\EventType::create(['name' => 'Test Event', 'slug' => 'test-event'])->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vendor_accounts', ['company_name' => 'Test Vendor']);
    }

    public function test_chat_thread_deleted_by_customer_allows_new_thread()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test', 'slug' => 'test-chat', 'is_active' => true, 'commission_rate' => 15]);
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
        ]);
        
        $thread1 = ConversationThread::create([
            'vendor_account_id' => $vendor->id,
            'prestashop_customer_id' => 123,
            'status' => 'open',
            'customer_deleted_at' => now(), // deleted by customer
        ]);

        $response = $this->postJson('/api/conversations/start', [
            'vendor_account_id' => $vendor->id,
            'prestashop_customer_id' => 123,
            'message' => 'New message after deletion',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('conversation_threads', 2);
    }

    public function test_lock_expired_retry_returns_lock_terminated()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test', 'slug' => 'test-lock', 'is_active' => true, 'commission_rate' => 15]);
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
        ]);
        $slot = \App\Models\VendorSlot::create([
            'vendor_account_id' => $vendor->id,
            'slug' => 'test-slot',
            'label' => 'Test Slot',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);
        $offering = \App\Models\Offering::create([
            'category_id' => $category->id,
            'vendor_account_id' => $vendor->id,
            'name' => 'Test Offering',
            'slug' => 'test-offering',
            'is_active' => true,
        ]);
        \Illuminate\Support\Facades\DB::table('vendor_offerings')->insert([
            'vendor_account_id' => $vendor->id,
            'offering_id' => $offering->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Models\VendorOfferingProfile::create([
            'vendor_account_id' => $vendor->id,
            'offering_id' => $offering->id,
            'is_published' => true,
            'is_approved' => true,
        ]);

        $lock = SlotLock::create([
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'offering_id' => $offering->id,
            'date' => '2026-08-01',
            'distance_km' => 10,
            'guests' => 50,
            'status' => 'EXPIRED',
            'idempotency_key' => str_repeat('a', 32),
            'hold_token' => \Illuminate\Support\Str::uuid()->toString(),
            'is_active' => false,
            'active_slot_key' => 'old_key',
        ]);

        $response = $this->postJson('/api/slots/hold', [
            'vendor_account_id' => $lock->vendor_account_id,
            'vendor_slot_id' => $lock->vendor_slot_id,
            'date' => '2026-08-01',
            'offering_id' => $lock->offering_id,
            'distance_km' => $lock->distance_km,
            'guests' => $lock->guests,
        ], [
            'Idempotency-Key' => $lock->idempotency_key
        ]);

        $response->assertStatus(409)
                 ->assertJsonPath('code', 'LOCK_TERMINATED');
    }

    public function test_commission_migration_fails_on_unmapped_category()
    {
        $category = Category::create([
            'name' => 'Unmapped Cat',
            'slug' => 'unmapped-cat',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'payment_model' => 'COMMISSION',
            'custom_commission_rate' => null,
            'category_id' => $category->id
        ]);

        $migration = require base_path('database/migrations/2026_07_13_152816_apply_commission_system_to_categories.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Esistono categorie non mappate');
        
        $migration->up();
    }
}
