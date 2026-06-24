<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewConversationMessageVendorMail extends Mailable
{
    use Queueable, SerializesModels;

    public $thread;

    /**
     * Create a new message instance.
     */
    public function __construct(\App\Models\ConversationThread $thread)
    {
        $this->thread = $thread;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuovo messaggio su PartyLegacy',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Hai ricevuto un nuovo messaggio su PartyLegacy.</p><p>Accedi alla piattaforma per rispondere.</p>',
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
