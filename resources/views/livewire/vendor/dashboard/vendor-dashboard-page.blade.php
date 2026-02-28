<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Vendor Dashboard
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Panoramica del tuo profilo e dei servizi attivi.
            </p>
        </div>

        <a href="{{ route('vendor.offerings') }}"
           class="text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
            Gestisci servizi
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs uppercase text-slate-500 tracking-wide">
                Stato account
            </div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                {{ $status }}
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs uppercase text-slate-500 tracking-wide">
                Categoria
            </div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                {{ $categoryName }}
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs uppercase text-slate-500 tracking-wide">
                Servizi attivi
            </div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                {{ count($activeOfferings) }}
            </div>
        </div>

    </div>

    {{-- Servizi --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">
                Servizi attivi
            </h2>
            <span class="text-sm text-slate-500">
                {{ count($activeOfferings) }} totali
            </span>
        </div>

        @if (empty($activeOfferings))
            <div class="mt-4 text-sm text-slate-500">
                Nessun servizio selezionato.
                <a href="{{ route('vendor.offerings') }}" class="underline text-slate-900">
                    Seleziona servizi
                </a>
            </div>
        @else
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($activeOfferings as $offering)
                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm border border-slate-200">
                        {{ $offering['name'] }}
                    </span>
                @endforeach
            </div>
        @endif

    </div>

    {{-- Cards contenuti servizi --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Schede servizi
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Aggiungi foto e descrizioni per rendere i tuoi servizi più completi.
                </p>
            </div>
        </div>

        @if (empty($activeOfferings))
            <div class="mt-4 text-sm text-slate-500">
                Nessun servizio attivo. Vai su
                <a href="{{ route('vendor.offerings') }}" class="underline text-slate-900">
                    Gestisci servizi
                </a>
                per selezionarne almeno uno.
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
                    @endphp

                    <div class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                        {{-- Cover --}}
                        <div class="h-36 bg-slate-50">
                            @if($cover)
                                <img src="{{ asset('storage/'.$cover) }}" class="h-36 w-full object-cover" alt="">
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

                            <p class="mt-2 text-sm text-slate-600 line-clamp-3">
                                {{ $short ?: 'Aggiungi una descrizione breve per questo servizio.' }}
                            </p>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs text-slate-500">
                                    Foto: {{ $imagesCount + ($cover ? 1 : 0) }}
                                </span>

                                <a href="{{ route('vendor.offerings') }}#offering-{{ $s['id'] }}"
                                   class="text-xs px-3 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                                    Completa scheda
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>