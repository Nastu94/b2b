<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected $vendorUser;
    protected $vendorAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendor']);
        $this->vendorUser->assignRole('vendor');

        $this->vendorAccount = VendorAccount::create([
            'user_id' => $this->vendorUser->id,
            'company_name' => 'Test Vendor',
            'status' => 'PENDING',
            'account_type' => 'COMPANY',
            'booking_capacity_mode' => 'single_resource',
            'vat_number' => '12345678901',
        ]);
    }

    public function test_vendor_cannot_change_status_to_active()
    {
        $component = Livewire::actingAs($this->vendorUser)
            ->test(\App\Livewire\Vendor\Profile\VendorProfilePage::class)
            ->call('enableEditing')
            ->set('form.status', 'ACTIVE')
            ->set('form.company_name', 'Changed Company')
            ->call('save');

        $this->vendorAccount->refresh();
        
        $this->assertEquals('PENDING', $this->vendorAccount->status);
        $this->assertEquals('Changed Company', $this->vendorAccount->company_name);
    }
}
