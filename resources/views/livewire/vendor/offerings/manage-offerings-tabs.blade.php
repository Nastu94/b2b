{{-- resources/views/livewire/vendor/offerings/manage-offerings-tabs.blade.php --}}
<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Gestione servizi</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Configura i tuoi servizi: selezione, contenuti e (in seguito) disponibilità, blackout e lead time.
                </p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-6 border-b">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button
                    type="button"
                    wire:click="setTab('offerings')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
                        {{ $activeTab === 'offerings' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}"
                >
                    Servizi
                </button>

                <button
                    type="button"
                    wire:click="setTab('content')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
                        {{ $activeTab === 'content' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}"
                >
                    Contenuti
                </button>

                {{-- Placeholder: le prossime tab le aggiungeremo quando implementiamo le feature del documento --}}
                <button
                    type="button"
                    wire:click="setTab('availability')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
                        {{ $activeTab === 'availability' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}"
                >
                    Disponibilità
                </button>

                <button
                    type="button"
                    wire:click="setTab('blackouts')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
                        {{ $activeTab === 'blackouts' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}"
                >
                    Blackout
                </button>

                <button
                    type="button"
                    wire:click="setTab('leadtime')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
                        {{ $activeTab === 'leadtime' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}"
                >
                    Lead time
                </button>
            </nav>
        </div>

        {{-- Contenuto tab --}}
        <div class="mt-6">
            @if ($activeTab === 'offerings')
                {{-- Tab 1: selezione servizi --}}
                <livewire:vendor.offerings.manage-offerings :key="'tab-offerings'" />
            @elseif ($activeTab === 'content')
                {{-- Tab 2: contenuti servizi --}}
                <livewire:vendor.offerings.manage-offering-contents :key="'tab-content'" />
            @elseif ($activeTab === 'availability')
                <div class="p-4 border rounded bg-gray-50 text-sm text-gray-700">
                    Sezione “Disponibilità” in arrivo: template settimanale + calcolo disponibilità.
                </div>
            @elseif ($activeTab === 'blackouts')
                <div class="p-4 border rounded bg-gray-50 text-sm text-gray-700">
                    Sezione “Blackout” in arrivo: blocchi manuali per date/slot/intervalli.
                </div>
            @elseif ($activeTab === 'leadtime')
                <div class="p-4 border rounded bg-gray-50 text-sm text-gray-700">
                    Sezione “Lead time” in arrivo: anticipo minimo/cutoff.
                </div>
            @endif
        </div>
    </div>
</div>