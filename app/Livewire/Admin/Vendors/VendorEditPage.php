<?php

namespace App\Livewire\Admin\Vendors;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\VendorAccount;
use App\Models\Category;

#[Layout('layouts.admin')] // usata solo per il layout, non per i dati, che vengono caricati in mount() - altrimenti mi dava errore vscode  
class VendorEditPage extends Component
{
    public VendorAccount $vendorAccount;

    public array $form = [];
    public array $categories = [];

    public bool $confirmingDelete = false;

    // Caricamento dati del vendor e categorie per select in mount() 
    public function mount(VendorAccount $vendorAccount): void
    {
        $this->vendorAccount = $vendorAccount->load(['user', 'category']);

        $this->categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
            ->all();

        $va = $this->vendorAccount;

        // Inizializzazione form con i dati del vendor. In questo modo, se il vendor ha già dei dati, li vediamo nel form. Se invece è un nuovo vendor (creato da admin), il form sarà vuoto e l'admin potrà compilarlo.
        $this->form = [
            'status' => $vendorAccount->status,
            'account_type' => $vendorAccount->account_type,
            'category_id' => $vendorAccount->category_id,

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
    }

    // Regole di validazione dinamiche in base al tipo di account (company/private)
    protected function rules(): array
    {
        $rules = [

            // Base
            'form.status' => ['required', 'in:ACTIVE,INACTIVE'],
            'form.account_type' => ['required', 'in:COMPANY,PRIVATE'],
            'form.category_id' => ['nullable', 'integer', 'exists:categories,id'],

            // Campi comuni
            'form.tax_code' => ['nullable', 'string', 'max:50'],

            // Sede legale
            'form.legal_country' => ['nullable', 'string', 'max:255'],
            'form.legal_region' => ['nullable', 'string', 'max:255'],
            'form.legal_city' => ['nullable', 'string', 'max:255'],
            'form.legal_postal_code' => ['nullable', 'string', 'max:50'],
            'form.legal_address_line1' => ['nullable', 'string', 'max:255'],

            // Operativa
            'form.operational_same_as_legal' => ['boolean'],
            'form.operational_country' => ['nullable', 'string', 'max:255'],
            'form.operational_region' => ['nullable', 'string', 'max:255'],
            'form.operational_city' => ['nullable', 'string', 'max:255'],
            'form.operational_postal_code' => ['nullable', 'string', 'max:50'],
            'form.operational_address_line1' => ['nullable', 'string', 'max:255'],
        ];

        // COMPANY
        if ($this->form['account_type'] === 'COMPANY') {
            $rules['form.company_name'] = ['required', 'string', 'max:255'];
            $rules['form.legal_entity_type'] = ['nullable', 'string', 'max:255'];
            $rules['form.vat_number'] = ['required', 'string', 'max:50'];

            // PRIVATE non obbligatori
            $rules['form.first_name'] = ['nullable'];
            $rules['form.last_name'] = ['nullable'];
        }

        // PRIVATE
        if ($this->form['account_type'] === 'PRIVATE') {
            $rules['form.first_name'] = ['required', 'string', 'max:255'];
            $rules['form.last_name'] = ['required', 'string', 'max:255'];

            // COMPANY non obbligatori
            $rules['form.company_name'] = ['nullable'];
            $rules['form.legal_entity_type'] = ['nullable'];
            $rules['form.vat_number'] = ['nullable'];
        }

        return $rules;
    }

    // Salvataggio dati del vendor
    public function save(): void
    {
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

        // se same_as_legal => copia i dati (utile)
        if ($this->vendorAccount->operational_same_as_legal) {
            $this->vendorAccount->operational_country = $this->vendorAccount->legal_country;
            $this->vendorAccount->operational_region = $this->vendorAccount->legal_region;
            $this->vendorAccount->operational_city = $this->vendorAccount->legal_city;
            $this->vendorAccount->operational_postal_code = $this->vendorAccount->legal_postal_code;
            $this->vendorAccount->operational_address_line1 = $this->vendorAccount->legal_address_line1;
        }

        $this->vendorAccount->save();

        session()->flash('status', 'Anagrafica salvata con successo.');
    }

    // Funzioni per cancellazione vendor (soft delete)
    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    
    public function deleteVendor(): void
    {
        $this->vendorAccount->delete(); // soft delete
        redirect()->route('admin.dashboard');
    }

    public function render()
    {
        $displayName = $this->vendorAccount->company_name
            ?: trim(($this->vendorAccount->first_name ?? '') . ' ' . ($this->vendorAccount->last_name ?? ''))
            ?: ('Vendor #' . $this->vendorAccount->id);

        return view('livewire.admin.vendors.vendor-edit-page', [
            'title' => 'Vendor: ' . $displayName,
        ]);
    }
}
