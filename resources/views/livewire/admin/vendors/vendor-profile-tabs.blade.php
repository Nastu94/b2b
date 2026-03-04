<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold text-slate-900"> {{ ucfirst($activeTab) }}</h1>

            @php
                $displayName = $vendorAccount->company_name
                    ?: trim(($vendorAccount->first_name ?? '') . ' ' . ($vendorAccount->last_name ?? ''))
                    ?: 'Vendor';

                $canUpdate = auth()->user()?->can('update', $vendorAccount) ?? false;
                $canDelete = auth()->user()?->can('delete', $vendorAccount) ?? false;
            @endphp

            <p class="mt-1 text-sm text-slate-500">
                <span class="font-medium text-slate-900">{{ $displayName }}</span>
                @if ($vendorAccount->user?->email)
                    • Email: <span class="font-medium text-slate-900">{{ $vendorAccount->user->email }}</span>
                @endif
            </p>
        </div>

        <div class="flex gap-2 shrink-0">
            <a href="{{ route('admin.dashboard') }}"
               class="text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                ← Torna alla lista
            </a>

            @if ($canUpdate)
                @if (!($editing ?? false))
                    <button type="button" wire:click="enableEditing"
                            class="text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">
                        Modifica
                    </button>
                @else
                    <button type="button" wire:click="cancelEditing"
                            class="text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                        Annulla
                    </button>
                @endif
            @endif

            @if ($canDelete)
                <button type="button" wire:click="confirmDelete"
                        class="text-sm px-4 py-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                    Elimina
                </button>
            @endif
        </div>
    </div>

    {{-- Tabs --}}
    <div class="w-full">
        <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100 p-1">
            <button type="button"
                    wire:click="setTab('anagrafica')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition
                    {{ ($activeTab ?? 'anagrafica') === 'anagrafica'
                        ? 'bg-white text-slate-900 shadow-sm'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                Anagrafica
            </button>

            <button type="button"
                    wire:click="setTab('servizi')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition
                    {{ ($activeTab ?? 'anagrafica') === 'servizi'
                        ? 'bg-white text-slate-900 shadow-sm'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-white/70' }}">
                Servizi
            </button>
        </div>
    </div>

    {{-- Content --}}
    @if (($activeTab ?? 'anagrafica') === 'anagrafica')
        <livewire:admin.vendors.tabs.vendor-anagrafica-tab
            :vendor-account-id="$vendorAccount->id"
            :editing="$editing"
            wire:key="admin-vendor-anagrafica-{{ $vendorAccount->id }}"
        />
    @endif

    @if (($activeTab ?? 'anagrafica') === 'servizi')
        <livewire:admin.vendors.tabs.vendor-servizi-tab
            :vendor-account-id="$vendorAccount->id"
            wire:key="admin-vendor-servizi-{{ $vendorAccount->id }}"
        />
    @endif

    {{-- Modal delete --}}
    @if ($confirmingDelete && ($canDelete ?? false))
        <div class="fixed inset-0 bg-black/30 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900">Conferma eliminazione</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Vuoi eliminare questo vendor?
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete"
                            class="text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                        Annulla
                    </button>

                    <button type="button" wire:click="deleteVendor"
                            class="text-sm px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700"
                            @disabled(!($canDelete ?? false))>
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>