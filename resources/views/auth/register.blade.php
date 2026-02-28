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

            $oldAccountType = old('account_type', 'COMPANY');
            $oldOperationalSame = old('operational_same_as_legal', '1');
            $operationalSameChecked =
                $oldOperationalSame === '1' ||
                $oldOperationalSame === 1 ||
                $oldOperationalSame === true ||
                $oldOperationalSame === 'on';
        @endphp

        <div class="w-full">
            <form method="POST" action="{{ route('register') }}" id="vendor-register-form"
                class="w-full max-w-6xl mx-auto space-y-6">
                @csrf

                {{-- GRID 2 COLONNE (desktop), 1 COLONNA (mobile) --}}
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                    {{-- DATI ACCESSO --}}
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

                    {{-- TIPO + CATEGORIA --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Profilo vendor</h3>
                                <p class="mt-1 text-xs text-gray-500">Seleziona tipo account e categoria di servizio.
                                </p>
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
                                    class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-400 focus:ring-indigo-400">
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- DATI AZIENDA --}}
                    <div id="company-fields"
                        class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5 {{ $oldAccountType === 'PRIVATE' ? 'hidden' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Dati Azienda</h3>
                                <p class="mt-1 text-xs text-gray-500">Compila se hai selezionato “Azienda”.</p>
                            </div>
                            <span
                                class="text-[11px] px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
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

                    {{-- DATI PRIVATO --}}
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

                    {{-- SEDE LEGALE --}}
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

                    {{-- SEDE OPERATIVA --}}
                    <div class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Sede Operativa</h3>
                                <p class="mt-1 text-xs text-gray-500">Se diversa dalla sede legale, inserisci i dati.
                                </p>
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

                    {{-- TERMS (FULL WIDTH) --}}
                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <div class="xl:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
                            <x-label for="terms">
                                <div class="flex items-center">
                                    <x-checkbox name="terms" id="terms" required />
                                    <div class="ms-2 text-sm">
                                        Accetto termini e privacy
                                    </div>
                                </div>
                            </x-label>
                        </div>
                    @endif

                    {{-- FOOT ACTIONS (FULL WIDTH) --}}
                    <div class="xl:col-span-2 flex items-center justify-between pt-2">
                        <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                            Già registrato?
                        </a>

                        <x-button>
                            Registrati
                        </x-button>
                    </div>

                </div>{{-- /grid --}}

            </form>

            {{-- Toggle JS leggero (non rompe nulla) --}}
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
                        if (opSame.checked) opFields.classList.add('hidden');
                        else opFields.classList.remove('hidden');
                    }

                    accountRadios.forEach(r => r.addEventListener('change', syncAccountType));
                    opSame?.addEventListener('change', syncOperational);

                    syncAccountType();
                    syncOperational();
                })();
            </script>
        </div>
    </x-authentication-card>
</x-guest-layout>
