<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegalDocumentController extends Controller
{
    public function vendorPrivacy()
    {
        return view('legal.vendor.privacy');
    }

    public function vendorContract()
    {
        return view('legal.vendor.contract');
    }

    public function vendorFile($filename)
    {
        $path = storage_path('app/legal/vendor/' . $filename);

        if (!file_exists($path)) {
            abort(404, 'File non trovato.');
        }

        return response()->file($path);
    }
}
