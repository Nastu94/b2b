{{-- resources/views/livewire/vendor/offerings/manage-offering-contents.blade.php --}}
<div class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold">Contenuti servizi selezionati</h2>
        <p class="text-sm text-gray-600 mt-1">
            Per ogni servizio attivo, inserisci descrizione e carica immagini (cover + gallery).
        </p>

        <div class="mt-6 space-y-4">
            @forelse($activeOfferingIds as $offeringId)
                <div id="offering-{{ $offeringId }}">
                    <livewire:vendor.offering-content-card
                        :offeringId="$offeringId"
                        :key="'offering-content-card-' . $offeringId"
                    />
                </div>
            @empty
                <div class="p-4 border rounded bg-gray-50 text-sm text-gray-700">
                    Non hai ancora servizi attivi. Vai nella tab “Servizi”, seleziona almeno un servizio e salva.
                </div>
            @endforelse
        </div>
    </div>
</div>