<div class="p-6">
    <div class="space-y-6">

        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Prenotazioni</h1>
            <p class="mt-1 text-slate-600">Gestione prenotazioni: stato, dettagli e azioni admin.</p>
        </div>

        <div class="bg-white shadow rounded-lg">

            <div class="border-b border-slate-200 px-6">
                <nav class="-mb-px flex gap-8" aria-label="Tabs">

                    <button type="button" wire:click="setTab('pending')"
                        class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition
        {{ $tab === 'pending' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        <x-app-icon name="clock" class="w-4 h-4" />
                        <span>In attesa</span>
                    </button>

                    <button type="button" wire:click="setTab('confirmed')"
                        class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition
        {{ $tab === 'confirmed' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        <x-app-icon name="check-circle" class="w-4 h-4" />
                        <span>Confermate</span>
                    </button>

                    <button type="button" wire:click="setTab('declined')"
                        class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition
        {{ $tab === 'declined' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        <x-app-icon name="x-circle" class="w-4 h-4" />
                        <span>Rifiutate</span>
                    </button>

                    <button type="button" wire:click="setTab('all')"
                        class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition
        {{ $tab === 'all' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        <x-app-icon name="list-bullet" class="w-4 h-4" />
                        <span>Tutte</span>
                    </button>

                </nav>
            </div>

            <div class="p-6">
                @if ($tab === 'pending')
                    <livewire:admin.bookings.admin-bookings-list status="PENDING_VENDOR_CONFIRMATION"
                        :key="'admin-pending'" />
                @elseif($tab === 'confirmed')
                    <livewire:admin.bookings.admin-bookings-list status="CONFIRMED" :key="'admin-confirmed'" />
                @elseif($tab === 'declined')
                    <livewire:admin.bookings.admin-bookings-list status="DECLINED" :key="'admin-declined'" />
                @elseif($tab === 'all')
                    <livewire:admin.bookings.admin-bookings-list status="" :key="'admin-all'" />
                @endif
            </div>

        </div>
    </div>
</div>
