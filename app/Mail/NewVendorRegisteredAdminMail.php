<?php

namespace App\Mail;

use App\Models\VendorAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewVendorRegisteredAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public VendorAccount $vendor;

    /**
     * Create a new message instance.
     */
    public function __construct(VendorAccount $vendor)
    {
        $this->vendor = $vendor->loadMissing('user');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuova Registrazione Fornitore - Party Legacy',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new-vendor-registered',
            with: [
                'vendorName' => $this->vendor->first_name ? $this->vendor->first_name . ' ' . $this->vendor->last_name : $this->vendor->company_name,
                'vendorEmail' => $this->vendor->user->email,
                'vendorType' => $this->vendor->account_type,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
