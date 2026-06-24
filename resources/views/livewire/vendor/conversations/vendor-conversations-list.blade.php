<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Le tue Conversazioni</h1>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($conversations as $conversation)
                    <li>
                        <a href="{{ route('vendor.conversations.show', $conversation) }}" class="block hover:bg-gray-50">
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
