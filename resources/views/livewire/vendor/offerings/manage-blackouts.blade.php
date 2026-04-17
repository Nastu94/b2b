{{-- resources/views/livewire/vendor/offerings/manage-blackouts.blade.php --}}
<div>
    {{-- Flash message --}}
    @if (session('status'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-600">
            Blocca date o fasce orarie in cui non sei disponibile.
        </p>
        @unless ($showForm)
            <button wire:click="openCreate" type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <x-app-icon name="plus" class="w-4 h-4" />
                <span>Nuovo</span>
            </button>
        @endunless
    </div>

    {{-- Form crea/modifica --}}
    @if ($showForm)
        <div class="mb-6 p-4 border border-slate-200 bg-slate-50 rounded-lg">
            <h3 class="text-sm font-semibold text-slate-800 mb-3">
                {{ $editingId ? 'Modifica indisponibilità' : 'Nuova indisponibilità' }}
            </h3>

            <div class="space-y-3">

                {{-- Date --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Data inizio <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model.live="date_from"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500" />
                        @error('date_from')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Data fine <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model.live="date_to" min="{{ $date_from }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500" />
                        @error('date_to')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Slot specifico (opzionale) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Slot da bloccare
                        <span class="text-gray-400 font-normal">(lascia vuoto per bloccare tutti gli slot)</span>
                    </label>
                    <select wire:model="vendor_slot_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500">
                        <option value="">— Tutti gli slot (giorno intero) —</option>
                        @foreach ($slots as $slot)
                            <option value="{{ $slot->id }}">
                                {{ $slot->label }} ({{ substr($slot->start_time, 0, 5) }} -
                                {{ substr($slot->end_time, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_slot_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Motivo interno --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Motivo interno
                        <span class="text-gray-400 font-normal">(visibile solo a te)</span>
                    </label>
                    <input type="text" wire:model="reason_internal" placeholder="Es. Ferie, Evento privato..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500" />
                </div>

                {{-- Motivo pubblico --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Motivo pubblico
                        <span class="text-gray-400 font-normal">(mostrato al cliente su PrestaShop)</span>
                    </label>
                    <input type="text" wire:model="reason_public" placeholder="Es. Non disponibile in questa data"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500" />
                </div>
            </div>

            {{-- Bottoni --}}
            <div class="flex gap-2 mt-4">
                <button wire:click="save" type="button"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium rounded-lg">
                    {{ $editingId ? 'Aggiorna' : 'Crea indisponibilità' }}
                </button>
                <button wire:click="cancel" type="button"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                    Annulla
                </button>
            </div>
        </div>
    @endif

    {{-- Lista blackout --}}
    @if ($blackouts->isEmpty())
        <div class="text-center py-8 text-gray-400 text-sm border border-dashed rounded-lg">
            Nessuna indisponibilità definita.
        </div>
    @else
        <div class="space-y-2">
            @foreach ($blackouts as $blackout)
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-white">
                    <div class="flex items-center gap-3">
                        {{-- Icona tipo blackout --}}
                        <div class="text-lg">
                            {{ $blackout->isFullDay() ? 'Giorno intero' : 'Slot specifico' }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800">
                                {{ $blackout->rangeLabel() }}
                                @if (!$blackout->isFullDay())
                                    <span class="ml-1 text-slate-800 text-xs">—</span>
                                    — {{ $blackout->slot?->label }}
                                    ({{ substr($blackout->slot?->start_time, 0, 5) }} -
                                    {{ substr($blackout->slot?->end_time, 0, 5) }})
                                    </span>
                                @else
                                    <span class="ml-1 text-gray-400 text-xs">Tutti gli slot</span>
                                @endif
                            </div>
                            @if ($blackout->reason_internal)
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ $blackout->reason_internal }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button wire:click="openEdit({{ $blackout->id }})" type="button"
                            class="text-xs text-slate-800 hover:text-slate-600 font-medium">
                            Modifica
                        </button>
                        <button wire:click="confirmDelete({{ $blackout->id }})" type="button"
                            class="text-xs text-red-500 hover:text-red-700 font-medium">
                            Elimina
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modale conferma eliminazione --}}
    @if ($confirmDeleteId)
        <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Elimina indisponibilità</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Sei sicuro di voler eliminare questa indisponibilità?
                </p>
                <div class="flex gap-3">
                    <button wire:click="delete" type="button"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                        Sì, elimina
                    </button>
                    <button wire:click="cancelDelete" type="button"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
