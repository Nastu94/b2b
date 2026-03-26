<?php

namespace App\Mail;

use App\Models\VendorOfferingProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorServiceApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VendorOfferingProfile $profile
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PartyLegacy: Il tuo Servizio è Online!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendors.service-approved',
        );
    }
}
