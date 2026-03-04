<?php

namespace App\Livewire\Admin\Vendors\Tabs;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class VendorServiziTab extends Component
{
    use AuthorizesRequests;

    public int $vendorAccountId;

    public VendorAccount $vendorAccount;

    /** @var array<int, array<string,mixed>> */
    public array $activeOfferings = [];

    public function mount(int $vendorAccountId): void
    {
        $this->vendorAccountId = $vendorAccountId;

        $this->vendorAccount = VendorAccount::query()
            ->with(['offerings'])
            ->findOrFail($vendorAccountId);

        $this->authorize('view', $this->vendorAccount);

        $this->loadCardsData();
    }

    private function loadCardsData(): void
    {
        $vendorId = $this->vendorAccount->id;

        // Stesso criterio della dashboard vendor: offerings attive (pivot is_active = true)
        $offerings = $this->vendorAccount->offerings()
            ->wherePivot('is_active', true)
            ->orderBy('offerings.name')
            ->get(['offerings.id', 'offerings.name']);

        if ($offerings->isEmpty()) {
            $this->activeOfferings = [];
            return;
        }

        $profiles = VendorOfferingProfile::query()
            ->where('vendor_account_id', $vendorId)
            ->whereIn('offering_id', $offerings->pluck('id'))
            ->withCount('images')
            ->get()
            ->keyBy('offering_id');

        $this->activeOfferings = $offerings->map(function ($o) use ($profiles) {
            $p = $profiles->get($o->id);

            $hasCover = !empty($p?->cover_image_path);
            $hasDesc  = !empty($p?->description);
            $isPublished = (bool) ($p?->is_published);

            $statusLabel = $isPublished
                ? 'PUBBLICATO'
                : (($hasCover || $hasDesc) ? 'IN BOZZA' : 'INCOMPLETO');

            return [
                'id' => (int) $o->id,
                'name' => (string) $o->name,

                // profilo contenuti
                'title' => (string) ($p?->title ?? ''),
                'short_description' => (string) ($p?->short_description ?? ''),
                'description' => (string) ($p?->description ?? ''),
                'cover_image_path' => (string) ($p?->cover_image_path ?? ''),
                'images_count' => (int) ($p?->images_count ?? 0),

                // stato
                'is_published' => $isPublished,
                'status_label' => $statusLabel,
            ];
        })->all();
    }

    public function render()
    {
        $this->authorize('view', $this->vendorAccount);

        return view('livewire.admin.vendors.tabs.vendor-servizi-tab');
    }
}