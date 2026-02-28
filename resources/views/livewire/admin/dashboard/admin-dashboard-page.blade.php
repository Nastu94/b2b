<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Lista vendor registrati.</p>
    </div>

    <a href="{{ route('admin.vendors.create') }}"
       class="inline-flex items-center text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
        + Crea Vendor
    </a>
</div>

    {{-- Flash --}}
    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- FILTRI SOPRA --}}
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-center">
            <input type="text" class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                placeholder="Cerca (email, ragione sociale, nome, P.IVA, CF...)" wire:model.live="search" />

            <select class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                wire:model.live="status">
                <option value="ALL">Tutti gli status</option>
                <option value="ACTIVE">Solo ACTIVE</option>
                <option value="INACTIVE">Solo INACTIVE</option>
            </select>

            <div class="text-sm text-slate-500 lg:text-right">
                Totale:
                <span class="ml-2 font-semibold text-slate-900">
                    {{ $vendors->total() }}
                </span>
            </div>
        </div>
    </div>

    {{-- LISTA --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden w-full">

        {{-- DESKTOP TABLE --}}
        <div class="hidden md:block w-full overflow-x-scroll">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3">Vendor</th>
                        <th class="text-left px-6 py-3">Email</th>
                        <th class="text-left px-6 py-3">Categoria</th>
                        <th class="text-left px-6 py-3">Status</th>
                        <th class="text-right px-6 py-3">Azioni</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($vendors as $vendor)
                        @php
                            $displayName =
                                $vendor->account_type === 'COMPANY' && !empty($vendor->company_name)
                                    ? $vendor->company_name
                                    : trim(($vendor->first_name ?? '') . ' ' . ($vendor->last_name ?? ''));

                            if ($displayName === '') {
                                $displayName = 'Vendor #' . $vendor->id;
                            }

                            $statusBadge =
                                $vendor->status === 'ACTIVE'
                                    ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                                    : 'bg-amber-50 border-amber-200 text-amber-800';
                        @endphp

                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $displayName }}
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                {{ $vendor->user?->email ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                {{ $vendor->category?->name ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-xs px-2 py-1 rounded-full border {{ $statusBadge }}">
                                    {{ $vendor->status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.vendors.edit', $vendor) }}"
                                    class="text-sm px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                                    Modifica
                                </a>

                                <button type="button" wire:click="confirmDelete({{ $vendor->id }})"
                                    class="ml-2 text-sm px-3 py-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                                    Elimina
                                </button>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                Nessun vendor trovato.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MOBILE CARDS --}}
        <div class="md:hidden p-4 space-y-4">
            @forelse($vendors as $vendor)
                @php
                    $displayName =
                        $vendor->account_type === 'COMPANY' && !empty($vendor->company_name)
                            ? $vendor->company_name
                            : trim(($vendor->first_name ?? '') . ' ' . ($vendor->last_name ?? ''));

                    if ($displayName === '') {
                        $displayName = 'Vendor #' . $vendor->id;
                    }

                    $statusBadge =
                        $vendor->status === 'ACTIVE'
                            ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                            : 'bg-amber-50 border-amber-200 text-amber-800';
                @endphp

                <div class="w-full bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">

                    <div class="flex items-center justify-between">
                        <div class="font-semibold text-slate-900">
                            {{ $displayName }}
                        </div>

                        <span class="text-xs px-2 py-1 rounded-full border {{ $statusBadge }}">
                            {{ $vendor->status }}
                        </span>
                    </div>

                    <div class="text-sm text-slate-600">
                        {{ $vendor->user?->email ?? 'N/A' }}
                    </div>

                    <div class="text-sm text-slate-500">
                        {{ $vendor->category?->name ?? 'N/A' }}
                    </div>

                    <div class="flex gap-2 pt-2">
                        <a href="{{ route('admin.vendors.edit', $vendor) }}"
                            class="inline-flex w-full justify-center items-center text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                            Apri anagrafica
                        </a>

                        <button type="button" wire:click="confirmDelete({{ $vendor->id }})"
                            class="text-sm px-4 py-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                            Elimina
                        </button>
                    </div>
                </div>

            @empty
                <div class="py-10 text-center text-slate-500">
                    Nessun vendor trovato.
                </div>
            @endforelse
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $vendors->links() }}
        </div>
    </div>

    {{-- Modal delete --}}
    @if ($confirmingDelete)
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
                        class="text-sm px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                        Elimina
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
