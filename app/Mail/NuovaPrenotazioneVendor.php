<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email inviata al vendor quando riceve una nuova prenotazione.
 * Contiene tutti i dettagli del booking e il link diretto al gestionale.
 */
class NuovaPrenotazioneVendor extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuova prenotazione ricevuta — ' . $this->booking->event_date->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.nuova-prenotazione-vendor',
        );
    }
}