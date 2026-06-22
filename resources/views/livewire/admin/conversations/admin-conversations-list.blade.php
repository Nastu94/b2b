<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Tutte le Conversazioni</h1>

        <!-- Filtri -->
        <div class="mb-4 flex space-x-2">
            <button wire:click="setFilter('all')" class="px-3 py-1 rounded {{ $filter === 'all' ? 'bg-slate-600 text-white' : 'bg-gray-200 text-gray-700' }}">Tutte</button>
            <button wire:click="setFilter('open')" class="px-3 py-1 rounded {{ $filter === 'open' ? 'bg-slate-600 text-white' : 'bg-gray-200 text-gray-700' }}">Aperte</button>
            <button wire:click="setFilter('filtered')" class="px-3 py-1 rounded {{ $filter === 'filtered' ? 'bg-slate-600 text-white' : 'bg-gray-200 text-gray-700' }}">Con contenuti filtrati</button>
            <button wire:click="setFilter('flagged')" class="px-3 py-1 rounded {{ $filter === 'flagged' ? 'bg-slate-600 text-white' : 'bg-gray-200 text-gray-700' }}">Sospette (Da verificare)</button>
            <button wire:click="setFilter('closed')" class="px-3 py-1 rounded {{ $filter === 'closed' ? 'bg-slate-600 text-white' : 'bg-gray-200 text-gray-700' }}">Chiuse</button>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($conversations as $conversation)
                    <li>
                        <a href="{{ route('admin.conversations.show', $conversation) }}" class="block hover:bg-gray-50">
                            <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <p class="text-sm font-medium text-slate-600 truncate">
                                        {{ $conversation->vendorAccount->company_name ?? 'Vendor' }} 
                                        <span class="text-gray-500 font-normal">vs</span> 
                                        {{ $conversation->guest_name ?? 'Cliente PrestaShop' }}
                                        
                                        @if($conversation->admin_unread_count > 0)
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Nuovi messaggi
                                            </span>
                                        @endif
                                        
                                        @if($conversation->status === 'closed')
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Chiusa</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 flex items-center text-sm text-gray-500">
                                        {{ $conversation->offering ? $conversation->offering->name : 'Nessun servizio specifico' }}
                                    </p>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : '' }}
                                </div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500">
                        Nessuna conversazione trovata con questo filtro.
                    </li>
                @endforelse
            </ul>
        </div>
        <div class="mt-4">
            {{ $conversations->links() }}
        </div>
    </div>
</div>
