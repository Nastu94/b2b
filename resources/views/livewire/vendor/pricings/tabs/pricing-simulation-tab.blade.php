<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Simulazione</h2>
        <p class="mt-1 text-sm text-slate-600">
            Inserisci un contesto di prova per verificare quali regole potrebbero entrare in gioco per questo listino.
        </p>
    </div>

    @if ($this->pricing === null)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Prima di usare la simulazione devi creare il listino base del servizio.
        </div>
    @else
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <form wire:submit="simulate" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="simulation_event_date" class="mb-2 block text-sm font-medium text-slate-700">
                            Data evento
                        </label>

                        <input id="simulation_event_date" type="date" wire:model.defer="simulation.event_date"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300">

                        <p class="mt-2 text-xs text-slate-500">
                            Serve per simulare eventuali regole con intervalli di date.
                        </p>

                        @error('simulation.event_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="simulation_weekday" class="mb-2 block text-sm font-medium text-slate-700">
                            Giorno della settimana
                        </label>

                        <select id="simulation_weekday" wire:model.defer="simulation.weekday"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300">
                            <option value="">-- Automatico / non specificato --</option>
                            <option value="1">Lunedì</option>
                            <option value="2">Martedì</option>
                            <option value="3">Mercoledì</option>
                            <option value="4">Giovedì</option>
                            <option value="5">Venerdì</option>
                            <option value="6">Sabato</option>
                            <option value="7">Domenica</option>
                        </select>

                        <p class="mt-2 text-xs text-slate-500">
                            Se lasci vuoto e imposti una data evento, il giorno verrà ricavato automaticamente.
                        </p>

                        @error('simulation.weekday')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="simulation_distance_km" class="mb-2 block text-sm font-medium text-slate-700">
                            Distanza (km)
                        </label>

                        <input id="simulation_distance_km" type="number" step="0.01" min="0"
                            wire:model.defer="simulation.distance_km"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300">

                        <p class="mt-2 text-xs text-slate-500">
                            Utile per testare regole collegate al raggio o a fasce di distanza.
                        </p>

                        @error('simulation.distance_km')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="simulation_lead_days" class="mb-2 block text-sm font-medium text-slate-700">
                            Anticipo prenotazione (giorni)
                        </label>

                        <input id="simulation_lead_days" type="number" min="0"
                            wire:model.defer="simulation.lead_days"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300">

                        <p class="mt-2 text-xs text-slate-500">
                            Indica quanti giorni prima dell'evento viene effettuata la prenotazione.
                        </p>

                        @error('simulation.lead_days')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="simulation_guests" class="mb-2 block text-sm font-medium text-slate-700">
                            Numero ospiti
                        </label>

                        <input id="simulation_guests" type="number" min="1" wire:model.defer="simulation.guests"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300">

                        <p class="mt-2 text-xs text-slate-500">
                            Serve per verificare eventuali regole che dipendono dalla capienza o dal numero di
                            partecipanti.
                        </p>

                        @error('simulation.guests')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" wire:click="resetSimulation"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <x-app-icon name="arrow-path" class="w-4 h-4" />
                        <span>Reset</span>
                    </button>

                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                        <x-app-icon name="play" class="w-4 h-4" />
                        <span>Simula</span>
                    </button>
                </div>
            </form>
        </div>

        @if ($hasSimulated)
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Contesto simulato</h3>

                    <dl class="mt-4 space-y-4 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Data evento</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ filled($simulation['event_date']) ? \Carbon\Carbon::parse($simulation['event_date'])->format('d/m/Y') : 'Non specificata' }}
                            </dd>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Giorno</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ $this->weekdayLabel(filled($simulation['weekday']) ? (int) $simulation['weekday'] : null) }}
                            </dd>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Distanza</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ filled($simulation['distance_km']) ? number_format((float) $simulation['distance_km'], 2, ',', '.') . ' km' : 'Non specificata' }}
                            </dd>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Anticipo</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ filled($simulation['lead_days']) ? (int) $simulation['lead_days'] . ' giorni' : 'Non specificato' }}
                            </dd>
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Ospiti</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ filled($simulation['guests']) ? (int) $simulation['guests'] : 'Non specificati' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">Esito simulazione</h3>

                    @if ($simulationResult !== null)
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    Prezzo base
                                </div>

                                <div class="mt-2 text-lg font-semibold text-slate-900">
                                    {{ $this->formattedBasePrice() }}
                                </div>
                            </div>

                            <div
                                class="rounded-lg border px-4 py-3 {{ $this->hasResolvedPriceDifference() ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                                <div
                                    class="text-xs font-medium uppercase tracking-wide {{ $this->hasResolvedPriceDifference() ? 'text-emerald-700' : 'text-slate-500' }}">
                                    Prezzo finale simulato
                                </div>

                                <div
                                    class="mt-2 text-lg font-semibold {{ $this->hasResolvedPriceDifference() ? 'text-emerald-700' : 'text-slate-900' }}">
                                    {{ $this->formattedResolvedPrice() }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($this->simulationBreakdown() !== [])
                        <div class="mt-4 rounded-lg border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 px-4 py-3">
                                <h4 class="text-sm font-semibold text-slate-900">
                                    Dettaglio calcolo
                                </h4>
                            </div>

                            <div class="divide-y divide-slate-200">
                                @foreach ($this->simulationBreakdown() as $step)
                                    <div class="px-4 py-3 text-sm">
                                        @if (($step['type'] ?? null) === 'base_price')
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-medium text-slate-900">
                                                        {{ $step['label'] ?? 'Prezzo base' }}
                                                    </div>
                                                </div>

                                                <div class="text-right font-semibold text-slate-900">
                                                    {{ number_format((float) ($step['amount'] ?? 0), 2, ',', '.') }}
                                                    {{ $this->pricing?->currencyCode() ?? 'EUR' }}
                                                </div>
                                            </div>
                                        @elseif (($step['type'] ?? null) === 'override')
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-medium text-slate-900">
                                                        {{ $step['rule_name'] ?? 'Override' }}
                                                    </div>

                                                    <div class="mt-1 text-xs text-slate-500">
                                                        {{ $step['label'] ?? 'Override prezzo' }} · Priorità
                                                        {{ $step['priority'] ?? '-' }}
                                                    </div>
                                                </div>

                                                <div class="text-right">
                                                    <div class="font-semibold text-emerald-700">
                                                        {{ number_format((float) ($step['amount'] ?? 0), 2, ',', '.') }}
                                                        {{ $this->pricing?->currencyCode() ?? 'EUR' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-medium text-slate-900">
                                                        {{ $step['rule_name'] ?? 'Regola' }}
                                                    </div>

                                                    <div class="mt-1 text-xs text-slate-500">
                                                        {{ $step['label'] ?? 'Regola' }}
                                                        · {{ $this->formatAdjustmentValue($step) }}
                                                        · Priorità {{ $step['priority'] ?? '-' }}
                                                    </div>
                                                </div>

                                                <div class="text-right">
                                                    <div
                                                        class="font-semibold {{ ((float) ($step['delta'] ?? 0)) >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                                        {{ $this->formatDelta($step['delta'] ?? 0) }}
                                                    </div>

                                                    <div class="mt-1 text-xs text-slate-500">
                                                        Totale:
                                                        {{ number_format((float) ($step['result_price'] ?? 0), 2, ',', '.') }}
                                                        {{ $this->pricing?->currencyCode() ?? 'EUR' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($this->ignoredRules() !== [])
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50">
                            <div class="border-b border-amber-200 px-4 py-3">
                                <h4 class="text-sm font-semibold text-amber-900">
                                    Regole compatibili ma ignorate
                                </h4>
                            </div>

                            <div class="divide-y divide-amber-200">
                                @foreach ($this->ignoredRules() as $ignoredRule)
                                    <div class="px-4 py-3 text-sm">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="font-medium text-amber-900">
                                                    {{ $ignoredRule['rule_name'] ?? 'Regola' }}
                                                </div>

                                                <div class="mt-1 text-xs text-amber-700">
                                                    Priorità {{ $ignoredRule['priority'] ?? '-' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2 text-sm text-amber-800">
                                            {{ $ignoredRule['reason'] ?? 'Regola compatibile ma non applicata.' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($this->matchingRules->isEmpty())
                        <div
                            class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            Nessuna regola risulta compatibile con i dati inseriti.
                        </div>
                    @else
                        <div class="mt-4 space-y-3">
                            <div class="text-sm text-slate-600">
                                Regole potenzialmente compatibili:
                                <span class="font-semibold text-slate-900">{{ $this->matchingRules->count() }}</span>
                            </div>

                            <div class="space-y-3">
                                @foreach ($this->matchingRules as $rule)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="font-medium text-slate-900">
                                                {{ $rule->name }}
                                            </div>

                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                Priorità {{ $rule->priority }}
                                            </span>

                                            @if ($rule->isExclusive())
                                                <span
                                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                                    Esclusiva
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-2 text-sm text-slate-600">
                                            <span class="font-medium text-slate-700">Effetto:</span>
                                            {{ $this->ruleValueLabel($rule) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($this->simulationNotes() !== [])
                        <div class="mt-4 space-y-3">
                            @foreach ($this->simulationNotes() as $note)
                                <div
                                    class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                                    {{ $note }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
