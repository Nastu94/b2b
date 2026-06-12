<x-guest-layout>
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 sm:p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Contratto Partner Master e Documenti Regolamentari</h1>
                
                <div class="prose max-w-none text-gray-600 mb-6">
                    <p>
                        Di seguito l'elenco completo dei documenti contrattuali e delle policy applicabili:
                    </p>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="{{ route('legal.vendor.file', ['filename' => 'contratto-partner-master.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Contratto Partner Master</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'regolamento-marketplace-partylegacy.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Regolamento Marketplace PartyLegacy</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'codice-etico-partylegacy.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Codice Etico PartyLegacy</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-di-verifica-partner.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy di Verifica Partner</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-partner-premium.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy Partner Premium</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'programma-sanzionatorio-interno.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Programma Sanzionatorio Interno</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-eventi-per-minori.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy Eventi per Minori</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-servizi-per-adulti-partylegacy.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy Servizi per Adulti PartyLegacy</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-catering-partylegacy.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy Catering PartyLegacy</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-ncc-limousine-party-bus.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy NCC, Limousine, Party Bus</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-location-ville-e-spazi-per-eventi.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy Location Ville e Spazi per Eventi</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-fotografi-videomaker-produzione-contenuti.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy Fotografi, Videomaker e Produzione Contenuti</a>
                    <a href="{{ route('legal.vendor.file', ['filename' => 'policy-attivita-sportive.pdf']) }}" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">Policy Attività Sportive</a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
