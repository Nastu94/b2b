<?php

namespace App\Livewire\Admin\Approvals;

use App\Services\AdminApprovalCountService;
use Livewire\Attributes\On;
use Livewire\Component;

class ApprovalsSidebarBadge extends Component
{
    public int $count = 0;

    public function mount(AdminApprovalCountService $service): void
    {
        $this->count = $service->pendingCount();
    }

    #[On('approvals-updated')]
    public function refreshCount(AdminApprovalCountService $service): void
    {
        $this->count = $service->pendingCount();
    }

    public function render(AdminApprovalCountService $service)
    {
        $this->count = $service->pendingCount();

        return view('livewire.admin.approvals.approvals-sidebar-badge');
    }
}
