<?php

namespace Tests\Feature\Livewire;

use App\Models\Booking;
use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBookingConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected $vendorUser;
    protected $vendorAccount;
    protected $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendor']);
        $this->vendorUser->assignRole('vendor');

        $this->vendorAccount = VendorAccount::create([
            'user_id' => $this->vendorUser->id,
            'company_name' => 'Test Vendor',
            'status' => 'approved',
            'pec_email' => 'vendor@test.com'
        ]);

        $slot = \App\Models\VendorSlot::create([
            'vendor_account_id' => $this->vendorAccount->id,
            'slug' => 'test-slot',
            'label' => 'Test Slot',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'vendor_account_id' => $this->vendorAccount->id,
            'vendor_slot_id' => $slot->id,
            'prestashop_order_id' => '12345',
            'prestashop_order_line_id' => '1',
            'status' => Booking::STATUS_PENDING_VENDOR_CONFIRMATION,
            'customer_data' => [
                'firstname' => 'Mario',
                'lastname' => 'Rossi',
                'email' => 'customer@test.com',
                'phone' => '+39 333 1234567',
            ],
            'start_time' => now(),
            'end_time' => now()->addHours(2),
            'event_date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_vendor_can_confirm_booking_and_sends_one_email()
    {
        Mail::fake();

        Livewire::actingAs($this->vendorUser)
            ->test(\App\Livewire\Vendor\Bookings\VendorBookingShowPage::class, ['booking' => $this->booking])
            ->call('confirm');

        $this->booking->refresh();
        $this->assertEquals(Booking::STATUS_CONFIRMED, $this->booking->status);

        Mail::assertQueued(\App\Mail\PrenotazioneConfermata::class, 1);
        Mail::assertQueued(\App\Mail\PrenotazioneConfermataVendor::class, 1);
    }

    public function test_double_confirm_does_not_send_duplicate_emails()
    {
        Mail::fake();

        $component = Livewire::actingAs($this->vendorUser)
            ->test(\App\Livewire\Vendor\Bookings\VendorBookingShowPage::class, ['booking' => $this->booking]);

        // First call
        $component->call('confirm');
        
        $this->booking->refresh();
        $this->assertEquals(Booking::STATUS_CONFIRMED, $this->booking->status);

        Mail::assertQueued(\App\Mail\PrenotazioneConfermata::class, 1);

        // Second call
        $component->call('confirm');

        // Still 1 email
        Mail::assertQueued(\App\Mail\PrenotazioneConfermata::class, 1);
    }

    public function test_vendor_can_decline_booking_and_notifies_customer_and_admin()
    {
        Mail::fake();
        config(['mail.admin.address' => 'operations@example.com']);

        Livewire::actingAs($this->vendorUser)
            ->test(\App\Livewire\Vendor\Bookings\VendorBookingShowPage::class, ['booking' => $this->booking])
            ->set('declineReason', 'Data non più disponibile')
            ->set('vendorNotes', 'Contattato telefonicamente')
            ->call('decline');

        $this->booking->refresh();
        $this->assertEquals(Booking::STATUS_DECLINED, $this->booking->status);

        Mail::assertQueued(\App\Mail\PrenotazioneRifiutata::class, 1);
        Mail::assertQueued(\App\Mail\PrenotazioneRifiutataAdmin::class, function ($mail) {
            $rendered = $mail->render();

            return $mail->hasTo('operations@example.com')
                && str_contains($rendered, '12345')
                && str_contains($rendered, 'customer@test.com')
                && str_contains($rendered, '+39 333 1234567')
                && str_contains($rendered, 'Data non più disponibile')
                && str_contains($rendered, 'Contattato telefonicamente')
                && str_contains($rendered, 'nessun rimborso è stato eseguito automaticamente');
        });
    }

    public function test_double_decline_does_not_send_duplicate_emails()
    {
        Mail::fake();
        config(['mail.admin.address' => 'operations@example.com']);

        $component = Livewire::actingAs($this->vendorUser)
            ->test(\App\Livewire\Vendor\Bookings\VendorBookingShowPage::class, ['booking' => $this->booking]);

        // First call
        $component->call('decline');
        
        $this->booking->refresh();
        $this->assertEquals(Booking::STATUS_DECLINED, $this->booking->status);

        Mail::assertQueued(\App\Mail\PrenotazioneRifiutata::class, 1);
        Mail::assertQueued(\App\Mail\PrenotazioneRifiutataAdmin::class, 1);

        // Second call
        $component->call('decline');

        // Still 1 email
        Mail::assertQueued(\App\Mail\PrenotazioneRifiutata::class, 1);
        Mail::assertQueued(\App\Mail\PrenotazioneRifiutataAdmin::class, 1);
    }
}
