<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email inviata al cliente quando il vendor rifiuta la prenotazione.
 * Include il motivo del rifiuto se fornito dal vendor.
 */
class PrenotazioneRifiutata extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prenotazione non disponibile — ' . $this->booking->event_date->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.prenotazione-rifiutata',
        );
    }
}