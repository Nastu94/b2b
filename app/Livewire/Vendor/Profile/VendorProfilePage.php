<?php

namespace App\Livewire\Vendor\Profile;

use App\Models\Category;
use App\Models\VendorAccount;
use App\Services\GeocodingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.vendor')]
class VendorProfilePage extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public $profile_image;

    public VendorAccount $vendorAccount;

    public bool $editing = false;

    public array $form = [];
    public array $originalForm = [];
    public array $categories = [];

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        $this->vendorAccount = VendorAccount::query()
            ->with(['user', 'category', 'offerings'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('view', $this->vendorAccount);

        $this->categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();

        $this->fillForm();
    }

    private function fillForm(): void
    {
        $vendorAccount = $this->vendorAccount;

        $this->form = [
            'status' => $vendorAccount->status,
            'account_type' => $vendorAccount->account_type,
            'category_id' => $vendorAccount->category_id,
            'event_type_ids' => $vendorAccount->eventTypes->pluck('id')->all(),

            // COMPANY
            'company_name' => $vendorAccount->company_name,
            'legal_entity_type' => $vendorAccount->legal_entity_type,
            'vat_number' => $vendorAccount->vat_number,

            // PRIVATE
            'first_name' => $vendorAccount->first_name,
            'last_name' => $vendorAccount->last_name,

            // COMMON
            'tax_code' => $vendorAccount->tax_code,

            // LEGAL SEAT
            'legal_country' => $vendorAccount->legal_country,
            'legal_region' => $vendorAccount->legal_region,
            'legal_city' => $vendorAccount->legal_city,
            'legal_postal_code' => $vendorAccount->legal_postal_code,
            'legal_address_line1' => $vendorAccount->legal_address_line1,

            // OPERATIONAL SEAT
            'operational_same_as_legal' => (bool) $vendorAccount->operational_same_as_legal,
            'operational_country' => $vendorAccount->operational_country,
            'operational_region' => $vendorAccount->operational_region,
            'operational_city' => $vendorAccount->operational_city,
            'operational_postal_code' => $vendorAccount->operational_postal_code,
            'operational_address_line1' => $vendorAccount->operational_address_line1,
        ];

        $this->originalForm = $this->form;
    }

    public function enableEditing(): void
    {
        $this->authorize('update', $this->vendorAccount);

        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->form = $this->originalForm;
        $this->profile_image = null;
        $this->resetValidation();
    }

    public function updatedFormCategoryId($value): void
    {
        $this->form['event_type_ids'] = [];
    }

    public function updatedFormOperationalSameAsLegal($value): void
    {
        // Se la sede operativa coincide con la legale, ripuliamo i campi manuali
        // per evitare dati inconsistenti.
        if ((bool) $value === true) {
            $this->form['operational_country'] = '';
            $this->form['operational_region'] = '';
            $this->form['operational_city'] = '';
            $this->form['operational_postal_code'] = '';
            $this->form['operational_address_line1'] = '';
        }
    }

    protected function rules(): array
    {
        $rules = [
            'profile_image' => ['nullable', 'image', 'max:5120'],
            'form.status' => ['required', 'in:ACTIVE,INACTIVE'],
            'form.account_type' => ['required', 'in:COMPANY,PRIVATE'],
            'form.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'form.event_type_ids' => ['nullable', 'array'],
            'form.event_type_ids.*' => ['integer', 'exists:event_types,id'],

            'form.tax_code' => ['nullable', 'string', 'max:50'],

            'form.legal_country' => ['nullable', 'string', 'max:255'],
            'form.legal_region' => ['nullable', 'string', 'max:255'],
            'form.legal_city' => ['nullable', 'string', 'max:255'],
            'form.legal_postal_code' => ['nullable', 'string', 'max:50'],
            'form.legal_address_line1' => ['nullable', 'string', 'max:255'],

            'form.operational_same_as_legal' => ['boolean'],
            'form.operational_country' => ['nullable', 'string', 'max:255'],
            'form.operational_region' => ['nullable', 'string', 'max:255'],
            'form.operational_city' => ['nullable', 'string', 'max:255'],
            'form.operational_postal_code' => ['nullable', 'string', 'max:50'],
            'form.operational_address_line1' => ['nullable', 'string', 'max:255'],
        ];

        if (($this->form['account_type'] ?? null) === 'COMPANY') {
            $rules['form.company_name'] = ['required', 'string', 'max:255'];
            $rules['form.legal_entity_type'] = ['nullable', 'string', 'max:255'];
            $rules['form.vat_number'] = ['required', 'string', 'max:50'];

            $rules['form.first_name'] = ['nullable'];
            $rules['form.last_name'] = ['nullable'];
        }

        if (($this->form['account_type'] ?? null) === 'PRIVATE') {
            $rules['form.first_name'] = ['required', 'string', 'max:255'];
            $rules['form.last_name'] = ['required', 'string', 'max:255'];

            $rules['form.company_name'] = ['nullable'];
            $rules['form.legal_entity_type'] = ['nullable'];
            $rules['form.vat_number'] = ['nullable'];
        }

        return $rules;
    }

    public function save(GeocodingService $geo): void
    {
        $this->authorize('update', $this->vendorAccount);

        $this->validate();

        $this->vendorAccount->fill([
            'status' => $this->form['status'],
            'category_id' => $this->form['category_id'],
            'account_type' => $this->form['account_type'],

            'company_name' => $this->form['company_name'],
            'legal_entity_type' => $this->form['legal_entity_type'],
            'vat_number' => $this->form['vat_number'],
            'tax_code' => $this->form['tax_code'],

            'first_name' => $this->form['first_name'],
            'last_name' => $this->form['last_name'],

            'legal_country' => $this->form['legal_country'],
            'legal_region' => $this->form['legal_region'],
            'legal_city' => $this->form['legal_city'],
            'legal_postal_code' => $this->form['legal_postal_code'],
            'legal_address_line1' => $this->form['legal_address_line1'],

            'operational_same_as_legal' => (bool) $this->form['operational_same_as_legal'],
            'operational_country' => $this->form['operational_country'],
            'operational_region' => $this->form['operational_region'],
            'operational_address_line1' => $this->form['operational_address_line1'],
        ]);

        // Prima salviamo il vendor se c'è immagine o altro (per poi fare il sync pulito)
        if ($this->profile_image) {
            if ($this->vendorAccount->profile_image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->vendorAccount->profile_image_path);
            }
            $this->vendorAccount->profile_image_path = $this->profile_image->store('vendor-profiles', 'public');
        }

        // Se operativo = legale, copia i campi testuali.
        if ($this->vendorAccount->operational_same_as_legal) {
            $this->vendorAccount->operational_country = $this->vendorAccount->legal_country;
            $this->vendorAccount->operational_region = $this->vendorAccount->legal_region;
            $this->vendorAccount->operational_city = $this->vendorAccount->legal_city;
            $this->vendorAccount->operational_postal_code = $this->vendorAccount->legal_postal_code;
            $this->vendorAccount->operational_address_line1 = $this->vendorAccount->legal_address_line1;
        }

        // Auto-geocoding:
        // - prima proviamo la sede operativa
        // - se fallisce, proviamo la sede legale
        // - se operativo = legale e troviamo coordinate, allineiamo anche la sede legale
        $statusMessage = 'Profilo salvato con successo.';

        $operationalAddress = [
            'address_line1' => $this->vendorAccount->operational_address_line1,
            'address_line2' => $this->vendorAccount->operational_address_line2 ?? null,
            'postal_code' => $this->vendorAccount->operational_postal_code,
            'city' => $this->vendorAccount->operational_city,
            'region' => $this->vendorAccount->operational_region,
            'country' => $this->vendorAccount->operational_country ?? 'IT',
        ];

        $coords = $geo->geocodeItaly($operationalAddress);

        if ($coords) {
            $this->vendorAccount->operational_lat = $coords['lat'];
            $this->vendorAccount->operational_lng = $coords['lng'];

            if ($this->vendorAccount->operational_same_as_legal) {
                $this->vendorAccount->legal_lat = $coords['lat'];
                $this->vendorAccount->legal_lng = $coords['lng'];
            }
        } else {
            $legalAddress = [
                'address_line1' => $this->vendorAccount->legal_address_line1,
                'address_line2' => $this->vendorAccount->legal_address_line2 ?? null,
                'postal_code' => $this->vendorAccount->legal_postal_code,
                'city' => $this->vendorAccount->legal_city,
                'region' => $this->vendorAccount->legal_region,
                'country' => $this->vendorAccount->legal_country ?? 'IT',
            ];

            $coordsLegal = $geo->geocodeItaly($legalAddress);

            if ($coordsLegal) {
                $this->vendorAccount->legal_lat = $coordsLegal['lat'];
                $this->vendorAccount->legal_lng = $coordsLegal['lng'];

                // Se l'operativa non è geocodificabile, almeno usiamo la legale come fallback.
                if (!$this->vendorAccount->operational_lat || !$this->vendorAccount->operational_lng) {
                    $this->vendorAccount->operational_lat = $coordsLegal['lat'];
                    $this->vendorAccount->operational_lng = $coordsLegal['lng'];
                }
            } else {
                $statusMessage = 'Profilo salvato, ma non sono riuscito a geolocalizzare l’indirizzo. Verifica CAP, città e numero civico.';
            }
        }

        $this->vendorAccount->save();

        if (isset($this->form['event_type_ids'])) {
            $this->vendorAccount->eventTypes()->sync($this->form['event_type_ids']);
        }

        session()->flash('status', $statusMessage);

        $this->vendorAccount->refresh()->load(['user', 'category', 'offerings', 'eventTypes']);

        $this->fillForm();
        $this->editing = false;
    }

    public function render()
    {
        $this->authorize('view', $this->vendorAccount);

        $eventTypes = \App\Models\EventType::where('is_active', true)->orderBy('name')->get();

        return view('livewire.vendor.profile.vendor-profile-page', [
            'eventTypes' => $eventTypes
        ]);
    }
}