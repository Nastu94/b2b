<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Crea Vendor</h1>
            <p class="mt-1 text-sm text-slate-500">
                Compila il form per creare un nuovo account vendor (stessa struttura del register).
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}"
           class="text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
            ← Torna alla dashboard
        </a>
    </div>

    {{-- Flash --}}
    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Form --}}
    <form wire:submit.prevent="save" class="space-y-6">

        {{-- DATI ACCESSO --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Dati accesso</h2>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Nome Account</label>
                    <input type="text" wire:model="form.name"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                    @error('form.name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Email</label>
                    <input type="email" wire:model="form.email"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                    @error('form.email') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Password</label>
                    <input type="password" wire:model="form.password"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                    @error('form.password') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Conferma Password</label>
                    <input type="password" wire:model="form.password_confirmation"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                </div>
            </div>
        </div>

        {{-- TIPO + CATEGORIA --}}
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

                    @error('form.account_type') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Categoria Servizio</label>
                    <select wire:model="form.category_id"
                            class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400">
                        <option value="">—</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>

                    @error('form.category_id') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- DATI AZIENDA / PRIVATO --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Dati anagrafici</h2>

            @if($form['account_type'] === 'COMPANY')
                <div class="mt-4 border border-slate-200 rounded-lg p-4 bg-indigo-50/40">
                    <div class="font-semibold text-slate-900 mb-3">Dati Azienda</div>

                    <label class="text-sm text-slate-600">Ragione Sociale</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           type="text" wire:model="form.company_name" placeholder="Es. Party Legacy SRL">
                    @error('form.company_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror

                    <div class="mt-3">
                        <label class="text-sm text-slate-600">Forma Giuridica</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.legal_entity_type" placeholder="Es. SRL, SNC...">
                        @error('form.legal_entity_type') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-3">
                        <label class="text-sm text-slate-600">Partita IVA</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.vat_number" placeholder="IT...">
                        @error('form.vat_number') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endif

            @if($form['account_type'] === 'PRIVATE')
                <div class="mt-4 border border-slate-200 rounded-lg p-4 bg-slate-50">
                    <div class="font-semibold text-slate-900 mb-3">Dati Privato</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm text-slate-600">Nome</label>
                            <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   type="text" wire:model="form.first_name" placeholder="Nome">
                            @error('form.first_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Cognome</label>
                            <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   type="text" wire:model="form.last_name" placeholder="Cognome">
                            @error('form.last_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="text-sm text-slate-600">Codice Fiscale</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.tax_code" placeholder="Codice fiscale">
                        @error('form.tax_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endif
        </div>

        {{-- SEDE LEGALE --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Sede Legale</h2>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Nazione</label>
                    <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           type="text" wire:model="form.legal_country" placeholder="IT">
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

        {{-- SEDE OPERATIVA --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold text-slate-900">Sede Operativa</h2>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model.live="form.operational_same_as_legal" value="1">
                    <span>Uguale alla sede legale</span>
                </label>
            </div>

            @if(!($form['operational_same_as_legal'] ?? true))
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-slate-600">Nazione</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.operational_country" placeholder="IT">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Provincia / Regione</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.operational_region" placeholder="Provincia/Regione">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Città</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.operational_city" placeholder="Città">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">CAP</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.operational_postal_code" placeholder="CAP">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm text-slate-600">Indirizzo</label>
                        <input class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                               type="text" wire:model="form.operational_address_line1" placeholder="Indirizzo">
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">
                    I dati della sede operativa verranno copiati dalla sede legale.
                </p>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex justify-end">
            <button type="submit"
                    class="text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                Crea Vendor
            </button>
        </div>

    </form>
</div>