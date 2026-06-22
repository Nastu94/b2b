<?php

namespace App\Livewire\Admin\Conversations;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\ConversationThread;

#[Layout('layouts.admin')]
class AdminConversationsList extends Component
{
    use WithPagination;

    public $filter = 'all'; // all, open, filtered, flagged, closed

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function render()
    {
        $query = ConversationThread::with(['vendorAccount', 'offering']);

        if ($this->filter === 'open') {
            $query->where('status', 'open');
        } elseif ($this->filter === 'closed') {
            $query->where('status', 'closed');
        } elseif ($this->filter === 'filtered' || $this->filter === 'flagged') {
            $query->whereHas('messages', function($q) {
                $q->where('moderation_status', $this->filter);
            });
        }

        $conversations = $query->orderBy('last_message_at', 'desc')->paginate(20);

        return view('livewire.admin.conversations.admin-conversations-list', [
            'conversations' => $conversations,
        ]);
    }
}
