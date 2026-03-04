<div class="space-y-6">
    {{-- Header pagina + CTA --}}
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold text-slate-900">Profilo</h1>
            <p class="mt-1 text-sm text-slate-500">
                Visualizza e modifica i dati del tuo profilo vendor.
            </p>
        </div>

        <div class="flex gap-2 shrink-0">
            @can('update', $vendorAccount)
                @if (!($editing ?? false))
                    <button type="button" wire:click="enableEditing"
                            class="px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                        Modifica
                    </button>
                @else
                    <button type="button" wire:click="cancelEditing"
                            class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition">
                        Annulla
                    </button>
                @endif
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @php
        $isCompany = ($form['account_type'] ?? '') === 'COMPANY';
        $isPrivate = ($form['account_type'] ?? '') === 'PRIVATE';

        $canUpdate = auth()->user()?->can('update', $vendorAccount) ?? false;
        $canEditNow = $canUpdate && ($editing ?? false);
    @endphp

    <form wire:submit.prevent="save" class="space-y-6">

        {{-- Stato + categoria + tipo account --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900">Stato e categoria</h2>

                <span
                    class="text-xs px-2 py-1 rounded-full border
                    {{ $isCompany ? 'bg-slate-50 border-slate-200 text-slate-700' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                    {{ $form['account_type'] ?? '—' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Status</label>
                    <select class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            wire:model="form.status" @disabled(!$canEditNow)>
                        <option value="ACTIVE">ACTIVE</option>
                        <option value="INACTIVE">INACTIVE</option>
                    </select>
                    @error('form.status') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Tipo account</label>
                    <select class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            wire:model.live="form.account_type" @disabled(!$canEditNow)>
                        <option value="COMPANY">COMPANY</option>
                        <option value="PRIVATE">PRIVATE</option>
                    </select>
                    @error('form.account_type') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Categoria</label>
                    <select class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                            wire:model="form.category_id" @disabled(!$canEditNow)>
                        <option value="">—</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                    @error('form.category_id') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Dati anagrafici --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900">Dati anagrafici</h2>
            <p class="mt-1 text-sm text-slate-500">
                I campi mostrati dipendono dal tipo account selezionato.
            </p>

            {{-- COMPANY --}}
            @if ($isCompany)
                <div class="mt-5 rounded-xl border border-slate-200 p-4 bg-slate-50/40">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Azienda</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-700">
                            COMPANY
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-sm text-slate-600">Ragione sociale</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.company_name" @disabled(!$canEditNow)>
                            @error('form.company_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Forma giuridica</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.legal_entity_type" @disabled(!$canEditNow)>
                            @error('form.legal_entity_type') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">P.IVA</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.vat_number" @disabled(!$canEditNow)>
                            @error('form.vat_number') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm text-slate-600">Codice fiscale</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.tax_code" @disabled(!$canEditNow)>
                            @error('form.tax_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- PRIVATE --}}
            @if ($isPrivate)
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
                                   wire:model="form.first_name" @disabled(!$canEditNow)>
                            @error('form.first_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Cognome</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.last_name" @disabled(!$canEditNow)>
                            @error('form.last_name') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm text-slate-600">Codice fiscale</label>
                            <input type="text"
                                   class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                                   wire:model="form.tax_code" @disabled(!$canEditNow)>
                            @error('form.tax_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            @if (!$isCompany && !$isPrivate)
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
                           wire:model="form.legal_country" @disabled(!$canEditNow)>
                    @error('form.legal_country') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Regione</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_region" @disabled(!$canEditNow)>
                    @error('form.legal_region') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Città</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_city" @disabled(!$canEditNow)>
                    @error('form.legal_city') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">CAP</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_postal_code" @disabled(!$canEditNow)>
                    @error('form.legal_postal_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Indirizzo</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.legal_address_line1" @disabled(!$canEditNow)>
                    @error('form.legal_address_line1') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Sede operativa --}}
        @php $sameAsLegal = (bool) ($form['operational_same_as_legal'] ?? false); @endphp

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
                           wire:model.live="form.operational_same_as_legal" @disabled(!$canEditNow)>
                    Uguale alla sede legale
                </label>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 {{ $sameAsLegal ? 'opacity-50' : '' }}">
                <div>
                    <label class="text-sm text-slate-600">Paese</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_country" @disabled($sameAsLegal || !$canEditNow)>
                    @error('form.operational_country') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Regione</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_region" @disabled($sameAsLegal || !$canEditNow)>
                    @error('form.operational_region') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">Città</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_city" @disabled($sameAsLegal || !$canEditNow)>
                    @error('form.operational_city') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="text-sm text-slate-600">CAP</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_postal_code" @disabled($sameAsLegal || !$canEditNow)>
                    @error('form.operational_postal_code') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Indirizzo</label>
                    <input type="text"
                           class="mt-1 w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                           wire:model="form.operational_address_line1" @disabled($sameAsLegal || !$canEditNow)>
                    @error('form.operational_address_line1') <div class="text-sm text-rose-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            @can('update', $vendorAccount)
                @if ($editing ?? false)
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                        Salva
                    </button>
                @endif
            @endcan

            @cannot('update', $vendorAccount)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 text-sm">
                    Non hai i permessi per modificare questo vendor.
                </div>
            @endcannot
        </div>
    </form>
</div>