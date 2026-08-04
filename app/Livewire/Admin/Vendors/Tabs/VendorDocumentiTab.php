<?php

namespace App\Livewire\Admin\Vendors\Tabs;

use App\Models\VendorAccount;
use App\Models\VendorDocument;
use App\Services\VendorDocumentService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class VendorDocumentiTab extends Component
{
    use AuthorizesRequests, WithFileUploads;

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
        $this->authorize('view', $this->vendorAccount);
    }

    private function findDocumentForCurrentVendor(int $documentId): VendorDocument
    {
        return VendorDocument::where('vendor_account_id', $this->vendorAccount->id)
            ->findOrFail($documentId);
    }

    public function approveDocument(int $documentId)
    {
        $document = $this->findDocumentForCurrentVendor($documentId);
        $this->authorize('review', $document);
        $document->update([
            'status' => VendorDocument::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => null,
        ]);
        session()->flash('message', 'Documento approvato.');
        $this->dispatch('approvals-updated');
    }

    public function startRejectDocument(int $documentId)
    {
        $this->authorize('review', $this->findDocumentForCurrentVendor($documentId));
        $this->rejectingDocumentId = $documentId;
        $this->review_note = '';
    }

    public function rejectDocument()
    {
        $this->validate([
            'rejectingDocumentId' => ['required', 'integer'],
            'review_note' => 'required|string|max:1000'
        ]);

        $document = $this->findDocumentForCurrentVendor($this->rejectingDocumentId);
        $this->authorize('review', $document);
        $document->update([
            'status' => VendorDocument::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $this->review_note,
        ]);

        $this->rejectingDocumentId = null;
        $this->review_note = '';
        session()->flash('message', 'Documento rifiutato.');
        $this->dispatch('approvals-updated');
    }

    public function deleteDocument(int $documentId, VendorDocumentService $service)
    {
        $document = $this->findDocumentForCurrentVendor($documentId);
        $this->authorize('delete', $document);
        $service->delete($document);
        session()->flash('message', 'Documento eliminato.');
    }

    public function startEditDocument(int $documentId)
    {
        $document = $this->findDocumentForCurrentVendor($documentId);
        $this->authorize('review', $document);
        $this->editingDocumentId = $document->id;
        $this->edit_type = $document->type;
        $this->edit_title = $document->title;
        $this->edit_expires_at = $document->expires_at ? $document->expires_at->format('Y-m-d') : null;
    }

    public function updateDocument()
    {
        $this->validate([
            'editingDocumentId' => ['required', 'integer'],
            'edit_type' => ['required', 'string', 'max:80'],
            'edit_title' => ['nullable', 'string', 'max:255'],
            'edit_expires_at' => ['nullable', 'date'],
        ]);

        $document = $this->findDocumentForCurrentVendor($this->editingDocumentId);
        $this->authorize('review', $document);
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
        $this->authorize('view', $this->vendorAccount);

        return view('livewire.admin.vendors.tabs.vendor-documenti-tab', [
            'documents' => $this->vendorAccount->documents()->latest()->get()
        ]);
    }
}
