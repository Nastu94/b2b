<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email inviata al cliente quando il vendor conferma la prenotazione.
 * Contiene i dati del vendor per permettere il contatto diretto.
 */
class PrenotazioneConfermata extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Il tuo evento è confermato — ' . $this->booking->event_date->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.prenotazione-confermata',
        );
    }
}