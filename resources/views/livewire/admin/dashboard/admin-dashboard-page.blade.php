<div class="space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Admin Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Lista vendor registrati.</p>
        </div>

        <a href="{{ route('admin.vendors.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
            <x-app-icon name="plus" class="w-4 h-4" />
            <span>Crea Vendor</span>
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-center">

            <input type="text" class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                placeholder="Cerca (email, ragione sociale, nome, P.IVA, CF...)" wire:model.live="search" />

            <select class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                wire:model.live="status">
                <option value="ALL">Tutti gli status</option>
                <option value="PENDING">Da Approvare (PENDING)</option>
                <option value="ACTIVE">Solo Attivi</option>
                <option value="INACTIVE">Solo Inattivi</option>
            </select>

            <select class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                wire:model.live="categoryId">
                <option value="">Tutte le categorie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                @endforeach
            </select>

            <select class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                wire:model.live="serviceMode">
                <option value="">Tutte le modalità</option>
                <option value="FIXED_LOCATION">Solo in sede</option>
                <option value="MOBILE">Solo mobile</option>
            </select>

            <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                <x-app-icon name="arrow-path" class="w-4 h-4" />
                <span>Azzera filtri</span>
            </button>

            <div class="text-sm text-slate-500 lg:text-right">
                Totale:
                <span class="ml-2 font-semibold text-slate-900">
                    {{ $vendors->total() }}
                </span>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden w-full"
         x-data="{
             syncTop() { this.$refs.bottom.scrollLeft = this.$refs.top.scrollLeft; },
             syncBottom() { this.$refs.top.scrollLeft = this.$refs.bottom.scrollLeft; },
             init() {
                 const observer = new ResizeObserver(() => {
                     if(this.$refs.table) {
                         this.$refs.dummy.style.width = this.$refs.table.scrollWidth + 'px';
                     }
                 });
                 // Osserviamo la tabella per ricalcolare la larghezza dello scroll finto
                 if(this.$refs.table) observer.observe(this.$refs.table);
             }
         }">

        <!-- Scrollbar superiore (visibile solo su Desktop/Tablet dove c'è la tabella) -->
        <style>
            .thin-scrollbar-top {
                scrollbar-width: thin;
                scrollbar-color: #94a3b8 transparent;
            }
            .thin-scrollbar-top::-webkit-scrollbar { height: 8px; }
            .thin-scrollbar-top::-webkit-scrollbar-track { background: transparent; }
            .thin-scrollbar-top::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 9999px; }
        </style>
        <div class="hidden md:block overflow-x-auto overflow-y-hidden border-b border-slate-100 thin-scrollbar-top" x-ref="top" @scroll="syncTop" style="height: 10px;">
            <div x-ref="dummy" style="height: 1px;"></div>
        </div>

        <div class="table-wrap table-wrap-fade overflow-x-auto" x-ref="bottom" @scroll="syncBottom">
            <table class="pl-table pl-table-sticky-first" x-ref="table">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3">Vendor</th>
                        <th class="text-left px-6 py-3">Email</th>
                        <th class="text-left px-6 py-3">Categoria</th>
                        <th class="text-left px-6 py-3">Modalità</th>
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

                            $statusBadge = match ($vendor->status) {
                                'ACTIVE' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                                'PENDING' => 'bg-amber-100 border-amber-400 text-amber-900 font-bold animate-pulse',
                                default => 'bg-slate-50 border-slate-200 text-slate-800'
                            };

                            $serviceModes = $vendor->vendorOfferingProfiles
                                ->pluck('service_mode')
                                ->filter()
                                ->unique()
                                ->values();

                            if ($serviceModes->count() === 0) {
                                $serviceModeLabel = 'N/A';
                            } elseif ($serviceModes->count() > 1) {
                                $serviceModeLabel = 'Mista';
                            } else {
                                $serviceModeLabel = match ($serviceModes->first()) {
                                    'MOBILE' => 'Mobile',
                                    'FIXED_LOCATION' => 'In sede',
                                    default => 'N/A',
                                };
                            }
                        @endphp

                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="shrink-0">
                                        @if ($vendor->profile_image_path)
                                            <img src="{{ route('media.public', ['path' => $vendor->profile_image_path]) }}" alt="Logo" class="w-10 h-10 rounded-full object-cover border border-slate-200">
                                        @else
                                            <div class="w-10 h-10 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center text-slate-400">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="font-semibold text-slate-900">
                                        {{ $displayName }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                {{ $vendor->user?->email ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                {{ $vendor->category?->name ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                {{ $serviceModeLabel }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-xs px-2 py-1 rounded-full border {{ $statusBadge }}">
                                    {{ $vendor->status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.vendors.edit', $vendor) }}"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    <x-app-icon name="eye" class="w-4 h-4" />
                                    <span>Apri</span>
                                </a>

                                <button type="button" wire:click="confirmDelete({{ $vendor->id }})"
                                    class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                                    <x-app-icon name="trash" class="w-4 h-4" />
                                    <span>Elimina</span>
                                </button>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                Nessun vendor trovato.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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

                    $statusBadge = match ($vendor->status) {
                        'ACTIVE' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                        'PENDING' => 'bg-amber-100 border-amber-400 text-amber-900 font-bold animate-pulse',
                        default => 'bg-slate-50 border-slate-200 text-slate-800'
                    };

                    $serviceModes = $vendor->vendorOfferingProfiles
                        ->pluck('service_mode')
                        ->filter()
                        ->unique()
                        ->values();

                    if ($serviceModes->count() === 0) {
                        $serviceModeLabel = 'N/A';
                    } elseif ($serviceModes->count() > 1) {
                        $serviceModeLabel = 'Mista';
                    } else {
                        $serviceModeLabel = match ($serviceModes->first()) {
                            'MOBILE' => 'Mobile',
                            'FIXED_LOCATION' => 'In sede',
                            default => 'N/A',
                        };
                    }
                @endphp

                <div class="w-full bg-white border border-slate-200 rounded-xl p-4 shadow-sm space-y-3">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                @if ($vendor->profile_image_path)
                                    <img src="{{ route('media.public', ['path' => $vendor->profile_image_path]) }}" alt="Logo" class="w-10 h-10 rounded-full object-cover border border-slate-200">
                                @else
                                    <div class="w-10 h-10 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center text-slate-400">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="font-semibold text-slate-900">
                                {{ $displayName }}
                            </div>
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

                    <div class="text-sm text-slate-500">
                        {{ $serviceModeLabel }}
                    </div>

                    <div class="flex gap-2 pt-2">
                        <a href="{{ route('admin.vendors.edit', $vendor) }}"
                            class="inline-flex w-full justify-center items-center gap-2 text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                            <x-app-icon name="eye" class="w-4 h-4" />
                            <span>Apri anagrafica</span>
                        </a>

                        <button type="button" wire:click="confirmDelete({{ $vendor->id }})"
                            class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                            <x-app-icon name="trash" class="w-4 h-4" />
                            <span>Elimina</span>
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

    @if ($confirmingDelete)
        <div class="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-[999]">
            <div class="w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-slate-900">Conferma eliminazione</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Vuoi eliminare questo vendor?
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete"
                        class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                        <x-app-icon name="arrow-left" class="w-4 h-4" />
                        <span>Annulla</span>
                    </button>

                    <button type="button" wire:click="deleteVendor"
                        class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                        <x-app-icon name="trash" class="w-4 h-4" />
                        <span>Elimina</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
