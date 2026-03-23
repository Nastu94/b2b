{{-- resources/views/livewire/vendor/offerings/manage-offerings-tabs.blade.php --}}
<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Gestione servizi</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Configura i tuoi servizi: selezione, contenuti, slot e template settimanale.
                </p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-6 border-b">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">

                <button type="button" wire:click="setTab('offerings')"
                    class="inline-flex items-center gap-2 whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
            {{ $activeTab === 'offerings' ? 'border-slate-600 text-slate-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                    <x-app-icon name="briefcase" class="w-4 h-4" />
                    <span>Servizi</span>
                </button>

                <button type="button" wire:click="setTab('content')"
                    class="inline-flex items-center gap-2 whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
            {{ $activeTab === 'content' ? 'border-slate-600 text-slate-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                    <x-app-icon name="photo" class="w-4 h-4" />
                    <span>Contenuti</span>
                </button>

                <button type="button" wire:click="setTab('slots')"
                    class="inline-flex items-center gap-2 whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
            {{ $activeTab === 'slots' ? 'border-slate-600 text-slate-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                    <x-app-icon name="clock" class="w-4 h-4" />
                    <span>Slot</span>
                </button>

                <button type="button" wire:click="setTab('weekly')"
                    class="inline-flex items-center gap-2 whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
            {{ $activeTab === 'weekly' ? 'border-slate-600 text-slate-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                    <x-app-icon name="calendar-days" class="w-4 h-4" />
                    <span>Template settimanale</span>
                </button>

                <button type="button" wire:click="setTab('blackouts')"
                    class="inline-flex items-center gap-2 whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
            {{ $activeTab === 'blackouts' ? 'border-slate-600 text-slate-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                    <x-app-icon name="x-circle" class="w-4 h-4" />
                    <span>Blackout</span>
                </button>

                <button type="button" wire:click="setTab('leadtime')"
                    class="inline-flex items-center gap-2 whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium
            {{ $activeTab === 'leadtime' ? 'border-slate-600 text-slate-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}">
                    <x-app-icon name="clock" class="w-4 h-4" />
                    <span>Lead time</span>
                </button>

            </nav>
        </div>

        {{-- Contenuto tab --}}
        <div class="mt-6">
            @if ($activeTab === 'offerings')
                <livewire:vendor.offerings.manage-offerings :key="'tab-offerings'" />
            @elseif ($activeTab === 'content')
                <livewire:vendor.offerings.manage-offering-contents :key="'tab-content'" />
            @elseif ($activeTab === 'slots')
                <livewire:vendor.offerings.manage-vendor-slots :key="'tab-slots'" />
            @elseif ($activeTab === 'weekly')
                <livewire:vendor.offerings.manage-weekly-schedule :key="'tab-weekly'" />
            @elseif ($activeTab === 'blackouts')
                <livewire:vendor.offerings.manage-blackouts :key="'tab-blackouts'" />
            @elseif ($activeTab === 'leadtime')
                <livewire:vendor.offerings.manage-lead-times :key="'tab-leadtime'" />
            @endif
        </div>
    </div>
</div>
