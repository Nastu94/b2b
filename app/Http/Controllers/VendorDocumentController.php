<?php

namespace App\Http\Controllers;

use App\Models\VendorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorDocumentController extends Controller
{
    /**
     * Download the document.
     */
    public function download(VendorDocument $document): StreamedResponse
    {
        $user = auth()->user();

        abort_unless($user, 401);

        // Autorizzazione (stessa logica della policy per semplicità/ridondanza)
        $isAdmin = $user->hasRole('admin');
        $isOwnerVendor = $user->hasRole('vendor') && $document->vendorAccount && $document->vendorAccount->user_id === $user->id;

        abort_unless($isAdmin || $isOwnerVendor, 403);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download(
            $document->path,
            $document->original_filename
        );
    }
}
