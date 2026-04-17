<div class="space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Crea Vendor</h1>
            <p class="mt-1 text-sm text-slate-500">
                Compila il form per creare un nuovo account vendor.
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}"
            class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-slate-900 text-white bg-slate-900 hover:bg-slate-800">
            <x-app-icon name="arrow-left" class="w-4 h-4" />
            <span>Torna alla dashboard</span>
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Immagine Profilo / Logo</h2>
            <div class="mt-4 flex items-start gap-6">
                <div class="shrink-0">
                    @if ($profile_image)
                        <img src="{{ $profile_image->temporaryUrl() }}" alt="Preview" class="h-24 w-24 rounded-lg object-cover border border-slate-200">
                    @else
                        <div class="h-24 w-24 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center text-slate-400">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="flex-1">
                    <input type="file" wire:model="profile_image" class="block w-full text-sm text-slate-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-medium
                        file:bg-slate-100 file:text-slate-700
                        hover:file:bg-slate-200" />
                    <p class="mt-2 text-xs text-slate-500">Formati consentiti: JPG, PNG. Dimensione massima: 5MB.</p>
                    @error('profile_image') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Dati accesso</h2>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Nome Account</label>
                    <input type="text" wire:model="form.name"
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                    @error('form.name')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Email</label>
                    <input type="email" wire:model="form.email"
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                    @error('form.email')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Password</label>
                    <input type="password" wire:model="form.password"
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                    @error('form.password')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Conferma Password</label>
                    <input type="password" wire:model="form.password_confirmation"
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Tipo account e categoria</h2>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-1">
                    <div class="text-sm text-slate-600">Tipo Account</div>

                    <div class="flex gap-6 mt-2 text-sm text-slate-800">
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model.live="form.account_type" value="COMPANY">
                            <span>Azienda</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model.live="form.account_type" value="PRIVATE">
                            <span>Privato</span>
                        </label>
                    </div>

                    @error('form.account_type')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Categoria Servizio</label>
                    <select wire:model.live="form.category_id"
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    @error('form.category_id')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @if(isset($eventTypes) && count($eventTypes) > 0)
            <div class="mt-6 border-t border-slate-200 pt-5">
                <label class="text-sm font-semibold text-slate-900 block mb-1">Tipi di Evento Supportati</label>
                <p class="text-xs text-slate-500 mb-1">Seleziona in quali tipi di evento questo vendor apparirà tra i risultati di ricerca.</p>
                <p class="text-xs text-amber-600 font-medium mb-4">Puoi selezionare una o più opzioni.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($eventTypes as $et)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="form.event_type_ids" value="{{ $et->id }}" class="rounded border-slate-300">
                            <span>{{ $et->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('form.event_type_ids')
                    <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                @enderror
            </div>
            @endif

            <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                La modalità di servizio e il raggio operativo verranno configurati nei singoli servizi del vendor.
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Dati anagrafici</h2>

            @if ($form['account_type'] === 'COMPANY')
                <div class="mt-4 border border-slate-200 rounded-lg p-4 bg-slate-50/40">
                    <div class="font-semibold text-slate-900 mb-3">Dati Azienda</div>

                    <label class="text-sm text-slate-600">Ragione Sociale</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="text" wire:model="form.company_name" placeholder="Es. Party Legacy SRL">
                    @error('form.company_name')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror

                    <div class="mt-3">
                        <label class="text-sm text-slate-600">Forma Giuridica</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.legal_entity_type" placeholder="Es. SRL, SNC...">
                        @error('form.legal_entity_type')
                            <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <label class="text-sm text-slate-600">Partita IVA</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.vat_number" placeholder="IT...">
                        @error('form.vat_number')
                            <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

            @if ($form['account_type'] === 'PRIVATE')
                <div class="mt-4 border border-slate-200 rounded-lg p-4 bg-slate-50">
                    <div class="font-semibold text-slate-900 mb-3">Dati Privato</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm text-slate-600">Nome</label>
                            <input
                                class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                type="text" wire:model="form.first_name" placeholder="Nome">
                            @error('form.first_name')
                                <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Cognome</label>
                            <input
                                class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                type="text" wire:model="form.last_name" placeholder="Cognome">
                            @error('form.last_name')
                                <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="text-sm text-slate-600">Codice Fiscale</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.tax_code" placeholder="Codice fiscale">
                        @error('form.tax_code')
                            <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Recapiti Pubblici</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Email Commerciale / Fatturazione</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="email" wire:model="form.billing_email" placeholder="Info@...">
                    @error('form.billing_email')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="text-sm text-slate-600">Telefono</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="text" wire:model="form.phone" placeholder="+39...">
                    @error('form.phone')
                        <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Sede Legale</h2>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Nazione</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="text" wire:model="form.legal_country" placeholder="IT">
                    <p class="mt-1 text-xs text-slate-500">Inserire codice ISO (es. IT)</p>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Provincia / Regione</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="text" wire:model="form.legal_region" placeholder="Es. MI / Lombardia">
                </div>

                <div>
                    <label class="text-sm text-slate-600">Città</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="text" wire:model="form.legal_city" placeholder="Milano">
                </div>

                <div>
                    <label class="text-sm text-slate-600">CAP</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="text" wire:model="form.legal_postal_code" placeholder="20100">
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Indirizzo</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        type="text" wire:model="form.legal_address_line1" placeholder="Via...">
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold text-slate-900">Sede Operativa</h2>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model.live="form.operational_same_as_legal" value="1">
                    <span>Uguale alla sede legale</span>
                </label>
            </div>

            @if (!($form['operational_same_as_legal'] ?? true))
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-slate-600">Nazione</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.operational_country" placeholder="IT">
                        <p class="mt-1 text-xs text-slate-500">Inserire codice ISO (es. IT)</p>
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Provincia / Regione</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.operational_region" placeholder="Provincia/Regione">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Città</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.operational_city" placeholder="Città">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">CAP</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.operational_postal_code" placeholder="CAP">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm text-slate-600">Indirizzo</label>
                        <input
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            type="text" wire:model="form.operational_address_line1" placeholder="Indirizzo">
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">
                    I dati della sede operativa verranno copiati dalla sede legale.
                </p>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Consensi Legali</h2>
            <div class="mt-4 space-y-3 text-sm text-slate-700">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="form.privacy_accepted" class="rounded border-slate-300">
                    <span>L'utente ha accettato la Privacy Policy</span>
                </label>
                @error('form.privacy_accepted')
                    <div class="text-sm text-rose-600">{{ $message }}</div>
                @enderror

                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="form.contract_accepted" class="rounded border-slate-300">
                    <span>L'utente ha accettato le Condizioni Contrattuali</span>
                </label>
                @error('form.contract_accepted')
                    <div class="text-sm text-rose-600">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                <x-app-icon name="plus" class="w-4 h-4" />
                <span>Crea Vendor</span>
            </button>
        </div>

    </form>
</div>
