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

    public function vendorFile(string $filename)
    {
        $allowedDocuments = [
            'codice-etico-partylegacy.pdf',
            'contratto-partner-master.pdf',
            'policy-di-verifica-partner.pdf',
            'policy-eventi-per-minori.pdf',
            'policy-fotografi-videomaker-produzione-contenuti.pdf',
            'policy-location-ville-e-spazi-per-eventi.pdf',
            'policy-ncc-limousine-party-bus.pdf',
            'policy-partner-premium.pdf',
            'policy-servizi-per-adulti-partylegacy.pdf',
            'privacy-e-compliance.pdf',
            'programma-sanzionatorio-interno.pdf',
            'regolamento-marketplace-partylegacy.pdf',
            'policy-catering-partylegacy.pdf',
            'policy-attivita-sportive.pdf',
        ];

        abort_unless(in_array($filename, $allowedDocuments, true), 404);

        $path = storage_path('app/legal/vendor/' . $filename);

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
