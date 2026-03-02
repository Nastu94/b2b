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
        </div>

        @if (empty($activeOfferings))
            <div class="mt-4 text-sm text-slate-500">
                Nessun servizio selezionato.
                <a href="{{ route('vendor.offerings') }}" class="underline text-slate-900">
                    Seleziona servizi
                </a>
            </div>
        @else
            @php

                $publishedOfferings = array_values(
                    array_filter($activeOfferings, fn($o) => (bool) ($o['is_published'] ?? false)),
                );
                $draftOfferings = array_values(
                    array_filter($activeOfferings, fn($o) => !(bool) ($o['is_published'] ?? false)),
                );

            @endphp

            {{-- Nuove liste: Pubblicati / In bozza --}}
            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Pubblicati --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Servizi pubblicati</h3>
                        <span class="text-xs text-slate-500">{{ count($publishedOfferings) }}</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse($publishedOfferings as $o)
                            <span
                                class="px-3 py-1 rounded-full text-sm bg-emerald-50 border border-emerald-200 text-emerald-800">
                                {{ $o['name'] }}
                            </span>
                        @empty
                            <p class="text-sm text-slate-500">Nessun servizio pubblicato.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Bozza --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Servizi in bozza</h3>
                        <span class="text-xs text-slate-500">{{ count($draftOfferings) }}</span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse($draftOfferings as $o)
                            <span
                                class="px-3 py-1 rounded-full text-sm bg-amber-50 border border-amber-200 text-amber-800">
                                {{ $o['name'] }}
                            </span>
                        @empty
                            <p class="text-sm text-slate-500">Nessuna bozza.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        @endif

    </div>

    {{-- sezioni da decidere - disponibilita, giorni di chiusura, preavviso --}}


</div>
