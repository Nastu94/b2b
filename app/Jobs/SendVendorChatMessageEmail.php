<?php

namespace App\Jobs;

use App\Mail\NewCustomerConversationMessageVendorMail;
use App\Models\ConversationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendVendorChatMessageEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $messageId)
    {
        //
    }

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = ConversationMessage::with('thread.vendorAccount.user')->find($this->messageId);

        if (!$message) {
            Log::warning('SendVendorChatMessageEmail terminated without sending: message not found.', [
                'message_id' => $this->messageId,
            ]);
            return;
        }

        if ($message->sender_type !== 'customer') {
            Log::warning('SendVendorChatMessageEmail terminated without sending: message is not from a customer.', [
                'message_id' => $this->messageId,
                'vendor_account_id' => $message->thread->vendor_account_id,
            ]);
            return;
        }

        $email = $message->thread->vendorAccount?->notificationEmail();

        if (!$email) {
            Log::warning('SendVendorChatMessageEmail terminated without sending: no notification email found for vendor.', [
                'message_id' => $this->messageId,
                'vendor_account_id' => $message->thread->vendor_account_id,
            ]);
            return;
        }

        Mail::to($email)->send(new NewCustomerConversationMessageVendorMail($message->thread));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendVendorChatMessageEmail failed definitively.', [
            'message_id' => $this->messageId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
