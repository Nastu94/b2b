<?php

namespace App\Livewire\Vendor;

use App\Models\Offering;
use App\Models\VendorOfferingImage;
use App\Models\VendorOfferingProfile;
use App\Services\PrestashopProductSyncService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente per gestione contenuti (testi + immagini) per una singola offering.
 *
 * Aggiunto: max_guests — capacità massima ospiti per servizi FIXED_LOCATION.
 * Il campo è visibile e modificabile solo quando service_mode = FIXED_LOCATION.
 * Viene azzerato automaticamente quando si passa a MOBILE.
 */
class OfferingContentCard extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;

    /** @var int */
    public int $offeringId;

    /** @var \App\Models\Offering */
    public Offering $offering;

    /** @var \App\Models\VendorOfferingProfile */
    public VendorOfferingProfile $profile;

    public ?string $title = null;
    public ?string $short_description = null;
    public ?string $description = null;
    public string $service_mode = 'FIXED_LOCATION';
    public ?int $service_radius_km = null;

    // Capacità massima ospiti — solo per FIXED_LOCATION, null = nessun limite
    public ?int $max_guests = null;

    public $cover = null;
    public array $gallery = [];

    public function mount(int $offeringId): void
    {
        $this->offeringId = $offeringId;

        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        abort_unless($user->can('vendor.access'), 403);

        $vendorAccount = $user->vendorAccount;

        if (!$vendorAccount || $vendorAccount->trashed()) {
            abort(403);
        }

        $vendorId = $vendorAccount->id;

        $this->offering = Offering::findOrFail($offeringId);

        if ((int) $this->offering->category_id !== (int) $vendorAccount->category_id) {
            abort(403);
        }

        $this->profile = VendorOfferingProfile::firstOrCreate(
            ['vendor_account_id' => $vendorId, 'offering_id' => $offeringId],
            [
                'service_mode' => 'FIXED_LOCATION',
                'service_radius_km' => null,
                'max_guests' => null,
            ]
        );

        $this->authorize('update', $this->profile);

        $this->title = $this->profile->title;
        $this->short_description = $this->profile->short_description;
        $this->description = $this->profile->description;
        $this->service_mode = $this->profile->service_mode ?? 'FIXED_LOCATION';
        $this->service_radius_km = $this->profile->service_radius_km !== null
            ? (int) $this->profile->service_radius_km
            : null;
        $this->max_guests = $this->profile->max_guests !== null
            ? (int) $this->profile->max_guests
            : null;
    }

    // Quando si cambia modalità di servizio azzera i campi non applicabili
    public function updatedServiceMode($value): void
    {
        if ($value === 'FIXED_LOCATION') {
            $this->service_radius_km = null;
        }

        if ($value === 'MOBILE') {
            $this->service_radius_km = null;
            $this->max_guests = null;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'service_mode' => 'required|in:FIXED_LOCATION,MOBILE',
            'service_radius_km' => 'nullable|integer|min:1|max:500',
            'max_guests' => 'nullable|integer|min:1|max:9999',
            'cover' => 'nullable|image|max:4096',
            'gallery' => 'array|max:8',
            'gallery.*' => 'image|max:4096',
        ]);

        if ($this->service_mode === 'MOBILE' && $this->service_radius_km === null) {
            $this->addError(
                'service_radius_km',
                'Il raggio operativo è obbligatorio quando la modalità di servizio è mobile.'
            );
            return;
        }

        // FIXED_LOCATION: raggio non applicabile
        if ($this->service_mode === 'FIXED_LOCATION') {
            $this->service_radius_km = null;
        }

        // MOBILE: max_guests non applicabile
        if ($this->service_mode === 'MOBILE') {
            $this->max_guests = null;
        }

        $this->authorize('update', $this->profile);
        $this->authorize('create', VendorOfferingImage::class);

        $wasPublished = (bool) $this->profile->is_published;
        $wasApproved = (bool) $this->profile->is_approved;

        $this->profile->update([
            'title' => $this->title,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'service_mode' => $this->service_mode,
            'service_radius_km' => $this->service_radius_km,
            'max_guests' => $this->max_guests,
            'is_approved' => false,
        ]);

        // Cover
        if ($this->cover) {
            if ($this->profile->cover_image_path && Storage::disk('public')->exists($this->profile->cover_image_path)) {
                Storage::disk('public')->delete($this->profile->cover_image_path);
            }

            $path = $this->cover->store(
                "vendors/{$this->profile->vendor_account_id}/offerings/{$this->offeringId}/cover",
                'public'
            );

            $this->profile->update(['cover_image_path' => $path]);
            $this->cover = null;
        }

        // Gallery
        foreach ($this->gallery as $img) {
            $path = $img->store(
                "vendors/{$this->profile->vendor_account_id}/offerings/{$this->offeringId}/gallery",
                'public'
            );

            VendorOfferingImage::create([
                'vendor_offering_profile_id' => $this->profile->id,
                'path' => $path,
                'sort_order' => 0,
            ]);
        }
        $this->gallery = [];

        $fresh = $this->profile->fresh();

        $shouldPublish = $this->isProfilePublishable($fresh);

        $needsNotification = false;

        if ($shouldPublish) {
            if (!$wasPublished) {
                // Nuova pubblicazione
                $fresh->update(['is_published' => true]);
                $needsNotification = true;
            } elseif ($wasApproved) {
                // Era stato pubblicato E approvato, ora modificato e torna in moderazione
                $needsNotification = true;
            }
        } else {
            if ($wasPublished) {
                // Diventa incompleto, quindi retrocede da pubblicato
                $fresh->update(['is_published' => false]);
            }
        }

        if ($needsNotification) {
            // Notifica all'amministratore (Admin)
            try {
                \Illuminate\Support\Facades\Mail::to(config('mail.from.address'))
                    ->send(new \App\Mail\NewServiceSubmittedAdminMail($fresh));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Impossibile inviare notifica Admin per modifica Servizio: ' . $e->getMessage());
            }

            if ($this->profile->vendorAccount && $this->profile->vendorAccount->user) {
                try {
                    \Illuminate\Support\Facades\Mail::to($this->profile->vendorAccount->user->email)
                        ->send(new \App\Mail\VendorServiceUpdatedMail($fresh));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Impossibile inviare notifica Vendor per modifica Servizio: ' . $e->getMessage());
                }
            }
        }

        $this->profile = $this->profile->fresh();
        $vendorAccount = $this->profile->vendorAccount()->with('category')->first();

        if ($vendorAccount) {
            try {
                app(PrestashopProductSyncService::class)->sync($vendorAccount);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $msg = $needsNotification ? 'Il servizio è stato salvato ed è in fase di approvazione' : 'Salvato';
        $this->dispatch('notify', message: $msg);
    }

    public function removeCover(): void
    {
        $this->authorize('update', $this->profile);
        $this->profile->refresh();

        if ($this->profile->cover_image_path && Storage::disk('public')->exists($this->profile->cover_image_path)) {
            Storage::disk('public')->delete($this->profile->cover_image_path);
        }

        $this->profile->update([
            'cover_image_path' => null,
            'is_published' => false,
            'is_approved' => false,
        ]);

        $this->profile->refresh()->load('images');

        $vendorAccount = $this->profile->vendorAccount()->with('category')->first();

        if ($vendorAccount) {
            try {
                app(PrestashopProductSyncService::class)->sync($vendorAccount);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->dispatch('notify', message: 'Cover rimossa');
    }

    public function deleteImage(int $imageId): void
    {
        $img = VendorOfferingImage::query()
            ->where('vendor_offering_profile_id', $this->profile->id)
            ->whereKey($imageId)
            ->firstOrFail();

        $this->authorize('delete', $img);

        if ($img->path && Storage::disk('public')->exists($img->path)) {
            Storage::disk('public')->delete($img->path);
        }

        $img->delete();

        $wasApproved = $this->profile->is_approved;
        $this->profile->update(['is_approved' => false]);

        $fresh = $this->profile->fresh();
        if ($wasApproved && $this->isProfilePublishable($fresh)) {
            try {
                \Illuminate\Support\Facades\Mail::to(config('mail.from.address'))
                    ->send(new \App\Mail\NewServiceSubmittedAdminMail($fresh));
            } catch (\Exception $e) { 
                \Illuminate\Support\Facades\Log::error('Impossibile inviare notifica Admin per eliminazione foto: ' . $e->getMessage());
            }

            if ($this->profile->vendorAccount && $this->profile->vendorAccount->user) {
                try {
                    \Illuminate\Support\Facades\Mail::to($this->profile->vendorAccount->user->email)
                        ->send(new \App\Mail\VendorServiceUpdatedMail($fresh));
                } catch (\Exception $e) {}
            }
            $this->profile->refresh()->load('images');
            $this->dispatch('notify', message: 'Foto eliminata. Servizio in revisione.');
            return;
        }

        $this->profile->refresh()->load('images');
        $this->dispatch('notify', message: 'Foto eliminata');
    }

    protected function isProfilePublishable(VendorOfferingProfile $profile): bool
    {
        $hasCover = is_string($profile->cover_image_path) && trim($profile->cover_image_path) !== '';
        $hasDescription = is_string($profile->description) && trim($profile->description) !== '';

        return $hasCover && $hasDescription;
    }

    public function render()
    {
        $this->profile->load('images');

        return view('livewire.vendor.offering-content-card');
    }
}