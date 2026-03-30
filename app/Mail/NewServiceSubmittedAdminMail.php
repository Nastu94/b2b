<?php

namespace App\Mail;

use App\Models\VendorOfferingProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewServiceSubmittedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public VendorOfferingProfile $service;

    /**
     * Create a new message instance.
     */
    public function __construct(VendorOfferingProfile $service)
    {
        $this->service = $service->loadMissing('vendorAccount');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuovo Servizio Sottoposto - Party Legacy',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $vendorName = $this->service->vendorAccount ? 
            ($this->service->vendorAccount->first_name ? 
                $this->service->vendorAccount->first_name . ' ' . $this->service->vendorAccount->last_name : 
                $this->service->vendorAccount->company_name) 
            : 'Fornitore Sconosciuto';

        return new Content(
            view: 'emails.admin.new-service-submitted',
            with: [
                'serviceName' => $this->service->name,
                'vendorName'  => $vendorName,
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
