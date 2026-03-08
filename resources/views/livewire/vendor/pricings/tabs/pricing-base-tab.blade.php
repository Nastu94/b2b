{{-- resources/views/livewire/vendor/pricings/tabs/pricing-base-tab.blade.php --}}
<div class="space-y-6">
    @if (session()->has('pricing_base_success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('pricing_base_success') }}
        </div>
    @endif

    <div>
        <h2 class="text-lg font-semibold text-slate-900">Listino base</h2>
        <p class="mt-1 text-sm text-slate-600">
            Imposta il prezzo principale del servizio, la valuta e le regole generali legate alla distanza.
        </p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            {{-- Stato listino --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <label class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model="form.is_active"
                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                    >

                    <span class="text-sm font-medium text-slate-700">
                        Listino attivo
                    </span>
                </label>

                <p class="mt-2 text-xs text-slate-500">
                    Disattivando il listino il servizio non utilizzerà questa configurazione prezzi.
                </p>

                @error('form.is_active')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Valuta --}}
            <div>
                <label for="currency" class="mb-2 block text-sm font-medium text-slate-700">
                    Valuta
                </label>

                <input
                    id="currency"
                    type="text"
                    wire:model.defer="form.currency"
                    maxlength="3"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                >

                @error('form.currency')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tipo prezzo --}}
            <div>
                <label for="price_type" class="mb-2 block text-sm font-medium text-slate-700">
                    Tipo prezzo
                </label>

                <select
                    id="price_type"
                    wire:model.defer="form.price_type"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                >
                    @foreach ($this->options['priceTypes'] as $option)
                        <option value="{{ $option['value'] }}">
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>

                @error('form.price_type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Prezzo base --}}
            <div>
                <label for="base_price" class="mb-2 block text-sm font-medium text-slate-700">
                    Prezzo base
                </label>

                <input
                    id="base_price"
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model.defer="form.base_price"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                >

                @error('form.base_price')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Raggio servizio --}}
            <div>
                <label for="service_radius_km" class="mb-2 block text-sm font-medium text-slate-700">
                    Raggio di servizio (km)
                </label>

                <input
                    id="service_radius_km"
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model.defer="form.service_radius_km"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                >

                <p class="mt-2 text-xs text-slate-500">
                    Lascia vuoto se non vuoi impostare un limite di raggio in questa fase.
                </p>

                @error('form.service_radius_km')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Gestione distanza --}}
            <div>
                <label for="distance_pricing_mode" class="mb-2 block text-sm font-medium text-slate-700">
                    Gestione distanza
                </label>

                <select
                    id="distance_pricing_mode"
                    wire:model.defer="form.distance_pricing_mode"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
                >
                    @foreach ($this->options['distanceModes'] as $option)
                        <option value="{{ $option['value'] }}">
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>

                @error('form.distance_pricing_mode')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Note interne --}}
        <div>
            <label for="notes_internal" class="mb-2 block text-sm font-medium text-slate-700">
                Note interne
            </label>

            <textarea
                id="notes_internal"
                rows="4"
                wire:model.defer="form.notes_internal"
                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300"
            ></textarea>

            <p class="mt-2 text-xs text-slate-500">
                Campo visibile solo in gestione interna, utile per annotazioni operative.
            </p>

            @error('form.notes_internal')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Azioni --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
            >
                {{ $pricingId ? 'Aggiorna listino base' : 'Crea listino base' }}
            </button>
        </div>
    </form>
</div>