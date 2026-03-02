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
            Definisci le fasce orarie che vuoi offrire ai clienti.
        </p>
        @unless($showForm)
            <button
                wire:click="openCreate"
                type="button"
                class="inline-flex items-center gap-1 px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
            >
                + Nuovo slot
            </button>
        @endunless
    </div>

    {{-- Form crea/modifica --}}
    @if($showForm)
        <div class="mb-6 p-4 border border-indigo-200 bg-indigo-50 rounded-lg">
            <h3 class="text-sm font-semibold text-indigo-800 mb-3">
                {{ $editingId ? 'Modifica slot' : 'Nuovo slot' }}
            </h3>

            <div class="space-y-3">
                {{-- Nome slot --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Nome slot <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="label"
                        placeholder="Es. Mattina, Pranzo, Sera"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    @error('label')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Orari obbligatori --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Ora inizio <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="time"
                            wire:model="start_time"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        @error('start_time')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Ora fine <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="time"
                            wire:model="end_time"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        @error('end_time')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Anteprima slug --}}
                @if($label && $start_time && $end_time)
                    <div class="text-xs text-gray-400">
                        ID slot generato:
                        <code class="bg-gray-100 px-1 py-0.5 rounded text-gray-600">
                            {{ \Illuminate\Support\Str::slug($label) }}-{{ str_replace(':', '', $start_time) }}-{{ str_replace(':', '', $end_time) }}
                        </code>
                    </div>
                @endif
            </div>

            {{-- Bottoni --}}
            <div class="flex gap-2 mt-4">
                <button
                    wire:click="save"
                    type="button"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
                >
                    {{ $editingId ? 'Aggiorna' : 'Crea slot' }}
                </button>
                <button
                    wire:click="cancel"
                    type="button"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                >
                    Annulla
                </button>
            </div>
        </div>
    @endif

    {{-- Lista slot --}}
    @if($slots->isEmpty())
        <div class="text-center py-8 text-gray-400 text-sm border border-dashed rounded-lg">
            Nessuno slot definito. Crea il primo slot per iniziare.
        </div>
    @else
        <div class="space-y-2">
            @foreach($slots as $slot)
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg bg-white">
                    <div>
                        <span class="text-sm font-medium text-gray-800">
                            {{ $slot->label }}
                        </span>
                        <span class="ml-2 text-xs text-gray-500">
                            {{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}
                        </span>
                        <span class="ml-2 text-xs text-gray-300">
                            {{ $slot->slug }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            wire:click="openEdit({{ $slot->id }})"
                            type="button"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                        >
                            Modifica
                        </button>
                        <button
                            wire:click="confirmDelete({{ $slot->id }})"
                            type="button"
                            class="text-xs text-red-500 hover:text-red-700 font-medium"
                        >
                            Elimina
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modale conferma eliminazione --}}
    @if($confirmDeleteId)
        <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
                <h3 class="text-base font-semibold text-gray-900 mb-2">Elimina slot</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Sei sicuro? Lo slot verrà rimosso anche dal template settimanale.
                </p>
                <div class="flex gap-3">
                    <button
                        wire:click="delete"
                        type="button"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700"
                    >
                        Sì, elimina
                    </button>
                    <button
                        wire:click="cancelDelete"
                        type="button"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                    >
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>