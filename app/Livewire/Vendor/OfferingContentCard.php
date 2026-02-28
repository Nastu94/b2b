<?php

namespace App\Livewire\Vendor;

use App\Models\Offering;
use App\Models\VendorOfferingImage;
use App\Models\VendorOfferingProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente per gestione contenuti (testi + immagini) per una singola offering.
 *
 * Nota: tipizzare le proprietà aiuta Livewire a idratare correttamente lo stato,
 * specialmente quando il componente è annidato in tab o altri componenti.
 */
class OfferingContentCard extends Component
{
    use WithFileUploads;
    use AuthorizesRequests;

    /**
     * ID dell'offering gestita dal componente.
     *
     * @var int
     */
    public int $offeringId;

    /**
     * Modello Offering (caricato in mount).
     *
     * @var \App\Models\Offering
     */
    public Offering $offering;

    /**
     * Profilo vendor-offering (caricato/creato in mount).
     *
     * @var \App\Models\VendorOfferingProfile
     */
    public VendorOfferingProfile $profile;

    /**
     * Campi testo (editabili).
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * @var string|null
     */
    public ?string $short_description = null;

    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Upload cover (singolo file).
     *
     * @var mixed
     */
    public $cover = null;

    /**
     * Upload gallery (multiplo).
     *
     * @var array<int, mixed>
     */
    public array $gallery = [];

    /**
     * Mount: carica dati e verifica autorizzazioni
     */
    public function mount(int $offeringId): void
    {
        $this->offeringId = $offeringId;

        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        // Gating: il vendor deve avere accesso al pannello vendor
        abort_unless($user->can('vendor.access'), 403);

        $vendorAccount = $user->vendorAccount;

        // Difesa in profondità: evita null e vendor soft-deleted
        if (!$vendorAccount || $vendorAccount->trashed()) {
            abort(403);
        }

        $vendorId = $vendorAccount->id;

        $this->offering = Offering::findOrFail($offeringId);

        /**
         * Regola di dominio:
         * il vendor può gestire contenuti solo per offerings della propria categoria.
         */
        if ((int) $this->offering->category_id !== (int) $vendorAccount->category_id) {
            abort(403);
        }

        // Crea o recupera il profilo "owned" dal vendor
        $this->profile = VendorOfferingProfile::firstOrCreate(
            ['vendor_account_id' => $vendorId, 'offering_id' => $offeringId],
            []
        );

        // Policy: il vendor può operare solo sul proprio profilo
        $this->authorize('update', $this->profile);

        $this->title = $this->profile->title;
        $this->short_description = $this->profile->short_description;
        $this->description = $this->profile->description;
    }

    /**
     * Salva dati e immagini, con validazione e autorizzazioni
     */
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

        // Policy: update profilo (ownership)
        $this->authorize('update', $this->profile);

        // Policy: create immagini (gating generale; ownership specifica è legata al profilo)
        $this->authorize('create', VendorOfferingImage::class);

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
            // Policy: è comunque un update sul profilo
            $this->authorize('update', $fresh);

            $fresh->update(['is_published' => true]);
        }

        $this->dispatch('notify', message: 'Salvato');
    }

    /**
     * Rimuove cover, con autorizzazione e pulizia filesystem
     */
    public function removeCover(): void
    {
        // Policy: update profilo (ownership)
        $this->authorize('update', $this->profile);

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

    /**
     * Elimina immagine gallery, con autorizzazione e pulizia filesystem
     */
    public function deleteImage(int $imageId): void
    {
        $img = VendorOfferingImage::query()
            ->where('vendor_offering_profile_id', $this->profile->id)
            ->whereKey($imageId)
            ->firstOrFail();

        // Policy: delete immagine (ownership via profile->vendor_account_id)
        $this->authorize('delete', $img);

        if ($img->path && Storage::disk('public')->exists($img->path)) {
            Storage::disk('public')->delete($img->path);
        }

        $img->delete();

        // refresh per aggiornare la UI
        $this->profile->refresh()->load('images');

        $this->dispatch('notify', message: 'Foto eliminata');
    }

    /**
     * Render: carica immagini solo per il profilo già "owned" e autorizzato
     */
    public function render()
    {
        // Scoping implicito: carichiamo immagini solo per il profilo già "owned" e autorizzato
        $this->profile->load('images');

        return view('livewire.vendor.offering-content-card');
    }
}