<?php

namespace App\Livewire\Admin\Vendors\Tabs;

use App\Models\Category;
use App\Models\VendorAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithFileUploads;

class VendorAnagraficaTab extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public $profile_image;

    public int $vendorAccountId;

    #[Reactive]
    public bool $editing = false;

    public VendorAccount $vendorAccount;

    public array $form = [];
    public array $originalForm = [];
    public array $categories = [];

    public function mount(int $vendorAccountId): void
    {
        $this->vendorAccountId = $vendorAccountId;

        $this->vendorAccount = VendorAccount::query()
            ->with(['user', 'category', 'offerings'])
            ->findOrFail($vendorAccountId);

        $this->authorize('view', $this->vendorAccount);

        $this->categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();

        $this->form = [
            'status' => $this->vendorAccount->status,
            'account_type' => $this->vendorAccount->account_type,
            'category_id' => $this->vendorAccount->category_id,
            'event_type_ids' => $this->vendorAccount->eventTypes->pluck('id')->all(),

            // COMPANY
            'company_name' => $this->vendorAccount->company_name,
            'legal_entity_type' => $this->vendorAccount->legal_entity_type,
            'vat_number' => $this->vendorAccount->vat_number,

            // PRIVATE
            'first_name' => $this->vendorAccount->first_name,
            'last_name' => $this->vendorAccount->last_name,

            // COMMON
            'tax_code' => $this->vendorAccount->tax_code,
            'billing_email' => $this->vendorAccount->billing_email,
            'phone' => $this->vendorAccount->phone,

            // LEGAL SEAT
            'legal_country' => $this->vendorAccount->legal_country,
            'legal_region' => $this->vendorAccount->legal_region,
            'legal_city' => $this->vendorAccount->legal_city,
            'legal_postal_code' => $this->vendorAccount->legal_postal_code,
            'legal_address_line1' => $this->vendorAccount->legal_address_line1,

            // OPERATIONAL SEAT
            'operational_same_as_legal' => (bool) $this->vendorAccount->operational_same_as_legal,
            'operational_country' => $this->vendorAccount->operational_country,
            'operational_region' => $this->vendorAccount->operational_region,
            'operational_city' => $this->vendorAccount->operational_city,
            'operational_postal_code' => $this->vendorAccount->operational_postal_code,
            'operational_address_line1' => $this->vendorAccount->operational_address_line1,
        ];

        $this->originalForm = $this->form;
    }

    public function updatedFormOperationalSameAsLegal($value): void
    {
        // Se la sede operativa coincide con la sede legale, puliamo i campi
        // manuali per evitare dati incoerenti.
        if ((bool) $value === true) {
            $this->form['operational_country'] = '';
            $this->form['operational_region'] = '';
            $this->form['operational_city'] = '';
            $this->form['operational_postal_code'] = '';
            $this->form['operational_address_line1'] = '';
        }
    }

    public function updatedFormCategoryId($value): void
    {
        $this->form['event_type_ids'] = [];
    }

    protected function rules(): array
    {
        $rules = [
            'profile_image' => ['nullable', 'image', 'max:5120'],
            'form.status' => ['required', 'in:PENDING,ACTIVE,INACTIVE'],
            'form.account_type' => ['required', 'in:COMPANY,PRIVATE'],
            'form.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'form.event_type_ids' => ['nullable', 'array'],
            'form.event_type_ids.*' => ['integer', 'exists:event_types,id'],

            'form.tax_code' => ['nullable', 'string', 'max:50'],
            'form.billing_email' => ['nullable', 'email', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:50'],

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

    public function save(): void
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
            'billing_email' => $this->form['billing_email'],
            'phone' => $this->form['phone'],

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

        if ($this->profile_image) {
            if ($this->vendorAccount->profile_image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->vendorAccount->profile_image_path);
            }
            $this->vendorAccount->profile_image_path = $this->profile_image->store('vendor-profiles', 'public');
        }

        // Se la sede operativa coincide con quella legale, copiamo i valori.
        if ($this->vendorAccount->operational_same_as_legal) {
            $this->vendorAccount->operational_country = $this->vendorAccount->legal_country;
            $this->vendorAccount->operational_region = $this->vendorAccount->legal_region;
            $this->vendorAccount->operational_city = $this->vendorAccount->legal_city;
            $this->vendorAccount->operational_postal_code = $this->vendorAccount->legal_postal_code;
            $this->vendorAccount->operational_address_line1 = $this->vendorAccount->legal_address_line1;
        }

        $this->vendorAccount->save();

        if (isset($this->form['event_type_ids'])) {
            $this->vendorAccount->eventTypes()->sync($this->form['event_type_ids']);
        }

        session()->flash('status', 'Anagrafica salvata con successo.');

        $this->vendorAccount->refresh()->load(['user', 'category', 'offerings', 'eventTypes']);

        $this->form = [
            'status' => $this->vendorAccount->status,
            'account_type' => $this->vendorAccount->account_type,
            'category_id' => $this->vendorAccount->category_id,
            'event_type_ids' => $this->vendorAccount->eventTypes->pluck('id')->all(),

            'company_name' => $this->vendorAccount->company_name,
            'legal_entity_type' => $this->vendorAccount->legal_entity_type,
            'vat_number' => $this->vendorAccount->vat_number,

            'first_name' => $this->vendorAccount->first_name,
            'last_name' => $this->vendorAccount->last_name,

            'tax_code' => $this->vendorAccount->tax_code,
            'billing_email' => $this->vendorAccount->billing_email,
            'phone' => $this->vendorAccount->phone,

            'legal_country' => $this->vendorAccount->legal_country,
            'legal_region' => $this->vendorAccount->legal_region,
            'legal_city' => $this->vendorAccount->legal_city,
            'legal_postal_code' => $this->vendorAccount->legal_postal_code,
            'legal_address_line1' => $this->vendorAccount->legal_address_line1,

            'operational_same_as_legal' => (bool) $this->vendorAccount->operational_same_as_legal,
            'operational_country' => $this->vendorAccount->operational_country,
            'operational_region' => $this->vendorAccount->operational_region,
            'operational_city' => $this->vendorAccount->operational_city,
            'operational_postal_code' => $this->vendorAccount->operational_postal_code,
            'operational_address_line1' => $this->vendorAccount->operational_address_line1,
        ];

        $this->originalForm = $this->form;

        $this->dispatch('vendor-anagrafica-saved', vendorAccountId: $this->vendorAccount->id);
    }

    #[On('vendor-anagrafica-cancel-edit')]
    public function onCancelEdit(int $vendorAccountId): void
    {
        if ($vendorAccountId !== $this->vendorAccount->id) {
            return;
        }

        $this->form = $this->originalForm;
        $this->profile_image = null;
        $this->resetValidation();
    }

    #[On('vendor-anagrafica-enter-edit')]
    public function onEnterEdit(int $vendorAccountId): void
    {
        if ($vendorAccountId !== $this->vendorAccount->id) {
            return;
        }

        // Il container controlla già lo stato di editing.
    }

    public function render()
    {
        $this->authorize('view', $this->vendorAccount);

        $eventTypes = \App\Models\EventType::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.vendors.tabs.vendor-anagrafica-tab', [
            'eventTypes' => $eventTypes
        ]);
    }
}