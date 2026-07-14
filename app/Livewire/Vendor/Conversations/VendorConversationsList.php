<?php

namespace App\Livewire\Vendor\Conversations;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\ConversationThread;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.vendor')]
class VendorConversationsList extends Component
{
    use WithPagination;

    public function render()
    {
        $vendorAccount = Auth::user()->vendorAccount;

        $conversations = ConversationThread::where('vendor_account_id', $vendorAccount->id)
            ->visibleToVendor()
            ->with(['offering'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(15);

        return view('livewire.vendor.conversations.vendor-conversations-list', [
            'conversations' => $conversations,
        ]);
    }

    public function deleteConversation(int $conversationId): void
    {
        $vendorAccount = Auth::user()->vendorAccount;

        $conversation = ConversationThread::query()
            ->whereKey($conversationId)
            ->where('vendor_account_id', $vendorAccount->id)
            ->firstOrFail();

        $conversation->deleteForVendor();

        $total = ConversationThread::where('vendor_account_id', $vendorAccount->id)
            ->visibleToVendor()
            ->count();

        if ($this->getPage() > 1 && $total <= ($this->getPage() - 1) * 15) {
            $this->setPage($this->getPage() - 1);
        }
    }
}
