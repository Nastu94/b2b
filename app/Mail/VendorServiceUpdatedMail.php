<?php

namespace App\Mail;

use App\Models\VendorOfferingProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorServiceUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public VendorOfferingProfile $profile;

    /**
     * Create a new message instance.
     */
    public function __construct(VendorOfferingProfile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Servizio in Fase di Approvazione - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendors.service-updated',
        );
    }
}
