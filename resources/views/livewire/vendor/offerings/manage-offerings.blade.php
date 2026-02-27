<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold">I miei servizi</h2>
        <p class="text-sm text-gray-600 mt-1">
            Seleziona i servizi che offri nella tua categoria.
        </p>

        @if (session('status'))
            <div class="mt-4 p-3 rounded bg-green-50 text-green-800 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($availableOfferings as $offering)
                    <label class="flex items-center gap-2 p-3 border rounded">
                        <input type="checkbox" wire:model="selectedOfferingIds" value="{{ $offering->id }}" />
                        <span>{{ $offering->name }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Salva
                </button>
            </div>
        </form>

        {{-- SOTTO: schede contenuti per servizi selezionati --}}
        <div class="mt-10">
            <h3 class="text-base font-semibold text-gray-900">Contenuti servizi selezionati</h3>
            <p class="text-sm text-gray-600 mt-1">
                Per ogni servizio selezionato, inserisci descrizione e carica immagini (cover + gallery).
            </p>

            <div class="mt-6 space-y-4">
                @forelse($selectedOfferingIds as $offeringId)
                    <div id="offering-{{ $offeringId }}">
                        <livewire:vendor.offering-content-card :offeringId="$offeringId" :key="'offering-content-card-' . $offeringId" />
                    </div>
                @empty
                    <div class="p-4 border rounded bg-gray-50 text-sm text-gray-700">
                        Seleziona almeno un servizio sopra: qui compariranno le schede per inserire foto e descrizione.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
