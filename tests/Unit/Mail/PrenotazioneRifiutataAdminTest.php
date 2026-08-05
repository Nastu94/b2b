<?php

namespace Tests\Unit\Mail;

use App\Mail\PrenotazioneRifiutataAdmin;
use App\Models\Booking;
use App\Models\Offering;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorSlot;
use Tests\TestCase;

class PrenotazioneRifiutataAdminTest extends TestCase
{
    public function test_email_contains_the_references_needed_for_manual_refund_review(): void
    {
        $vendor = new VendorAccount();
        $vendor->id = 27;
        $vendor->company_name = 'Party Vendor';
        $vendor->setRelation('user', new User());

        $offering = new Offering();
        $offering->id = 11;
        $offering->name = 'DJ Set';

        $slot = new VendorSlot();
        $slot->id = 8;
        $slot->label = '20:00 - 22:00';

        $booking = new Booking([
            'prestashop_order_id' => 'PS-12345',
            'prestashop_order_line_id' => '7',
            'event_date' => '2026-09-12',
            'total_amount' => 249.90,
            'currency' => 'EUR',
            'paid_at' => '2026-08-05 10:30:00',
            'customer_data' => [
                'firstname' => 'Mario',
                'lastname' => 'Rossi',
                'email' => 'cliente@example.com',
                'phone' => '+39 333 1234567',
            ],
            'decline_reason' => 'Data non più disponibile',
            'vendor_notes' => 'Cliente già contattato',
        ]);
        $booking->id = 42;
        $booking->setRelation('vendorAccount', $vendor);
        $booking->setRelation('offering', $offering);
        $booking->setRelation('vendorSlot', $slot);

        $mail = new PrenotazioneRifiutataAdmin($booking);
        $rendered = $mail->render();

        $this->assertSame(
            'Azione richiesta: prenotazione rifiutata - ordine PrestaShop PS-12345',
            $mail->envelope()->subject
        );
        $this->assertStringContainsString('PS-12345', $rendered);
        $this->assertStringContainsString('Mario Rossi', $rendered);
        $this->assertStringContainsString('cliente@example.com', $rendered);
        $this->assertStringContainsString('+39 333 1234567', $rendered);
        $this->assertStringContainsString('Data non più disponibile', $rendered);
        $this->assertStringContainsString('Cliente già contattato', $rendered);
        $this->assertStringContainsString('nessun rimborso è stato eseguito automaticamente', $rendered);
        $this->assertStringContainsString(route('admin.bookings.show', $booking), $rendered);
    }
}
