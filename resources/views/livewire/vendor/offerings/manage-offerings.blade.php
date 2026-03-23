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
                        <span>{{ $offering->name }}</span>
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
</div>
