<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Le tue Conversazioni</h1>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($conversations as $conversation)
                    <li class="flex items-center hover:bg-gray-50 pr-4">
                        <a href="{{ route('vendor.conversations.show', $conversation) }}" class="flex-1 block">
                            <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <p class="text-sm font-medium text-slate-600 truncate">
                                        {{ $conversation->customer_name ?? 'Cliente PrestaShop' }}
                                        @if($conversation->vendor_unread_count > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ $conversation->vendor_unread_count }} Nuovi
                                            </span>
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
                        <button
                            type="button"
                            wire:click="deleteConversation({{ $conversation->id }})"
                            wire:confirm="Sei sicuro di voler nascondere questa conversazione?"
                            class="ml-4 text-red-600 hover:text-red-900 focus:outline-none"
                            aria-label="Elimina conversazione"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500">
                        Nessuna conversazione trovata.
                    </li>
                @endforelse
            </ul>
        </div>
        <div class="mt-4">
            {{ $conversations->links() }}
        </div>
    </div>
</div>
