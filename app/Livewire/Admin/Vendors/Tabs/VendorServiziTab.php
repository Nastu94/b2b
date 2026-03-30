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

    // Proprietà per la gestione della Modale Dettagli
    public bool $isViewingModalOpen = false;
    public ?VendorOfferingProfile $viewingProfile = null;

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
            $isApproved  = (bool) ($p?->is_approved);

            $statusLabel = $isPublished
                ? ($isApproved ? 'PUBBLICATO' : 'IN ATTESA DI APPROVAZIONE')
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
                'service_mode' => (string) ($p?->service_mode ?? 'FIXED_LOCATION'),
                'service_radius_km' => $p?->service_radius_km,

                // stato
                'is_published' => $isPublished,
                'is_approved' => $isApproved,
                'status_label' => $statusLabel,
            ];
        })->all();
    }

    public function approveOfferingProfile(int $offeringId): void
    {
        $this->authorize('update', $this->vendorAccount);

        $profile = VendorOfferingProfile::where('vendor_account_id', $this->vendorAccount->id)
            ->where('offering_id', $offeringId)
            ->first();

        if ($profile) {
            $profile->update([
                'is_approved' => true,
                'is_published' => true,
            ]);

            if ($this->vendorAccount->user && $this->vendorAccount->user->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($this->vendorAccount->user->email)
                        ->send(new \App\Mail\VendorServiceApprovedMail($profile));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Impossibile inviare email Servizio: ' . $e->getMessage());
                }
            }

            session()->flash('status', 'Servizio approvato con successo. Email inviata e sincronizzazione avviata.');
            $this->loadCardsData();
            
            // Se eravamo in modale, aggiorniamo il record caricato
            if ($this->viewingProfile && $this->viewingProfile->offering_id === $offeringId) {
                $this->viewingProfile->refresh();
            }
        }
    }

    public function openOfferingDetails(int $offeringId): void
    {
        $this->authorize('view', $this->vendorAccount);

        $this->viewingProfile = VendorOfferingProfile::with(['images'])
            ->where('vendor_account_id', $this->vendorAccount->id)
            ->where('offering_id', $offeringId)
            ->first();

        $this->isViewingModalOpen = true;
    }

    public function closeOfferingDetails(): void
    {
        $this->isViewingModalOpen = false;
        $this->viewingProfile = null;
    }

    public function render()
    {
        $this->authorize('view', $this->vendorAccount);

        return view('livewire.admin.vendors.tabs.vendor-servizi-tab');
    }
}