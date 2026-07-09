<?php

namespace App\Livewire\Admin\Vendors\Tabs;

use App\Models\VendorAccount;
use App\Models\VendorDocument;
use App\Services\VendorDocumentService;
use Livewire\Component;
use Livewire\WithFileUploads;

class VendorDocumentiTab extends Component
{
    use WithFileUploads;

    public int $vendorAccountId;
    public VendorAccount $vendorAccount;

    // Upload rimosso, i documenti li inserisce il vendor.

    // Reject Form
    public ?int $rejectingDocumentId = null;
    public string $review_note = '';

    // Edit Form
    public ?int $editingDocumentId = null;
    public string $edit_type = 'OTHER';
    public ?string $edit_title = null;
    public ?string $edit_expires_at = null;

    public function mount(int $vendorAccountId)
    {
        $this->vendorAccountId = $vendorAccountId;
        $this->vendorAccount = VendorAccount::findOrFail($vendorAccountId);
    }

    private function findDocumentForCurrentVendor(int $documentId): VendorDocument
    {
        return VendorDocument::where('vendor_account_id', $this->vendorAccount->id)
            ->findOrFail($documentId);
    }

    public function approveDocument(int $documentId)
    {
        $document = $this->findDocumentForCurrentVendor($documentId);
        $document->update([
            'status' => VendorDocument::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => null,
        ]);
        session()->flash('message', 'Documento approvato.');
    }

    public function startRejectDocument(int $documentId)
    {
        $this->rejectingDocumentId = $documentId;
        $this->review_note = '';
    }

    public function rejectDocument()
    {
        $this->validate([
            'review_note' => 'required|string|max:1000'
        ]);

        $document = $this->findDocumentForCurrentVendor($this->rejectingDocumentId);
        $document->update([
            'status' => VendorDocument::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $this->review_note,
        ]);

        $this->rejectingDocumentId = null;
        $this->review_note = '';
        session()->flash('message', 'Documento rifiutato.');
    }

    public function deleteDocument(int $documentId, VendorDocumentService $service)
    {
        $document = $this->findDocumentForCurrentVendor($documentId);
        $service->delete($document);
        session()->flash('message', 'Documento eliminato.');
    }

    public function startEditDocument(int $documentId)
    {
        $document = $this->findDocumentForCurrentVendor($documentId);
        $this->editingDocumentId = $document->id;
        $this->edit_type = $document->type;
        $this->edit_title = $document->title;
        $this->edit_expires_at = $document->expires_at ? $document->expires_at->format('Y-m-d') : null;
    }

    public function updateDocument()
    {
        $this->validate([
            'edit_type' => ['required', 'string', 'max:80'],
            'edit_title' => ['nullable', 'string', 'max:255'],
            'edit_expires_at' => ['nullable', 'date'],
        ]);

        $document = $this->findDocumentForCurrentVendor($this->editingDocumentId);
        $document->update([
            'type' => $this->edit_type,
            'title' => $this->edit_title,
            'expires_at' => $this->edit_expires_at,
        ]);

        $this->editingDocumentId = null;
        session()->flash('message', 'Metadati documento aggiornati.');
    }

    public function render()
    {
        return view('livewire.admin.vendors.tabs.vendor-documenti-tab', [
            'documents' => $this->vendorAccount->documents()->latest()->get()
        ]);
    }
}
