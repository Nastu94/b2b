<?php

namespace App\Livewire\Admin\Conversations;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\ConversationThread;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
class AdminConversationShow extends Component
{
    public ConversationThread $conversation;
    public string $newMessage = '';

    public function mount(ConversationThread $conversation)
    {
        $this->conversation = $conversation;
        
        if ($this->conversation->admin_unread_count > 0) {
            $this->conversation->update(['admin_unread_count' => 0]);
        }
    }

    public function closeConversation()
    {
        $this->conversation->update(['status' => 'closed']);
        $this->conversation->refresh();
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|max:2000',
        ]);

        $this->conversation->messages()->create([
            'sender_type' => 'admin',
            'sender_id' => Auth::id(),
            'body_original' => $this->newMessage,
            'body_filtered' => $this->newMessage,
            'moderation_status' => 'clean',
        ]);

        $this->conversation->update([
            'vendor_unread_count' => $this->conversation->vendor_unread_count + 1,
            'customer_unread_count' => $this->conversation->customer_unread_count + 1,
            'last_message_at' => now(),
        ]);

        if ($this->conversation->guest_email) {
            \Illuminate\Support\Facades\Mail::to($this->conversation->guest_email)
                ->queue(new \App\Mail\NewConversationMessageCustomerMail($this->conversation));
        }

        if ($this->conversation->vendorAccount && $this->conversation->vendorAccount->user) {
            \Illuminate\Support\Facades\Mail::to($this->conversation->vendorAccount->user->email)
                ->queue(new \App\Mail\NewConversationMessageVendorMail($this->conversation));
        }

        $this->newMessage = '';
        $this->conversation->refresh();
    }

    public function render()
    {
        return view('livewire.admin.conversations.admin-conversation-show', [
            'messages' => $this->conversation->messages()->orderBy('created_at', 'asc')->get()
        ]);
    }
}
