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

    @if (empty($activeOfferings))
        <div class="mt-4 text-sm text-slate-500">
            Nessun servizio attivo per questo vendor.
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($activeOfferings as $s)
                @php
                    $isPublished = (bool)($s['is_published'] ?? false);
                    $label = $s['status_label'] ?? 'INCOMPLETO';

                    $statusClasses = $isPublished
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : (($label === 'IN BOZZA')
                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : 'bg-slate-50 text-slate-700 border-slate-200');

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

                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xs text-slate-500">
                                Foto: {{ $imagesCount + ($cover ? 1 : 0) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>