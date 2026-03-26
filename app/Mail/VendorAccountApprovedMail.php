<?php

namespace App\Mail;

use App\Models\VendorAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorAccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VendorAccount $vendorAccount
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PartyLegacy: Il tuo Account Fornitore è stato Approvato!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.vendors.account-approved',
        );
    }
}
