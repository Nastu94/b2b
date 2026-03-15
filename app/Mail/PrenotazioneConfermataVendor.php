<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email inviata al vendor come conferma della sua azione.
 * Contiene i dati del cliente per permettere il contatto diretto.
 */
class PrenotazioneConfermataVendor extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prenotazione confermata — ' . $this->booking->event_date->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.prenotazione-confermata-vendor',
        );
    }
}