<?php

namespace Tests\Feature;

use App\Models\ConversationThread;
use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Livewire\Livewire;

class ClosedConversationTest extends TestCase
{
    use RefreshDatabase;

    protected $vendorUser;
    protected $vendorAccount;
    protected $adminUser;
    protected $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::factory()->create();
        // Assuming role creation is needed
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendor']);
        $this->vendorUser->assignRole('vendor');

        $this->vendorAccount = VendorAccount::create([
            'user_id' => $this->vendorUser->id,
            'company_name' => 'Test Company',
            'status' => 'approved',
        ]);

        $this->adminUser = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $this->adminUser->assignRole('admin');

        $this->conversation = ConversationThread::create([
            'vendor_account_id' => $this->vendorAccount->id,
            'prestashop_customer_id' => 123,
            'status' => 'closed',
        ]);
    }

    public function test_customer_cannot_send_message_to_closed_conversation()
    {
        $response = $this->postJson("/api/conversations/{$this->conversation->id}/messages", [
            'prestashop_customer_id' => 123,
            'message' => 'Test message',
        ]);

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'message' => 'Conversation is not open']);
        
        $this->assertDatabaseCount('conversation_messages', 0);
    }

    public function test_vendor_cannot_send_message_to_closed_conversation()
    {
        Livewire::actingAs($this->vendorUser)
            ->test(\App\Livewire\Vendor\Conversations\VendorConversationShow::class, ['conversation' => $this->conversation])
            ->set('newMessage', 'Test vendor message')
            ->call('sendMessage')
            ->assertHasErrors(['newMessage' => 'Questa conversazione è stata chiusa.']);

        $this->assertDatabaseCount('conversation_messages', 0);
    }

    public function test_admin_cannot_send_message_to_closed_conversation()
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Admin\Conversations\AdminConversationShow::class, ['conversation' => $this->conversation])
            ->set('newMessage', 'Test admin message')
            ->call('sendMessage')
            ->assertHasErrors(['newMessage' => 'Questa conversazione è stata chiusa.']);

        $this->assertDatabaseCount('conversation_messages', 0);
    }

    public function test_customer_receives_404_if_conversation_is_deleted_by_customer()
    {
        $this->conversation->update(['status' => 'open', 'customer_deleted_at' => now()]);

        $response = $this->postJson("/api/conversations/{$this->conversation->id}/messages", [
            'prestashop_customer_id' => 123,
            'message' => 'Test message',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false, 'message' => 'Conversation not found']);
    }
}
