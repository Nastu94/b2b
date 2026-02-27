<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold text-slate-900">Anagrafica Vendor</h1>

            @php
                $isCompany = ($form['account_type'] ?? '') === 'COMPANY';
                $isPrivate = ($form['account_type'] ?? '') === 'PRIVATE';

                $displayName = $isCompany
                    ? ($form['company_name'] ?? '')
                    : trim(($form['first_name'] ?? '').' '.($form['last_name'] ?? ''));

                if (trim($displayName) === '') {
                    $displayName = 'Vendor';
                }
            @endphp

            <p class="mt-1 text-sm text-slate-500">
                <span class="font-medium text-slate-900">{{ $displayName }}</span>
                @if($vendorAccount->user?->email)
                    • Email: <span class="font-medium text-slate-900">{{ $vendorAccount->user->email }}</span>
                @endif
               
            </p>
        </div>

        <div class="flex gap-2 shrink-0">
            <a href="{{ route('admin.dashboard') }}"
               class="text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                ← Torna alla lista
            </a>

            <button type="button"
                    wire:click="confirmDelete"
                    class="text-sm px-4 py-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                Elimina
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">

        {{-- Stato + categoria + tipo account --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900">Stato e categoria</h2>

                <span class="text-xs px-2 py-1 rounded-full border
                    {{ $isCompany ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                    {{ $form['account_type'] ?? '—' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Status</label>
                    <select
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        wire:model="form.status"
                    >
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="INACTIVE">INACTIVE</option>
                    </select>
                    @error('form.status') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Tipo account</label>
                    <select
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        wire:model.live="form.account_type"
                    >
                        <option value="COMPANY">COMPANY</option>
                        <option value="PRIVATE">PRIVATE</option>
                    </select>
                    @error('form.account_type') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Categoria</label>
                    <select
                        class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                        wire:model="form.category_id"
                    >
                        <option value="">—</option>
                        @foreach($categories as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                    @error('form.category_id') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Dati anagrafici (CONDIZIONATI) --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Dati anagrafici</h2>
            <p class="mt-1 text-sm text-slate-500">
                I campi mostrati dipendono dal tipo account selezionato.
            </p>

            {{-- COMPANY --}}
            @if($isCompany)
                <div class="mt-5 rounded-xl border border-slate-200 p-4 bg-indigo-50/40">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Azienda</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 border border-indigo-200 text-indigo-700">
                            COMPANY
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-sm text-slate-600">Ragione sociale</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.company_name">
                            @error('form.company_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Forma giuridica</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.legal_entity_type">
                            @error('form.legal_entity_type') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">P.IVA</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.vat_number">
                            @error('form.vat_number') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm text-slate-600">Codice fiscale</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.tax_code">
                            @error('form.tax_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- PRIVATE --}}
            @if($isPrivate)
                <div class="mt-5 rounded-xl border border-slate-200 p-4 bg-slate-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Privato</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-200 border border-slate-300 text-slate-700">
                            PRIVATE
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-slate-600">Nome</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.first_name">
                            @error('form.first_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Cognome</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.last_name">
                            @error('form.last_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm text-slate-600">Codice fiscale</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.tax_code">
                            @error('form.tax_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- Fallback se account_type non impostato --}}
            @if(!$isCompany && !$isPrivate)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 text-sm">
                    Seleziona un <strong>Tipo account</strong> per mostrare i campi corretti.
                </div>
            @endif
        </div>

        {{-- Sede legale --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Sede legale</h2>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Paese</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_country">
                    @error('form.legal_country') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Regione</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_region">
                    @error('form.legal_region') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Città</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_city">
                    @error('form.legal_city') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">CAP</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_postal_code">
                    @error('form.legal_postal_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Indirizzo</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_address_line1">
                    @error('form.legal_address_line1') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Sede operativa --}}
        @php
            $sameAsLegal = (bool)($form['operational_same_as_legal'] ?? false);
        @endphp

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Sede operativa</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Se “Uguale alla sede legale” è attivo, i campi operativi vengono copiati e non sono modificabili.
                    </p>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-1">
                    <input type="checkbox"
                           class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                           wire:model.live="form.operational_same_as_legal">
                    Uguale alla sede legale
                </label>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 {{ $sameAsLegal ? 'opacity-50' : '' }}">
                <div>
                    <label class="text-sm text-slate-600">Paese</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_country"
                           @disabled($sameAsLegal)>
                    @error('form.operational_country') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Regione</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_region"
                           @disabled($sameAsLegal)>
                    @error('form.operational_region') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Città</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_city"
                           @disabled($sameAsLegal)>
                    @error('form.operational_city') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">CAP</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_postal_code"
                           @disabled($sameAsLegal)>
                    @error('form.operational_postal_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Indirizzo</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_address_line1"
                           @disabled($sameAsLegal)>
                    @error('form.operational_address_line1') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                Salva
            </button>
        </div>
    </form>

    {{-- Modal delete --}}
    @if($confirmingDelete)
        <div class="fixed inset-0 bg-black/30 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900">Conferma eliminazione</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Vuoi eliminare questo vendor? 
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button"
                            wire:click="cancelDelete"
                            class="text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                        Annulla
                    </button>

                    <button type="button"
                            wire:click="deleteVendor"
                            class="text-sm px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>