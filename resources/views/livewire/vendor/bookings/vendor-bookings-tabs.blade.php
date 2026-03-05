<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">
                    Prenotazioni
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Gestisci le richieste dei clienti: in attesa e confermate.
                </p>
            </div>
        </div>

        {{-- Container principale --}}
        <div class="bg-white shadow rounded-lg mt-6">

            {{-- Tabs --}}
            <div class="border-b border-slate-200 px-6">
                <nav class="-mb-px flex gap-8" aria-label="Tabs">

                    <button type="button" wire:click="setTab('pending')"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition {{ $tab === 'pending' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        In attesa
                    </button>


                    <button type="button" wire:click="setTab('confirmed')"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition {{ $tab === 'confirmed' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        Confermate
                    </button>

                </nav>
            </div>

            {{-- Contenuto tab --}}
            <div class="p-4">

                @if ($tab === 'pending')
                    <livewire:vendor.bookings.vendor-bookings-list status="PENDING_VENDOR_CONFIRMATION"
                        :key="'pending'" />
                @elseif($tab === 'confirmed')
                    <livewire:vendor.bookings.vendor-bookings-list status="CONFIRMED" :key="'confirmed'" />
                @endif

            </div>

        </div>
    </div>
</div>
