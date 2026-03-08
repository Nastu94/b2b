<?php

namespace App\Livewire\Admin\Vendors;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Category;
use App\Models\VendorAccount;
use App\Services\CreateVendorService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

#[Layout('layouts.admin')]
class VendorCreatePage extends Component
{
    use AuthorizesRequests;

    public array $form = [
        // accesso
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',

        // tipo + categoria
        'account_type' => 'COMPANY',
        'category_id' => null,

        // company
        'company_name' => '',
        'legal_entity_type' => '',
        'vat_number' => '',

        // private
        'first_name' => '',
        'last_name' => '',
        'tax_code' => '',

        // sede legale
        'legal_country' => '',
        'legal_region' => '',
        'legal_city' => '',
        'legal_postal_code' => '',
        'legal_address_line1' => '',

        // sede operativa
        'operational_same_as_legal' => true,
        'operational_country' => '',
        'operational_region' => '',
        'operational_city' => '',
        'operational_postal_code' => '',
        'operational_address_line1' => '',
    ];

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        abort_unless($user->can('admin.access'), 403);

        $this->authorize('create', VendorAccount::class);

        // Default iniziali coerenti con la registrazione pubblica.
        $this->form['legal_country'] = $this->form['legal_country'] ?: 'IT';
    }

    public function updatedFormOperationalSameAsLegal($value): void
    {
        // Se la sede operativa coincide con la legale, ripuliamo i campi operativi
        // per evitare dati incoerenti nel form.
        if ((bool) $value === true) {
            $this->form['operational_country'] = '';
            $this->form['operational_region'] = '';
            $this->form['operational_city'] = '';
            $this->form['operational_postal_code'] = '';
            $this->form['operational_address_line1'] = '';
        }
    }

    public function rules(): array
    {
        $rules = [
            // accesso
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'form.password' => ['required', 'string', 'min:8', 'confirmed'],

            // tipo + categoria
            'form.account_type' => ['required', Rule::in(['COMPANY', 'PRIVATE'])],
            'form.category_id' => ['required', 'integer', 'exists:categories,id'],

            // sede legale
            'form.legal_country' => ['nullable', 'string', 'max:255'],
            'form.legal_region' => ['nullable', 'string', 'max:255'],
            'form.legal_city' => ['nullable', 'string', 'max:255'],
            'form.legal_postal_code' => ['nullable', 'string', 'max:50'],
            'form.legal_address_line1' => ['nullable', 'string', 'max:255'],

            // sede operativa flag
            'form.operational_same_as_legal' => ['boolean'],
        ];

        // Blocco COMPANY.
        if ($this->form['account_type'] === 'COMPANY') {
            $rules['form.company_name'] = ['required', 'string', 'max:255'];
            $rules['form.legal_entity_type'] = ['nullable', 'string', 'max:255'];
            $rules['form.vat_number'] = ['required', 'string', 'max:50'];

            $rules['form.tax_code'] = ['nullable', 'string', 'max:50'];
            $rules['form.first_name'] = ['nullable', 'string', 'max:255'];
            $rules['form.last_name'] = ['nullable', 'string', 'max:255'];
        }

        // Blocco PRIVATE.
        if ($this->form['account_type'] === 'PRIVATE') {
            $rules['form.first_name'] = ['required', 'string', 'max:255'];
            $rules['form.last_name'] = ['required', 'string', 'max:255'];

            // Manteniamo il comportamento coerente con il form admin attuale.
            $rules['form.tax_code'] = ['nullable', 'string', 'max:50'];

            $rules['form.company_name'] = ['nullable', 'string', 'max:255'];
            $rules['form.legal_entity_type'] = ['nullable', 'string', 'max:255'];
            $rules['form.vat_number'] = ['nullable', 'string', 'max:50'];
        }

        // Sede operativa.
        if (!($this->form['operational_same_as_legal'] ?? true)) {
            $rules['form.operational_country'] = ['nullable', 'string', 'max:255'];
            $rules['form.operational_region'] = ['nullable', 'string', 'max:255'];
            $rules['form.operational_city'] = ['nullable', 'string', 'max:255'];
            $rules['form.operational_postal_code'] = ['nullable', 'string', 'max:50'];
            $rules['form.operational_address_line1'] = ['nullable', 'string', 'max:255'];
        } else {
            // Se la sede operativa coincide con quella legale, i campi restano vuoti
            // e verranno popolati nel save.
            $rules['form.operational_country'] = ['nullable'];
            $rules['form.operational_region'] = ['nullable'];
            $rules['form.operational_city'] = ['nullable'];
            $rules['form.operational_postal_code'] = ['nullable'];
            $rules['form.operational_address_line1'] = ['nullable'];
        }

        return $rules;
    }

    public function save(CreateVendorService $createVendorService)
    {
        $this->authorize('create', VendorAccount::class);

        $this->validate();

        // Se la sede operativa coincide con la sede legale, copiamo i dati.
        if ($this->form['operational_same_as_legal']) {
            $this->form['operational_country'] = $this->form['legal_country'];
            $this->form['operational_region'] = $this->form['legal_region'];
            $this->form['operational_city'] = $this->form['legal_city'];
            $this->form['operational_postal_code'] = $this->form['legal_postal_code'];
            $this->form['operational_address_line1'] = $this->form['legal_address_line1'];
        }

        $vendorAccount = $createVendorService->create($this->form);

        session()->flash('status', 'Vendor creato con successo.');

        return redirect()->route('admin.vendors.edit', $vendorAccount);
    }

    public function render()
    {
        $this->authorize('create', VendorAccount::class);

        return view('livewire.admin.vendors.vendor-create-page', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'title' => 'Crea Vendor',
        ]);
    }
}