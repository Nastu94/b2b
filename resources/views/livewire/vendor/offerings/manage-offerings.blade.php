{{-- resources/views/livewire/vendor/offerings/manage-offerings.blade.php --}}
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
                        <div class="flex-1">
                            <span class="block">{{ $offering->name }}</span>
                            @if($offering->is_custom && $offering->status === \App\Models\Offering::STATUS_PENDING_REVIEW)
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">In approvazione</span>
                            @elseif($offering->is_custom && $offering->status === \App\Models\Offering::STATUS_REJECTED)
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Rifiutato</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    <x-app-icon name="check" class="w-4 h-4" />
                    <span>Salva</span>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6 mt-6">
        <h2 class="text-lg font-semibold">Proponi nuovo servizio</h2>
        <p class="text-sm text-gray-600 mt-1">
            Se non trovi il servizio che offri, proponi uno nuovo al nostro team.
        </p>

        <form wire:submit.prevent="proposeCustomOffering" class="mt-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Titolo pubblico</label>
                <input type="text" wire:model="newOfferingTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Descrizione breve</label>
                <textarea wire:model="newOfferingShortDesc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" rows="2" required></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Descrizione completa</label>
                <textarea wire:model="newOfferingFullDesc" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" rows="4" required></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    <x-app-icon name="plus" class="w-4 h-4" />
                    <span>Proponi Servizio</span>
                </button>
            </div>
        </form>
    </div>
</div>
