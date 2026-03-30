<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">
                Schede servizi
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Anteprima contenuti, modalità di servizio e stato pubblicazione.
            </p>
        </div>

        <span class="text-sm text-slate-500">
            {{ count($activeOfferings) }} attivi
        </span>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 relative">
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

    @if (empty($activeOfferings))
        <div class="mt-4 text-sm text-slate-500">
            Nessun servizio attivo per questo vendor.
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($activeOfferings as $s)
                @php
                    $isPublished = (bool)($s['is_published'] ?? false);
                    $isApproved = (bool)($s['is_approved'] ?? false);
                    $label = $s['status_label'] ?? 'INCOMPLETO';

                    $statusClasses = match ($label) {
                        'PUBBLICATO' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'IN ATTESA DI APPROVAZIONE' => 'bg-amber-100 text-amber-900 border-amber-400 font-bold',
                        'IN BOZZA' => 'bg-amber-50 text-amber-700 border-amber-200',
                        default => 'bg-slate-50 text-slate-700 border-slate-200'
                    };

                    $cover = $s['cover_image_path'] ?? '';
                    $short = $s['short_description'] ?? '';
                    $imagesCount = (int)($s['images_count'] ?? 0);
                    $serviceMode = $s['service_mode'] ?? 'FIXED_LOCATION';
                    $serviceRadiusKm = $s['service_radius_km'] ?? null;

                    $serviceModeLabel = match ($serviceMode) {
                        'MOBILE' => 'Mobile',
                        'FIXED_LOCATION' => 'In sede',
                        default => 'N/A',
                    };
                @endphp

                <div class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                    <div class="h-36 bg-slate-50">
                        @if($cover)
                            <img src="{{ route('media.public', ['path' => $cover]) }}" class="h-36 w-full object-cover" alt="">
                        @else
                            <div class="h-36 w-full flex items-center justify-center text-xs text-slate-400">
                                Nessuna cover
                            </div>
                        @endif
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">
                                {{ $s['name'] }}
                            </h3>

                            <span class="text-[11px] px-2 py-1 rounded-full border {{ $statusClasses }}">
                                {{ $label }}
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="text-[11px] px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700">
                                {{ $serviceModeLabel }}
                            </span>

                            @if($serviceMode === 'MOBILE' && $serviceRadiusKm)
                                <span class="text-[11px] px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700">
                                    {{ $serviceRadiusKm }} km
                                </span>
                            @endif
                        </div>

                        <p class="mt-3 text-sm text-slate-600 line-clamp-3">
                            {{ $short ?: 'Aggiungi una descrizione breve per questo servizio.' }}
                        </p>

                        <div class="mt-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-500">
                                    Foto: {{ $imagesCount + ($cover ? 1 : 0) }}
                                </span>
                            </div>

                            @if (!($s['is_approved'] ?? false))
                                <button type="button" wire:click="approveOfferingProfile({{ $s['id'] }})"
                                    class="w-full inline-flex items-center justify-center gap-2 text-sm px-4 py-2 rounded-lg bg-amber-500 text-white font-semibold hover:bg-amber-600 shadow-sm mt-2 transition">
                                    <x-app-icon name="check-circle" class="w-4 h-4" />
                                    <span>Approva Servizio</span>
                                </button>
                            @endif

                            <button type="button" wire:click="openOfferingDetails({{ $s['id'] }})"
                                class="w-full inline-flex items-center justify-center gap-2 text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 mt-2 transition">
                                <x-app-icon name="eye" class="w-4 h-4" />
                                <span>Apri Dettaglio</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modale Dettagli --}}
    <x-dialog-modal wire:model="isViewingModalOpen" maxWidth="2xl">
        <x-slot name="title">
            Dettagli Servizio: <span class="font-bold text-slate-900">{{ $viewingProfile->title ?? 'N/A' }}</span>
        </x-slot>

        <x-slot name="content">
            @if($viewingProfile)
                <div class="space-y-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        @if($viewingProfile->cover_image_url)
                            <a href="{{ $viewingProfile->cover_image_url }}" target="_blank" class="shrink-0 block">
                                <img src="{{ $viewingProfile->cover_image_url }}" alt="Cover" class="w-full md:w-64 h-auto aspect-video md:aspect-square object-cover rounded-xl border border-slate-200 hover:opacity-90 transition">
                            </a>
                        @endif
                        <div class="w-full">
                            <div class="text-sm font-semibold text-slate-700">Descrizione Breve</div>
                            <p class="text-sm text-slate-600 mt-1">{{ $viewingProfile->short_description ?: 'N/A' }}</p>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-semibold text-slate-700">Descrizione Completa</div>
                        <div class="mt-2 text-sm text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-200 whitespace-pre-wrap">{{ $viewingProfile->description ?: 'Nessuna descrizione lunga fornita.' }}</div>
                    </div>

                    @if($viewingProfile->images && $viewingProfile->images->count() > 0)
                        <div>
                            <div class="text-sm font-semibold text-slate-700">Galleria Foto aggiuntiva ({{ $viewingProfile->images->count() }})</div>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($viewingProfile->images as $photo)
                                    @if($photo->path)
                                        <a href="{{ route('media.public', ['path' => $photo->path]) }}" target="_blank" class="block">
                                            <img src="{{ route('media.public', ['path' => $photo->path]) }}" alt="Photo" class="w-full h-48 object-cover rounded-xl border border-slate-200 hover:opacity-90 transition shadow-sm">
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="py-10 text-center text-slate-500">Recupero informazioni in corso...</div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <button type="button" wire:click="closeOfferingDetails"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition"
                wire:loading.attr="disabled">
                Chiudi
            </button>

            @if($viewingProfile && !$viewingProfile->is_approved)
                <button type="button" wire:click="approveOfferingProfile({{ $viewingProfile->offering_id }}); closeOfferingDetails()"
                    class="ml-2 inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 transition"
                    wire:loading.attr="disabled">
                    <x-app-icon name="check-circle" class="w-4 h-4" />
                    Approva Ora
                </button>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>