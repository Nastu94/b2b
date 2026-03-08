<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Riepilogo</h2>
        <p class="mt-1 text-sm text-slate-600">
            Controlla rapidamente lo stato del listino e verifica le principali impostazioni configurate.
        </p>
    </div>

    @if ($this->pricing === null)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Prima di visualizzare il riepilogo devi creare il listino base del servizio.
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Stato listino
                </div>

                <div class="mt-2 text-lg font-semibold text-slate-900">
                    {{ $this->pricingStatusLabel() }}
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Tipo prezzo
                </div>

                <div class="mt-2 text-lg font-semibold text-slate-900">
                    {{ $this->priceTypeLabel() }}
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Prezzo base
                </div>

                <div class="mt-2 text-lg font-semibold text-slate-900">
                    {{ $this->formattedBasePrice() }}
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Gestione distanza
                </div>

                <div class="mt-2 text-lg font-semibold text-slate-900">
                    {{ $this->distanceModeLabel() }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Configurazione base</h3>

                <dl class="mt-4 space-y-4 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Raggio di servizio</dt>
                        <dd class="text-right font-medium text-slate-800">
                            {{ $this->formattedServiceRadius() }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Note interne</dt>
                        <dd class="max-w-sm text-right font-medium text-slate-800">
                            {{ filled($this->pricing->notes_internal) ? $this->pricing->notes_internal : 'Nessuna nota' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Panoramica regole</h3>

                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-slate-500">Totali</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->rules->count() }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Attive</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->activeRulesCount() }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Esclusive</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->exclusiveRulesCount() }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Con vincolo date</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->dateRulesCount() }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Con vincolo giorni</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->weekdayRulesCount() }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Con vincolo distanza</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->distanceRulesCount() }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Con vincolo anticipo</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->leadDaysRulesCount() }}</dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">Con vincolo ospiti</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->guestsRulesCount() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Controlli utili</h3>

            @if ($this->warnings === [])
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    La configurazione non presenta criticità evidenti.
                </div>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($this->warnings as $warning)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ $warning }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>