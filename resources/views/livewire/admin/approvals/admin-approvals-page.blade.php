<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Approvazioni</h1>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white border border-slate-200 p-4 rounded-xl shadow-sm flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label class="block text-sm font-semibold text-slate-700">Ricerca</label>
            <input type="text" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Cerca vendor o servizio...">
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-semibold text-slate-700">Vendor</label>
            <select wire:model.live="filterVendorId" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tutti i vendor</option>
                @foreach($this->vendorsList as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->company_name ?: ($vendor->first_name . ' ' . $vendor->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-semibold text-slate-700">Tipo</label>
            <select wire:model.live="filterType" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">Tutti</option>
                <option value="vendor">Vendor</option>
                <option value="services">Servizi e Contenuti</option>
                <option value="custom_offering">Offering Custom</option>
            </select>
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-semibold text-slate-700">Stato</label>
            <select wire:model.live="filterStatus" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="pending">Da approvare</option>
                <option value="approved">Approvato</option>
                <option value="rejected">Rifiutato</option>
                <option value="all">Tutti gli stati</option>
            </select>
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-semibold text-slate-700">Categoria</label>
            <select wire:model.live="filterCategory" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tutte le categorie</option>
                @foreach($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if (session()->has('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 relative">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-app-icon name="check-circle" class="w-5 h-5 text-emerald-600" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
    @error('general')
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 relative">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <x-app-icon name="x-circle" class="w-5 h-5 text-red-600" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        {{ $message }}
                    </p>
                </div>
            </div>
        </div>
    @enderror

    <!-- Results List (Bookings Style) -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 divide-y divide-slate-200">
        @forelse($items as $item)
            @php
                $statusClasses = match ($item['status']) {
                    'ACTIVE', 'APPROVED' => 'bg-emerald-100 text-emerald-700',
                    'PENDING', 'PENDING_REVIEW' => 'bg-amber-100 text-amber-700',
                    'REJECTED' => 'bg-slate-200 text-slate-700',
                    default => 'bg-slate-100 text-slate-700'
                };

                $label = match ($item['status']) {
                    'ACTIVE', 'APPROVED' => 'Approvato',
                    'PENDING', 'PENDING_REVIEW' => 'Da approvare',
                    'REJECTED' => 'Rifiutato',
                    default => ucfirst(strtolower($item['status']))
                };

                $icon = match ($item['status']) {
                    'ACTIVE', 'APPROVED' => 'check-circle',
                    'PENDING', 'PENDING_REVIEW' => 'clock',
                    'REJECTED' => 'x-circle',
                    default => 'information-circle'
                };
            @endphp

            <div class="p-6 flex flex-col sm:flex-row items-start justify-between gap-4 hover:bg-slate-50 transition">
                <div class="space-y-4 flex-1 w-full">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ $item['title'] }}
                        </h3>

                        <span class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium {{ $statusClasses }}">
                            <x-app-icon name="{{ $icon }}" class="w-3.5 h-3.5" />
                            <span>{{ $label }}</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-3 text-sm">
                        <div>
                            <div class="text-slate-500">Tipo</div>
                            <div class="font-medium text-slate-900 uppercase">
                                {{ str_replace('_', ' ', $item['type']) }}
                            </div>
                        </div>

                        <div>
                            <div class="text-slate-500">Categoria</div>
                            <div class="font-medium text-slate-900">
                                {{ $item['category'] ?? 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-slate-500">Vendor</div>
                            <div class="font-medium text-slate-900">
                                {{ $item['vendor_name'] }}
                            </div>
                        </div>

                        @if($item['subtitle'])
                        <div>
                            <div class="text-slate-500">Dettaglio</div>
                            <div class="font-medium text-slate-900 line-clamp-1" title="{{ $item['subtitle'] }}">
                                {{ $item['subtitle'] }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="sm:ml-6 flex flex-row gap-2 items-center w-full sm:w-auto mt-4 sm:mt-0 justify-end">
                    @if($item['vendor_account_id'])
                        <a href="{{ route('admin.vendors.edit', $item['vendor_account_id']) }}" target="_blank" 
                            class="inline-flex items-center justify-center p-2 rounded-lg bg-slate-900 text-white transition hover:bg-slate-800" title="Apri scheda vendor">
                            <x-app-icon name="eye" class="w-5 h-5" />
                        </a>

                        @if(in_array($item['type'], ['service', 'custom_offering']) && in_array($item['status'], ['PENDING_REVIEW', 'DRAFT']))
                            <button type="button" wire:click="approveService({{ $item['vendor_account_id'] }}, {{ $item['related_offering_id'] }})" wire:confirm="Sei sicuro di voler approvare?" 
                                class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-600 text-white transition hover:bg-emerald-700" title="Approva">
                                <x-app-icon name="check" class="w-5 h-5" />
                            </button>
                            <button type="button" wire:click="rejectService({{ $item['vendor_account_id'] }}, {{ $item['related_offering_id'] }})" wire:confirm="Sei sicuro di voler rifiutare?" 
                                class="inline-flex items-center justify-center p-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Rifiuta">
                                <x-app-icon name="x-mark" class="w-5 h-5" />
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-500 text-sm">
                Nessun elemento in attesa o corrispondente alla ricerca.
            </div>
        @endforelse

        @if($items->hasPages())
            <div class="p-5 bg-white">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
