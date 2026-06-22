<?php

namespace App\Livewire\Vendor\Conversations;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\ConversationThread;
use App\Services\ConversationModerationService;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.vendor')]
class VendorConversationShow extends Component
{
    public ConversationThread $conversation;
    public string $newMessage = '';

    public function mount(ConversationThread $conversation)
    {
        $vendorAccount = Auth::user()->vendorAccount;

        if ($conversation->vendor_account_id !== $vendorAccount->id) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $this->conversation = $conversation;
        
        if ($this->conversation->vendor_unread_count > 0) {
            $this->conversation->update(['vendor_unread_count' => 0]);
        }
    }

    public function sendMessage(ConversationModerationService $moderationService)
    {
        $this->validate([
            'newMessage' => 'required|string|max:2000',
        ]);

        $moderated = $moderationService->moderate($this->newMessage);

        $this->conversation->messages()->create([
            'sender_type' => 'vendor',
            'sender_id' => Auth::id(),
            'body_original' => $moderated['original'],
            'body_filtered' => $moderated['filtered'],
            'moderation_status' => $moderated['status'],
            'moderation_flags' => $moderated['flags'],
        ]);

        $this->conversation->update([
            'customer_unread_count' => $this->conversation->customer_unread_count + 1,
            'admin_unread_count' => $this->conversation->admin_unread_count + 1,
            'last_message_at' => now(),
        ]);

        if ($this->conversation->guest_email) {
            \Illuminate\Support\Facades\Mail::to($this->conversation->guest_email)
                ->queue(new \App\Mail\NewConversationMessageCustomerMail($this->conversation));
        }

        $this->newMessage = '';
        $this->conversation->refresh();
    }

    public function render()
    {
        return view('livewire.vendor.conversations.vendor-conversation-show', [
            'messages' => $this->conversation->messages()->orderBy('created_at', 'asc')->get()
        ]);
    }
}
