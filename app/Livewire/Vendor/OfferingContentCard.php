<?php

namespace App\Livewire\Vendor;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Offering;
use App\Models\VendorOfferingProfile;
use App\Models\VendorOfferingImage;

class OfferingContentCard extends Component
{
    use WithFileUploads;

    public int $offeringId;

    public $offering;
    public $profile;

    public $title;
    public $short_description;
    public $description;

    public $cover;          // upload singolo
    public $gallery = [];   // upload multiplo

    public function mount(int $offeringId): void
    {
        $this->offeringId = $offeringId;

        $vendorId = Auth::user()->vendorAccount->id;

        $this->offering = Offering::findOrFail($offeringId);

        $this->profile = VendorOfferingProfile::firstOrCreate(
            ['vendor_account_id' => $vendorId, 'offering_id' => $offeringId],
            []
        );

        $this->title = $this->profile->title;
        $this->short_description = $this->profile->short_description;
        $this->description = $this->profile->description;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cover' => 'nullable|image|max:4096',
            'gallery' => 'array|max:8',            // max 8 foto
            'gallery.*' => 'image|max:4096',
        ]);

        $this->profile->update([
            'title' => $this->title,
            'short_description' => $this->short_description,
            'description' => $this->description,
        ]);

        // Cover
        if ($this->cover) {
            // elimina cover precedente se esiste (sia DB che file)
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

        // Publish auto se completo
        $fresh = $this->profile->fresh();
        if ($fresh->cover_image_path && $fresh->description) {
            $fresh->update(['is_published' => true]);
        }

        $this->dispatch('notify', message: 'Salvato');
    }

    public function removeCover(): void
    {
        // refresh per coerenza
        $this->profile->refresh();

        if ($this->profile->cover_image_path && Storage::disk('public')->exists($this->profile->cover_image_path)) {
            Storage::disk('public')->delete($this->profile->cover_image_path);
        }

        $this->profile->update([
            'cover_image_path' => null,
            // se manca la cover, non è pubblicabile
            'is_published' => false,
        ]);

        $this->profile->refresh()->load('images');

        $this->dispatch('notify', message: 'Cover rimossa');
    }

    public function deleteImage(int $imageId): void
    {
        $img = VendorOfferingImage::query()
            ->where('vendor_offering_profile_id', $this->profile->id)
            ->whereKey($imageId)
            ->firstOrFail();

        if ($img->path && Storage::disk('public')->exists($img->path)) {
            Storage::disk('public')->delete($img->path);
        }

        $img->delete();

        // refresh per aggiornare la UI
        $this->profile->refresh()->load('images');

        $this->dispatch('notify', message: 'Foto eliminata');
    }

    public function render()
    {
        $this->profile->load('images');

        return view('livewire.vendor.offering-content-card');
    }
}