<?php

namespace App\Livewire\Admin\Vendors\Tabs;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Models\Offering;
use Illuminate\Support\Facades\DB;
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

    public ?int $editingOfferingId = null;
    public string $editOfferingName = '';
    public string $editProfileTitle = '';
    public string $editProfileShortDescription = '';
    public string $editProfileDescription = '';

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

        // Stesso criterio della dashboard vendor, ma includiamo i custom in attesa o approvati
        $vendorId = $this->vendorAccount->id;

        $offerings = $this->vendorAccount->offerings()
            ->where(function ($query) use ($vendorId) {
                $query->where('vendor_offerings.is_active', true)
                    ->orWhere(function ($subQuery) use ($vendorId) {
                        $subQuery->where('offerings.is_custom', true)
                            ->where('offerings.created_by_vendor_account_id', $vendorId)
                            ->whereIn('offerings.status', [
                                Offering::STATUS_PENDING_REVIEW,
                                Offering::STATUS_APPROVED,
                            ]);
                    });
            })
            ->orderBy('offerings.name')
            ->get([
                'offerings.id',
                'offerings.name',
                'offerings.is_custom',
                'offerings.status',
                'offerings.created_by_vendor_account_id',
            ]);

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
                'is_custom' => (bool) $o->is_custom,
                'status' => (string) $o->status,
                'created_by_vendor_account_id' => $o->created_by_vendor_account_id ? (int) $o->created_by_vendor_account_id : null,

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

        try {
            app(\App\Services\OfferingApprovalService::class)->approveOfferingProfile($this->vendorAccount, $offeringId);

            session()->flash('status', 'Servizio approvato con successo. Email inviata e sincronizzazione avviata.');
            $this->loadCardsData();
            
            // Se eravamo in modale, aggiorniamo il record caricato
            if ($this->viewingProfile && $this->viewingProfile->offering_id === $offeringId) {
                $this->viewingProfile->refresh();
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('general', collect($e->errors())->flatten()->first());
        }
    }

    public function approveViewingOffering(): void
    {
        if (!$this->viewingProfile) {
            return;
        }

        $this->approveOfferingProfile((int) $this->viewingProfile->offering_id);

        if (!$this->getErrorBag()->isNotEmpty()) {
            $this->closeOfferingDetails();
        }
    }

    public function rejectOfferingProfile(int $offeringId): void
    {
        $this->authorize('update', $this->vendorAccount);

        app(\App\Services\OfferingApprovalService::class)->rejectOfferingProfile($this->vendorAccount, $offeringId);

        session()->flash('status', 'Servizio rifiutato.');
        $this->loadCardsData();
        
        if ($this->viewingProfile && $this->viewingProfile->offering_id === $offeringId) {
            $this->viewingProfile->refresh();
        }
    }

    public function openOfferingDetails(int $offeringId): void
    {
        $this->authorize('view', $this->vendorAccount);

        $this->viewingProfile = VendorOfferingProfile::with(['images'])
            ->where('vendor_account_id', $this->vendorAccount->id)
            ->where('offering_id', $offeringId)
            ->first();

        $offering = Offering::findOrFail($offeringId);

        $this->editingOfferingId = $offeringId;
        $this->editOfferingName = $offering->name;
        $this->editProfileTitle = $this->viewingProfile?->title ?? '';
        $this->editProfileShortDescription = $this->viewingProfile?->short_description ?? '';
        $this->editProfileDescription = $this->viewingProfile?->description ?? '';

        $this->isViewingModalOpen = true;
    }

    public function closeOfferingDetails(): void
    {
        $this->isViewingModalOpen = false;
        $this->viewingProfile = null;
        $this->editingOfferingId = null;
        $this->editOfferingName = '';
        $this->editProfileTitle = '';
        $this->editProfileShortDescription = '';
        $this->editProfileDescription = '';
    }

    public function saveOfferingEdits(): void
    {
        $this->authorize('update', $this->vendorAccount);

        $this->validate([
            'editingOfferingId' => 'required|integer',
            'editOfferingName' => 'required|string|max:255',
            'editProfileTitle' => 'required|string|max:255',
            'editProfileShortDescription' => 'nullable|string|max:255',
            'editProfileDescription' => 'nullable|string',
        ]);

        DB::transaction(function () {
            $profile = VendorOfferingProfile::query()
                ->where('vendor_account_id', $this->vendorAccount->id)
                ->where('offering_id', $this->editingOfferingId)
                ->firstOrFail();

            $offering = Offering::query()
                ->whereKey($this->editingOfferingId)
                ->firstOrFail();

            if ($offering->is_custom) {
                $offering->update([
                    'name' => $this->editOfferingName,
                ]);
            }

            $profile->update([
                'title' => $this->editProfileTitle,
                'short_description' => $this->editProfileShortDescription,
                'description' => $this->editProfileDescription,
            ]);

            $this->viewingProfile = $profile->fresh(['images']);
        });

        $this->loadCardsData();

        session()->flash('status', 'Servizio aggiornato con successo.');
    }

    public function deleteOffering(int $offeringId): void
    {
        $this->authorize('update', $this->vendorAccount);

        DB::transaction(function () use ($offeringId) {
            $offering = Offering::query()->findOrFail($offeringId);

            $profile = VendorOfferingProfile::query()
                ->where('vendor_account_id', $this->vendorAccount->id)
                ->where('offering_id', $offeringId)
                ->first();

            if ($offering->is_custom && $offering->status !== Offering::STATUS_APPROVED) {
                if ($profile) {
                    $profile->delete();
                }

                $this->vendorAccount->offerings()->detach($offeringId);

                $offering->delete();

                return;
            }

            $this->vendorAccount->offerings()
                ->updateExistingPivot($offeringId, ['is_active' => false]);

            if ($profile) {
                $profile->update([
                    'is_published' => false,
                    'is_approved' => false,
                ]);
            }

            if ($offering->is_custom) {
                $offering->update([
                    'is_active' => false,
                    'status' => Offering::STATUS_REJECTED,
                ]);
            }
        });

        $this->closeOfferingDetails();
        $this->loadCardsData();

        session()->flash('status', 'Servizio eliminato/disattivato.');
    }

    public function render()
    {
        $this->authorize('view', $this->vendorAccount);

        return view('livewire.admin.vendors.tabs.vendor-servizi-tab');
    }
}