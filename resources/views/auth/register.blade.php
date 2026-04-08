<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />

            <x-slot name="title">
                {{ __('Benvenuto! Crea il tuo account per iniziare a gestire i tuoi eventi.') }}
            </x-slot>
        </x-slot>

        <x-validation-errors class="mb-4" />

        @php
            $categories = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->get();
            $eventTypes = \App\Models\EventType::where('is_active', true)->orderBy('name')->get();

            $oldAccountType = old('account_type', 'COMPANY');
            $oldOperationalSame = old('operational_same_as_legal', '1');

            $operationalSameChecked =
                $oldOperationalSame === '1' ||
                $oldOperationalSame === 1 ||
                $oldOperationalSame === true ||
                $oldOperationalSame === 'on';
        @endphp

        <div class="w-full">
            <form method="POST" action="{{ route('register') }}" id="vendor-register-form" enctype="multipart/form-data"
                class="w-full max-w-6xl mx-auto space-y-6" x-data="{ privacyAccepted: false, contractAccepted: false }">
                @csrf

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Dati accesso</h3>
                                <p class="mt-1 text-xs text-gray-500">Credenziali per accedere al pannello vendor.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-label for="name" value="Nome Account" />
                                <x-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name')" required autofocus />
                            </div>

                            <div>
                                <x-label for="email" value="Email" />
                                <x-input id="email" class="block mt-1 w-full" type="email" name="email"
                                    :value="old('email')" required />
                            </div>

                            <div>
                                <x-label for="password" value="Password" />
                                <x-input id="password" class="block mt-1 w-full" type="password" name="password"
                                    required />
                            </div>

                            <div>
                                <x-label for="password_confirmation" value="Conferma Password" />
                                <x-input id="password_confirmation" class="block mt-1 w-full" type="password"
                                    name="password_confirmation" required />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Profilo vendor</h3>
                                <p class="mt-1 text-xs text-gray-500">Seleziona tipo account e categoria di servizio.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div class="md:col-span-1">
                                <x-label value="Tipo Account" />

                                <div class="mt-2 flex flex-col gap-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="radio" name="account_type" value="COMPANY"
                                            {{ $oldAccountType === 'COMPANY' ? 'checked' : '' }}>
                                        <span>Azienda</span>
                                    </label>

                                    <label class="flex items-center gap-2">
                                        <input type="radio" name="account_type" value="PRIVATE"
                                            {{ $oldAccountType === 'PRIVATE' ? 'checked' : '' }}>
                                        <span>Privato</span>
                                    </label>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <x-label for="category_id" value="Categoria Servizio" />
                                <select id="category_id" name="category_id"
                                    class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-slate-400 focus:ring-slate-400">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <h3 class="text-sm font-semibold text-gray-900 mb-1">Quali eventi copri?</h3>
                            <p class="text-xs text-gray-500 mb-3">Seleziona almeno un tipo di evento in cui lavori o sei disponibile a lavorare.</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($eventTypes as $et)
                                    <label class="flex items-start gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="event_type_ids[]" value="{{ $et->id }}" 
                                            class="mt-0.5 rounded border-gray-300 text-slate-900 focus:ring-slate-400"
                                            {{ is_array(old('event_type_ids')) && in_array($et->id, old('event_type_ids')) ? 'checked' : '' }}>
                                        <span class="leading-tight">{{ $et->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('event_type_ids')
                                <p class="mt-2 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-5">
                            <x-label for="profile_image" value="Logo / Immagine Copertina (Opzionale)" />
                            <input id="profile_image" type="file" name="profile_image" accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 border border-gray-200 rounded-md p-2" />
                            <p class="mt-1 text-xs text-gray-500">Questa immagine verrà mostrata sulla tua Vetrina PrestaShop.</p>
                        </div>

                        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500">
                            La modalità di servizio e il raggio operativo verranno configurati successivamente nei singoli servizi del vendor.
                        </div>
                    </div>

                    <div id="company-fields"
                        class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5 {{ $oldAccountType === 'PRIVATE' ? 'hidden' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Dati Azienda</h3>
                                <p class="mt-1 text-xs text-gray-500">Compila se hai selezionato “Azienda”.</p>
                            </div>
                            <span
                                class="text-[11px] px-2 py-1 rounded-full bg-slate-50 text-slate-700 border border-slate-100">
                                COMPANY
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div>
                                <x-label value="Ragione Sociale" />
                                <x-input class="block mt-1 w-full" type="text" name="company_name"
                                    placeholder="Es. Party Legacy SRL" :value="old('company_name')" />
                            </div>

                            <div>
                                <x-label value="Forma Giuridica" />
                                <x-input class="block mt-1 w-full" type="text" name="legal_entity_type"
                                    placeholder="Es. SRL, SNC..." :value="old('legal_entity_type')" />
                            </div>

                            <div>
                                <x-label value="Partita IVA" />
                                <x-input class="block mt-1 w-full" type="text" name="vat_number" placeholder="IT..."
                                    :value="old('vat_number')" />
                            </div>
                        </div>
                    </div>

                    <div id="private-fields"
                        class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5 {{ $oldAccountType === 'PRIVATE' ? '' : 'hidden' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Dati Privato</h3>
                                <p class="mt-1 text-xs text-gray-500">Compila se hai selezionato “Privato”.</p>
                            </div>
                            <span
                                class="text-[11px] px-2 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200">
                                PRIVATE
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <x-label value="Nome" />
                                <x-input class="block mt-1 w-full" type="text" name="first_name" placeholder="Nome"
                                    :value="old('first_name')" />
                            </div>

                            <div>
                                <x-label value="Cognome" />
                                <x-input class="block mt-1 w-full" type="text" name="last_name" placeholder="Cognome"
                                    :value="old('last_name')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-label value="Codice Fiscale" />
                                <x-input class="block mt-1 w-full" type="text" name="tax_code"
                                    placeholder="Codice fiscale" :value="old('tax_code')" />
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Recapiti Pubblici</h3>
                                <p class="mt-1 text-xs text-gray-500">I contatti commerciali che verranno mostrati ai clienti e in fattura.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-label value="Email Commerciale / Fatturazione" />
                                <x-input class="block mt-1 w-full" type="email" name="billing_email"
                                    placeholder="Info@..." :value="old('billing_email')" />
                            </div>

                            <div>
                                <x-label value="Telefono Pubblico" />
                                <x-input class="block mt-1 w-full" type="text" name="phone"
                                    placeholder="+39..." :value="old('phone')" />
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Sede Legale</h3>
                                <p class="mt-1 text-xs text-gray-500">Dati fiscali/legali principali.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-label value="Nazione" />
                                <x-input class="block mt-1 w-full" type="text" name="legal_country"
                                    placeholder="IT" :value="old('legal_country')" />
                            </div>

                            <div>
                                <x-label value="Provincia / Regione" />
                                <x-input class="block mt-1 w-full" type="text" name="legal_region"
                                    placeholder="Es. MI / Lombardia" :value="old('legal_region')" />
                            </div>

                            <div>
                                <x-label value="Città" />
                                <x-input class="block mt-1 w-full" type="text" name="legal_city"
                                    placeholder="Milano" :value="old('legal_city')" />
                            </div>

                            <div>
                                <x-label value="CAP" />
                                <x-input class="block mt-1 w-full" type="text" name="legal_postal_code"
                                    placeholder="20100" :value="old('legal_postal_code')" />
                            </div>

                            <div class="md:col-span-2">
                                <x-label value="Indirizzo" />
                                <x-input class="block mt-1 w-full" type="text" name="legal_address_line1"
                                    placeholder="Via..." :value="old('legal_address_line1')" />
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Sede Operativa</h3>
                                <p class="mt-1 text-xs text-gray-500">Se diversa dalla sede legale, inserisci i dati.</p>
                            </div>

                            <label class="flex items-center gap-2 text-sm">
                                <input id="operational_same_as_legal" type="checkbox"
                                    name="operational_same_as_legal" value="1"
                                    {{ $operationalSameChecked ? 'checked' : '' }}>
                                <span>Uguale alla sede legale</span>
                            </label>
                        </div>

                        <div id="operational-fields" class="mt-4 {{ $operationalSameChecked ? 'hidden' : '' }}">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <x-label value="Nazione" />
                                    <x-input class="block mt-1 w-full" type="text" name="operational_country"
                                        placeholder="IT" :value="old('operational_country')" />
                                </div>

                                <div>
                                    <x-label value="Provincia / Regione" />
                                    <x-input class="block mt-1 w-full" type="text" name="operational_region"
                                        placeholder="Provincia/Regione" :value="old('operational_region')" />
                                </div>

                                <div>
                                    <x-label value="Città" />
                                    <x-input class="block mt-1 w-full" type="text" name="operational_city"
                                        placeholder="Città" :value="old('operational_city')" />
                                </div>

                                <div>
                                    <x-label value="CAP" />
                                    <x-input class="block mt-1 w-full" type="text" name="operational_postal_code"
                                        placeholder="CAP" :value="old('operational_postal_code')" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-label value="Indirizzo" />
                                    <x-input class="block mt-1 w-full" type="text"
                                        name="operational_address_line1" placeholder="Indirizzo" :value="old('operational_address_line1')" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5 space-y-4" x-data="{ showPrivacy: false, showContract: false }">
                        <x-label for="privacy_accepted">
                            <div class="flex items-start">
                                <x-checkbox name="privacy_accepted" id="privacy_accepted" required class="mt-1 border-slate-300 text-amber-500 focus:ring-amber-500" x-model="privacyAccepted" />
                                <div class="ms-3 text-sm text-slate-600">
                                    Ho letto e accetto la 
                                    <button type="button" @click="showPrivacy = true" class="text-amber-600 hover:text-amber-700 underline underline-offset-2 font-medium">Privacy Policy</button>*
                                </div>
                            </div>
                        </x-label>

                        <x-label for="contract_accepted">
                            <div class="flex items-start">
                                <x-checkbox name="contract_accepted" id="contract_accepted" required class="mt-1 border-slate-300 text-amber-500 focus:ring-amber-500" x-model="contractAccepted" />
                                <div class="ms-3 text-sm text-slate-600">
                                    Ho letto e accetto le 
                                    <button type="button" @click="showContract = true" class="text-amber-600 hover:text-amber-700 underline underline-offset-2 font-medium">Condizioni Generali di Contratto per Venditori</button>*
                                </div>
                            </div>
                        </x-label>

                        <!-- Modale Privacy -->
                        <div x-show="showPrivacy" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="showPrivacy" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/75 transition-opacity" @click="showPrivacy = false" aria-hidden="true"></div>
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                <div x-show="showPrivacy" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <div class="sm:flex sm:items-start">
                                            <div class="mt-3 text-left sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                <h3 class="text-lg leading-6 font-semibold text-slate-900" id="modal-title">Privacy Policy</h3>
                                                <div class="mt-4 text-sm text-slate-600 space-y-4 max-h-96 overflow-y-auto pr-2">
                                                    <!-- Inserire qui il testo della Privacy fornito dal cliente -->
                                                    <p class="font-medium">Testo della Privacy Policy in lavorazione... Da integrare al rilascio ufficiale dei documenti da parte della proprietà.</p>
                                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam in odio in libero rhoncus mattis. Integer lacinia, orci non congue vulputate, nibh eros facilisis mi, a volutpat arcu dui eu nisi. Fusce vitae finibus magna. Suspendisse pulvinar interdum consequat. Integer accumsan pretium convallis.</p>
                                                    <p>Pellentesque id nunc est. Nunc condimentum lectus risus, in facilisis neque viverra non. Vestibulum ut aliquet nisi. Nunc dapibus mauris metus. Aenean vel velit id est interdum rutrum at ac ipsum.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl border-t border-slate-200">
                                        <button type="button" @click="showPrivacy = false" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-slate-900 text-base font-medium text-white hover:bg-slate-800 sm:ml-3 sm:w-auto sm:text-sm">
                                            Chiudi e Torna al Form
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modale Contratto -->
                        <div x-show="showContract" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="showContract" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/75 transition-opacity" @click="showContract = false" aria-hidden="true"></div>
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                <div x-show="showContract" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <div class="sm:flex sm:items-start">
                                            <div class="mt-3 text-left sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                <h3 class="text-lg leading-6 font-semibold text-slate-900" id="modal-title">Condizioni Generali di Contratto per Venditori</h3>
                                                <div class="mt-4 text-sm text-slate-600 space-y-4 max-h-96 overflow-y-auto pr-2">
                                                    <!-- Inserire qui il testo del Contratto fornito dal cliente -->
                                                    <p class="font-medium">Condizioni di contratto in lavorazione... Da integrare al rilascio ufficiale dei documenti da parte dello studio legale della proprietà.</p>
                                                    <p>Suspendisse potenti. Mauris vitae libero quis augue luctus tincidunt et et nisl. Quisque sit amet lectus varius, gravida nisi et, eleifend magna. Etiam et ex ante. Sed eleifend turpis et justo sollicitudin finibus.</p>
                                                    <p>Ut id dui sapien. Fusce commodo metus in turpis imperdiet tristique. Ut sit amet nibh a nisi tristique dignissim. Vivamus lacinia neque iaculis urna congue bibendum. Aliquam efficitur ex in tellus suscipit pulvinar.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl border-t border-slate-200">
                                        <button type="button" @click="showContract = false" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-slate-900 text-base font-medium text-white hover:bg-slate-800 sm:ml-3 sm:w-auto sm:text-sm">
                                            Chiudi e Torna al Form
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-2 flex items-center justify-between pt-2">
                        <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                            Già registrato?
                        </a>

                        <x-button x-bind:disabled="!privacyAccepted || !contractAccepted" class="disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            Registrati
                        </x-button>
                    </div>

                </div>

            </form>

            <script>
                (function() {
                    const form = document.getElementById('vendor-register-form');
                    if (!form) return;

                    const company = document.getElementById('company-fields');
                    const priv = document.getElementById('private-fields');
                    const accountRadios = form.querySelectorAll('input[name="account_type"]');

                    const opSame = document.getElementById('operational_same_as_legal');
                    const opFields = document.getElementById('operational-fields');

                    function syncAccountType() {
                        const val = form.querySelector('input[name="account_type"]:checked')?.value;

                        if (val === 'PRIVATE') {
                            company.classList.add('hidden');
                            priv.classList.remove('hidden');
                        } else {
                            priv.classList.add('hidden');
                            company.classList.remove('hidden');
                        }
                    }

                    function syncOperational() {
                        if (!opSame || !opFields) return;

                        if (opSame.checked) {
                            opFields.classList.add('hidden');
                        } else {
                            opFields.classList.remove('hidden');
                        }
                    }

                    accountRadios.forEach(radio => radio.addEventListener('change', syncAccountType));
                    opSame?.addEventListener('change', syncOperational);

                    syncAccountType();
                    syncOperational();
                })();
            </script>
        </div>
    </x-authentication-card>
</x-guest-layout>