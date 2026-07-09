<?php

namespace App\Livewire\Vendor\Profile;

use App\Models\VendorAccount;
use App\Models\VendorDocument;
use App\Services\VendorDocumentService;
use Livewire\Component;
use Livewire\WithFileUploads;

class VendorDocumentsSection extends Component
{
    use WithFileUploads;

    public int $vendorAccountId;
    public VendorAccount $vendorAccount;

    public $document_file;
    public string $type = '';
    public ?string $title = null;
    public ?string $expires_at = null;

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->hasRole('vendor'), 403);

        $this->vendorAccount = VendorAccount::where('user_id', $user->id)->firstOrFail();
        $this->vendorAccountId = $this->vendorAccount->id;
    }

    public function uploadDocument(VendorDocumentService $service)
    {
        $this->validate([
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'type' => ['required', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ], [
            'type.required' => 'Devi selezionare il tipo di documento.'
        ]);

        abort_unless((int) $this->vendorAccount->user_id === (int) auth()->id(), 403);

        $service->store(
            $this->vendorAccount,
            $this->document_file,
            [
                'type' => $this->type,
                'title' => $this->title,
                'expires_at' => $this->expires_at,
                'status' => VendorDocument::STATUS_PENDING,
            ],
            auth()->user()
        );

        $this->reset(['document_file', 'type', 'title', 'expires_at']);
        session()->flash('message', 'Documento caricato con successo ed è in attesa di approvazione.');
    }

    public function deleteDocument(int $documentId, VendorDocumentService $service)
    {
        $document = VendorDocument::findOrFail($documentId);

        // Verifica che il vendor sia proprietario
        abort_unless($document->vendor_account_id === $this->vendorAccount->id, 403);
        
        // Il vendor può eliminare solo PENDING o REJECTED
        abort_unless(in_array($document->status, [VendorDocument::STATUS_PENDING, VendorDocument::STATUS_REJECTED]), 403, 'Non puoi eliminare un documento approvato.');

        $service->delete($document);
        session()->flash('message', 'Documento eliminato.');
    }

    public function render()
    {
        return view('livewire.vendor.profile.vendor-documents-section', [
            'documents' => $this->vendorAccount->documents()->latest()->get()
        ]);
    }
}
