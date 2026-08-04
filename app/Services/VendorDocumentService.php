<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
            $extension = match (strtolower((string) $file->getMimeType())) {
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => throw new \InvalidArgumentException('Formato documento non supportato.'),
            };

            // Save file in private disk: vendor-documents/{vendor_account_id}/uuid.ext
            $filename = Str::uuid() . '.' . $extension;
            $path = $file->storeAs(
                'vendor-documents/' . $vendorAccount->id,
                $filename,
                'local'
            );

            return DB::transaction(function () use ($vendorAccount, $file, $data, $uploadedBy, $path) {
                // Soft delete vecchi documenti della stessa categoria.
                $type = $data['type'] ?? 'OTHER';
                VendorDocument::where('vendor_account_id', $vendorAccount->id)
                    ->where('type', $type)
                    ->get()
                    ->each
                    ->delete();

                $originalFilename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $originalFilename = Str::limit($originalFilename, 255, '');
                $title = isset($data['title'])
                    ? Str::limit(trim(strip_tags((string) $data['title'])), 255, '')
                    : null;

                return VendorDocument::create([
                    'vendor_account_id' => $vendorAccount->id,
                    'type' => $type,
                    'title' => $title,
                    'original_filename' => $originalFilename,
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'status' => $data['status'] ?? VendorDocument::STATUS_PENDING,
                    'expires_at' => $data['expires_at'] ?? null,
                    'uploaded_by' => $uploadedBy?->id,
                ]);
            });
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
