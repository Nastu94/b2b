<?php

namespace App\Livewire\Vendor\Dashboard;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.vendor')]
class VendorDashboardPage extends Component
{
    public string $vendorName = '';
    public string $status = 'N/A';
    public string $categoryName = 'N/A';

    /** @var array<int, array{id:int, name:string}> */
    public array $activeOfferings = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        $vendorAccount = $user->vendorAccount;

        // Dati base del vendor
        $this->vendorName = $user->name;
        $this->status = $vendorAccount?->status ?? 'N/A';
        $this->categoryName = $vendorAccount?->category?->name ?? 'N/A';

        $this->activeOfferings = [];

        if ($vendorAccount) {
            $vendorId = $vendorAccount->id;

            $offerings = $vendorAccount->offerings()
                ->wherePivot('is_active', true)
                ->orderBy('offerings.name')
                ->get(['offerings.id', 'offerings.name']);

            // carico tutti i profili del vendor per quelle offerings in 1 query
            $profiles = \App\Models\VendorOfferingProfile::query()
                ->where('vendor_account_id', $vendorId)
                ->whereIn('offering_id', $offerings->pluck('id'))
                ->withCount('images')
                ->get()
                ->keyBy('offering_id');

            $this->activeOfferings = $offerings->map(function ($o) use ($profiles) {
                $p = $profiles->get($o->id);

                $hasCover = !empty($p?->cover_image_path);
                $hasDesc  = !empty($p?->description);
                $isPublished = (bool)($p?->is_published);

                $statusLabel = $isPublished
                    ? 'PUBBLICATO'
                    : (($hasCover || $hasDesc) ? 'IN BOZZA' : 'INCOMPLETO');

                return [
                    'id' => (int) $o->id,
                    'name' => (string) $o->name,

                    // profilo contenuti
                    'title' => (string)($p?->title ?? ''),
                    'short_description' => (string)($p?->short_description ?? ''),
                    'description' => (string)($p?->description ?? ''),
                    'cover_image_path' => (string)($p?->cover_image_path ?? ''),
                    'images_count' => (int)($p?->images_count ?? 0),

                    // stato
                    'is_published' => $isPublished,
                    'status_label' => $statusLabel,
                ];
            })->all();
        }
    }

    public function render()
    {
        // Passiamo "title" al layout vendor
        return view('livewire.vendor.dashboard.vendor-dashboard-page', [
            'title' => 'Dashboard',
        ]);
    }
}
