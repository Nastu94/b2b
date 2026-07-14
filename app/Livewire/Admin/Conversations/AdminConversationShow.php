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
        if ($conversation->admin_deleted_at !== null) {
            abort(404);
        }

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

        $sent = \Illuminate\Support\Facades\DB::transaction(function () {
            $lockedConversation = ConversationThread::query()
                ->whereKey($this->conversation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedConversation->status !== 'open') {
                return false;
            }

            $lockedConversation->messages()->create([
                'sender_type' => 'admin',
                'sender_id' => Auth::id(),
                'body_original' => $this->newMessage,
                'body_filtered' => $this->newMessage,
                'moderation_status' => 'clean',
                'moderation_flags' => null,
            ]);

            $lockedConversation->update([
                'vendor_unread_count' => $lockedConversation->vendor_unread_count + 1,
                'customer_unread_count' => $lockedConversation->customer_unread_count + 1,
                'last_message_at' => now(),
                'vendor_deleted_at' => null,
            ]);

            // Invia notifica al Vendor (solo se il VendorAccount esiste)
            if ($lockedConversation->vendorAccount) {
                $vendorEmail = $lockedConversation->vendorAccount->notificationEmail();
                if ($vendorEmail) {
                    \Illuminate\Support\Facades\Mail::to($vendorEmail)
                        ->queue((new \App\Mail\NewCustomerConversationMessageVendorMail($lockedConversation))->afterCommit());
                }
            }

            // Invia notifica al Cliente (solo se ha l'email)
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

    public function deleteConversation()
    {
        $this->conversation->deleteForAdmin();
        return redirect()->route('admin.conversations');
    }

    public function render()
    {
        return view('livewire.admin.conversations.admin-conversation-show', [
            'messages' => $this->conversation->messages()->orderBy('created_at', 'asc')->get()
        ]);
    }
}
