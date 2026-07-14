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

        if ($conversation->vendor_deleted_at !== null) {
            abort(404);
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

        $sent = \Illuminate\Support\Facades\DB::transaction(function () use ($moderated) {
            $lockedConversation = ConversationThread::query()
                ->whereKey($this->conversation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedConversation->status !== 'open') {
                return false;
            }

            $lockedConversation->messages()->create([
                'sender_type' => 'vendor',
                'sender_id' => Auth::id(),
                'body_original' => $moderated['original'],
                'body_filtered' => $moderated['filtered'],
                'moderation_status' => $moderated['status'],
                'moderation_flags' => $moderated['flags'],
            ]);

            $lockedConversation->update([
                'customer_unread_count' => $lockedConversation->customer_unread_count + 1,
                'admin_unread_count' => $lockedConversation->admin_unread_count + 1,
                'last_message_at' => now(),
                'admin_deleted_at' => null,
            ]);

            if ($lockedConversation->customer_email) {
                \Illuminate\Support\Facades\Mail::to($lockedConversation->customer_email)
                    ->queue((new \App\Mail\NewConversationMessageCustomerMail($lockedConversation))->afterCommit());
            }

            return true;
        });

        if (!$sent) {
            $this->addError('newMessage', 'Questa conversazione è stata chiusa.');
            $this->conversation->refresh();
            return;
        }

        $this->newMessage = '';
        $this->conversation->refresh();
    }

    public function closeConversation()
    {
        $this->conversation->update(['status' => 'closed']);
        $this->conversation->refresh();
    }

    public function deleteConversation()
    {
        $this->conversation->deleteForVendor();
        return redirect()->route('vendor.conversations');
    }

    public function render()
    {
        return view('livewire.vendor.conversations.vendor-conversation-show', [
            'messages' => $this->conversation->messages()->orderBy('created_at', 'asc')->get()
        ]);
    }
}
