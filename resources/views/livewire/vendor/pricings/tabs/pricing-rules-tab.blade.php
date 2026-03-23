<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Regole</h2>
        <p class="mt-1 text-sm text-slate-600">
            Aggiungi maggiorazioni, sconti o override per gestire condizioni specifiche del listino.
        </p>
    </div>

    @if (session()->has('pricing_rules_success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('pricing_rules_success') }}
        </div>
    @endif

    @if ($this->pricing === null)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Prima di creare regole devi salvare il listino base del servizio.
        </div>
    @else
        <div class="flex items-center justify-between gap-4">
            <div class="text-sm text-slate-500">
                Regole configurate: <span class="font-medium text-slate-700">{{ $this->rules->count() }}</span>
            </div>

            <button
                type="button"
                wire:click="startCreate"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <x-app-icon name="plus" class="w-4 h-4" />
                <span>Nuova regola</span>
            </button>
        </div>

        @if ($showRuleForm)
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">
                            {{ $editingRuleId ? 'Modifica regola' : 'Nuova regola' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Configura tipo, importo e condizioni di applicazione.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="text-sm font-medium text-slate-500 transition hover:text-slate-700"
                    >
                        Chiudi
                    </button>
                </div>

                <form wire:submit="saveRule" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="rule_name" class="mb-2 block text-sm font-medium text-slate-700">
                                Nome regola
                            </label>

                            <input
                                id="rule_name"
                                type="text"
                                wire:model.defer="form.name"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Inserisci un nome chiaro per riconoscere rapidamente la regola in elenco.
                            </p>

                            @error('form.name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="priority" class="mb-2 block text-sm font-medium text-slate-700">
                                Priorità
                            </label>

                            <input
                                id="priority"
                                type="number"
                                min="1"
                                wire:model.defer="form.priority"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Le regole con numero più basso vengono considerate prima. Ad esempio 10 ha priorità maggiore di 20.
                            </p>

                            @error('form.priority')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="rule_type" class="mb-2 block text-sm font-medium text-slate-700">
                                Tipo regola
                            </label>

                            <select
                                id="rule_type"
                                wire:model.live="form.rule_type"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >
                                @foreach ($this->options['ruleTypes'] as $option)
                                    <option value="{{ $option['value'] }}">
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-2 text-xs text-slate-500">
                                Scegli se la regola deve aumentare il prezzo, applicare uno sconto oppure sostituirlo con un importo fisso.
                            </p>

                            @error('form.rule_type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($this->usesAdjustmentFields())
                            <div>
                                <label for="adjustment_type" class="mb-2 block text-sm font-medium text-slate-700">
                                    Tipo valore
                                </label>

                                <select
                                    id="adjustment_type"
                                    wire:model.defer="form.adjustment_type"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                                >
                                    <option value="">-- Seleziona --</option>

                                    @foreach ($this->options['adjustmentTypes'] as $option)
                                        <option value="{{ $option['value'] }}">
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-2 text-xs text-slate-500">
                                    Usa importo fisso o percentuale quando la regola modifica il prezzo con una maggiorazione o uno sconto.
                                </p>

                                @error('form.adjustment_type')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="adjustment_value" class="mb-2 block text-sm font-medium text-slate-700">
                                    Valore modifica
                                </label>

                                <input
                                    id="adjustment_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.defer="form.adjustment_value"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                                >

                                <p class="mt-2 text-xs text-slate-500">
                                    Inserisci l'importo o la percentuale da applicare in base al tipo valore selezionato.
                                </p>

                                @error('form.adjustment_value')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        @if ($this->isOverrideRuleType())
                            <div>
                                <label for="override_price" class="mb-2 block text-sm font-medium text-slate-700">
                                    Prezzo override
                                </label>

                                <input
                                    id="override_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.defer="form.override_price"
                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                                >

                                <p class="mt-2 text-xs text-slate-500">
                                    Inserisci il prezzo finale che questa regola deve impostare quando risulta applicabile.
                                </p>

                                @error('form.override_price')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="rounded-lg border border-slate-200 bg-white p-4 md:col-span-2 space-y-4">
                            <div>
                                <label class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        wire:model="form.is_active"
                                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                    >

                                    <span class="text-sm font-medium text-slate-700">
                                        Regola attiva
                                    </span>
                                </label>

                                <p class="mt-2 text-xs text-slate-500">
                                    Se attiva, la regola viene considerata nella configurazione del listino. Se disattivata, resta salvata ma non viene applicata.
                                </p>
                            </div>

                            <div>
                                <label class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        wire:model="form.is_exclusive"
                                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                    >

                                    <span class="text-sm font-medium text-slate-700">
                                        Regola esclusiva
                                    </span>
                                </label>

                                <p class="mt-2 text-xs text-slate-500">
                                    Usala per indicare una regola che non dovrebbe combinarsi con altre. Questo comportamento verrà gestito nella logica di calcolo del pricing.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="starts_at" class="mb-2 block text-sm font-medium text-slate-700">
                                Data inizio
                            </label>

                            <input
                                id="starts_at"
                                type="date"
                                wire:model.defer="form.starts_at"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            @error('form.starts_at')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ends_at" class="mb-2 block text-sm font-medium text-slate-700">
                                Data fine
                            </label>

                            <input
                                id="ends_at"
                                type="date"
                                wire:model.defer="form.ends_at"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            @error('form.ends_at')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 block text-sm font-medium text-slate-700">
                            Giorni della settimana
                        </div>

                        <p class="mb-3 text-xs text-slate-500">
                            Se selezioni uno o più giorni, la regola verrà considerata solo in quelle giornate.
                        </p>

                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            @foreach ($this->options['weekdays'] as $option)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <input
                                        type="checkbox"
                                        value="{{ $option['value'] }}"
                                        wire:model="form.weekdays"
                                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                    >

                                    <span class="text-sm text-slate-700">
                                        {{ $option['label'] }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('form.weekdays')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        @error('form.weekdays.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="distance_km_min" class="mb-2 block text-sm font-medium text-slate-700">
                                Distanza minima (km)
                            </label>

                            <input
                                id="distance_km_min"
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model.defer="form.conditions.distance_km_min"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Applica la regola solo oltre questa distanza.
                            </p>

                            @error('form.conditions.distance_km_min')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="distance_km_max" class="mb-2 block text-sm font-medium text-slate-700">
                                Distanza massima (km)
                            </label>

                            <input
                                id="distance_km_max"
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model.defer="form.conditions.distance_km_max"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Applica la regola solo fino a questa distanza.
                            </p>

                            @error('form.conditions.distance_km_max')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="lead_days_min" class="mb-2 block text-sm font-medium text-slate-700">
                                Anticipo minimo (giorni)
                            </label>

                            <input
                                id="lead_days_min"
                                type="number"
                                min="0"
                                wire:model.defer="form.conditions.lead_days_min"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                La regola si applica solo se la prenotazione viene fatta con almeno questi giorni di anticipo.
                            </p>

                            @error('form.conditions.lead_days_min')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="lead_days_max" class="mb-2 block text-sm font-medium text-slate-700">
                                Anticipo massimo (giorni)
                            </label>

                            <input
                                id="lead_days_max"
                                type="number"
                                min="0"
                                wire:model.defer="form.conditions.lead_days_max"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                La regola si applica solo entro questo numero di giorni prima dell'evento.
                            </p>

                            @error('form.conditions.lead_days_max')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="guests_min" class="mb-2 block text-sm font-medium text-slate-700">
                                Ospiti minimi
                            </label>

                            <input
                                id="guests_min"
                                type="number"
                                min="1"
                                wire:model.defer="form.conditions.guests_min"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            @error('form.conditions.guests_min')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="guests_max" class="mb-2 block text-sm font-medium text-slate-700">
                                Ospiti massimi
                            </label>

                            <input
                                id="guests_max"
                                type="number"
                                min="1"
                                wire:model.defer="form.conditions.guests_max"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            @error('form.conditions.guests_max')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="time_from" class="mb-2 block text-sm font-medium text-slate-700">
                                Ora da
                            </label>

                            <input
                                id="time_from"
                                type="time"
                                wire:model.defer="form.conditions.time_from"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            @error('form.conditions.time_from')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="time_to" class="mb-2 block text-sm font-medium text-slate-700">
                                Ora a
                            </label>

                            <input
                                id="time_to"
                                type="time"
                                wire:model.defer="form.conditions.time_to"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                            >

                            @error('form.conditions.time_to')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="rule_notes_internal" class="mb-2 block text-sm font-medium text-slate-700">
                            Note interne
                        </label>

                        <textarea
                            id="rule_notes_internal"
                            rows="4"
                            wire:model.defer="form.notes_internal"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                        ></textarea>

                        @error('form.notes_internal')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <button
                            type="button"
                            wire:click="cancelEdit"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Annulla
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            {{ $editingRuleId ? 'Aggiorna regola' : 'Salva regola' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            @if ($this->rules->isEmpty())
                <div class="px-4 py-6 text-sm text-slate-500">
                    Nessuna regola configurata per questo listino.
                </div>
            @else
                <div class="divide-y divide-slate-200">
                    @foreach ($this->rules as $rule)
                        <div class="flex flex-col gap-4 px-4 py-4 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="font-medium text-slate-900">
                                        {{ $rule->name }}
                                    </div>

                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $rule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $rule->is_active ? 'Attiva' : 'Inattiva' }}
                                    </span>

                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        Priorità {{ $rule->priority }}
                                    </span>
                                </div>

                                <div class="mt-2 space-y-1 text-sm text-slate-600">
                                    <div>
                                        <span class="font-medium text-slate-700">Tipo:</span>
                                        {{ \App\Domain\Pricing\Support\PricingOptions::ruleTypeLabel($rule->rule_type) }}
                                    </div>

                                    <div>
                                        <span class="font-medium text-slate-700">Effetto:</span>
                                        {{ $this->ruleValueLabel($rule) }}
                                    </div>

                                    <div>
                                        <span class="font-medium text-slate-700">Condizioni:</span>
                                        {{ $this->ruleConditionsLabel($rule) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="toggleRule({{ $rule->id }})"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    {{ $rule->is_active ? 'Disattiva' : 'Attiva' }}
                                </button>

                                <button
                                    type="button"
                                    wire:click="startEdit({{ $rule->id }})"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                >
                                    Modifica
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteRule({{ $rule->id }})"
                                    wire:confirm="Vuoi eliminare questa regola?"
                                    class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50"
                                >
                                    Elimina
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>