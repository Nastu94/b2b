<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email operativa inviata all'admin quando un vendor rifiuta una prenotazione.
 * Segnala la necessità di verificare il pagamento in PrestaShop e procedere
 * manualmente con il rimborso soltanto quando dovuto.
 */
class PrenotazioneRifiutataAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {
        $this->booking->loadMissing(['vendorAccount.user', 'offering', 'vendorSlot']);
    }

    public function envelope(): Envelope
    {
        $orderId = $this->booking->prestashop_order_id ?: 'non disponibile';

        return new Envelope(
            subject: 'Azione richiesta: prenotazione rifiutata - ordine PrestaShop ' . $orderId,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.prenotazione-rifiutata-admin',
        );
    }
}
