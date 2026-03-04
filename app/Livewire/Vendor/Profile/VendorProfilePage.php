<?php

namespace App\Livewire\Vendor\Profile;

use App\Models\Category;
use App\Models\VendorAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Services\GeocodingService;

#[Layout('layouts.vendor')]
class VendorProfilePage extends Component
{
    use AuthorizesRequests;

    public VendorAccount $vendorAccount;

    public bool $editing = false;

    public array $form = [];
    public array $originalForm = [];
    public array $categories = [];

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        // Prendo il vendor dell’utente loggato
        $this->vendorAccount = VendorAccount::query()
            ->with(['user', 'category', 'offerings'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('view', $this->vendorAccount);

        $this->categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->all();

        $this->fillForm();
    }

    private function fillForm(): void
    {
        $va = $this->vendorAccount;

        $this->form = [
            'status' => $va->status,
            'account_type' => $va->account_type,
            'category_id' => $va->category_id,

            // COMPANY
            'company_name' => $va->company_name,
            'legal_entity_type' => $va->legal_entity_type,
            'vat_number' => $va->vat_number,

            // PRIVATE
            'first_name' => $va->first_name,
            'last_name' => $va->last_name,

            // COMMON
            'tax_code' => $va->tax_code,

            // LEGAL SEAT
            'legal_country' => $va->legal_country,
            'legal_region' => $va->legal_region,
            'legal_city' => $va->legal_city,
            'legal_postal_code' => $va->legal_postal_code,
            'legal_address_line1' => $va->legal_address_line1,

            // OPERATIONAL SEAT
            'operational_same_as_legal' => (bool) $va->operational_same_as_legal,
            'operational_country' => $va->operational_country,
            'operational_region' => $va->operational_region,
            'operational_city' => $va->operational_city,
            'operational_postal_code' => $va->operational_postal_code,
            'operational_address_line1' => $va->operational_address_line1,
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
        $this->resetValidation();
    }

    protected function rules(): array
    {
        $rules = [
            'form.status' => ['required', 'in:ACTIVE,INACTIVE'],
            'form.account_type' => ['required', 'in:COMPANY,PRIVATE'],
            'form.category_id' => ['nullable', 'integer', 'exists:categories,id'],

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
            'operational_city' => $this->form['operational_city'],
            'operational_postal_code' => $this->form['operational_postal_code'],
            'operational_address_line1' => $this->form['operational_address_line1'],
        ]);

        // Se operativo = legale, copia i campi testo (come già fai)
        if ($this->vendorAccount->operational_same_as_legal) {
            $this->vendorAccount->operational_country = $this->vendorAccount->legal_country;
            $this->vendorAccount->operational_region = $this->vendorAccount->legal_region;
            $this->vendorAccount->operational_city = $this->vendorAccount->legal_city;
            $this->vendorAccount->operational_postal_code = $this->vendorAccount->legal_postal_code;
            $this->vendorAccount->operational_address_line1 = $this->vendorAccount->legal_address_line1;
        }

        /**
         * AUTO-GEOCODING
         * - usiamo prima indirizzo operativo (quello usato per la distanza)
         * - se fallisce, proviamo legale
         * - se operativo = legale e troviamo coords, allineiamo anche legal_lat/lng
         */
        $statusMessage = 'Profilo salvato con successo.';

        $operationalAddr = [
            'address_line1' => $this->vendorAccount->operational_address_line1,
            'address_line2' => $this->vendorAccount->operational_address_line2 ?? null,
            'postal_code'   => $this->vendorAccount->operational_postal_code,
            'city'          => $this->vendorAccount->operational_city,
            'region'        => $this->vendorAccount->operational_region,
            'country'       => $this->vendorAccount->operational_country ?? 'IT',
        ];

        $coords = $geo->geocodeItaly($operationalAddr);

        if ($coords) {
            $this->vendorAccount->operational_lat = $coords['lat'];
            $this->vendorAccount->operational_lng = $coords['lng'];

            if ($this->vendorAccount->operational_same_as_legal) {
                $this->vendorAccount->legal_lat = $coords['lat'];
                $this->vendorAccount->legal_lng = $coords['lng'];
            }
        } else {
            // fallback: prova sede legale
            $legalAddr = [
                'address_line1' => $this->vendorAccount->legal_address_line1,
                'address_line2' => $this->vendorAccount->legal_address_line2 ?? null,
                'postal_code'   => $this->vendorAccount->legal_postal_code,
                'city'          => $this->vendorAccount->legal_city,
                'region'        => $this->vendorAccount->legal_region,
                'country'       => $this->vendorAccount->legal_country ?? 'IT',
            ];

            $coordsLegal = $geo->geocodeItaly($legalAddr);

            if ($coordsLegal) {
                $this->vendorAccount->legal_lat = $coordsLegal['lat'];
                $this->vendorAccount->legal_lng = $coordsLegal['lng'];

                // se l'operativa non è geocodificabile, almeno usiamo la legale come fallback distanza
                if (!$this->vendorAccount->operational_lat || !$this->vendorAccount->operational_lng) {
                    $this->vendorAccount->operational_lat = $coordsLegal['lat'];
                    $this->vendorAccount->operational_lng = $coordsLegal['lng'];
                }
            } else {
                $statusMessage = 'Profilo salvato, ma non sono riuscito a geolocalizzare l’indirizzo. Verifica CAP/città e numero civico.';
            }
        }

        $this->vendorAccount->save();

        session()->flash('status', $statusMessage);

        $this->vendorAccount->refresh()->load(['user', 'category', 'offerings']);

        $this->fillForm();
        $this->editing = false;
    }

    public function render()
    {
        $this->authorize('view', $this->vendorAccount);

        return view('livewire.vendor.profile.vendor-profile-page');
    }
}
