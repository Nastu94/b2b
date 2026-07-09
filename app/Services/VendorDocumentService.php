<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VendorDocumentService
{
    /**
     * Store a new vendor document.
     * 
     * @param VendorAccount $vendorAccount
     * @param UploadedFile $file
     * @param array $data Additional metadata (type, title, expires_at, status)
     * @param User|null $uploadedBy
     * @return VendorDocument
     */
    public function store(
        VendorAccount $vendorAccount,
        UploadedFile $file,
        array $data = [],
        ?User $uploadedBy = null
    ): VendorDocument {
        $path = null;
        
        try {
            // Save file in private disk: vendor-documents/{vendor_account_id}/uuid.ext
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs(
                'vendor-documents/' . $vendorAccount->id,
                $filename,
                'local'
            );

            return VendorDocument::create([
                'vendor_account_id' => $vendorAccount->id,
                'type' => $data['type'] ?? 'OTHER',
                'title' => $data['title'] ?? null,
                'original_filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'status' => $data['status'] ?? VendorDocument::STATUS_PENDING,
                'expires_at' => $data['expires_at'] ?? null,
                'uploaded_by' => $uploadedBy?->id,
            ]);
        } catch (\Throwable $e) {
            // Rollback physical file if DB insert fails
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    /**
     * Delete a vendor document.
     * Soft delete DB record to keep audit trail, keep physical file.
     * 
     * @param VendorDocument $document
     * @return void
     */
    public function delete(VendorDocument $document): void
    {
        // $document->delete() will perform a soft delete because of SoftDeletes trait.
        // We do NOT delete the physical file for audit reasons.
        $document->delete();
    }
}
