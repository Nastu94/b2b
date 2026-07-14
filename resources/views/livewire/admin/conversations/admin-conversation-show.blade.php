<div class="py-6 flex flex-col h-screen max-h-[800px]">
    <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 flex-1 flex flex-col">
        
        <div class="mb-4 flex justify-between items-center">
            <div>
                <a href="{{ route('admin.conversations') }}" class="text-slate-600 hover:text-slate-900">&larr; Torna alle conversazioni</a>
                <h1 class="text-2xl font-semibold text-gray-900 mt-2">Chat: {{ $conversation->vendorAccount->company_name ?? 'Vendor' }} vs {{ $conversation->customer_name ?? 'Cliente' }}</h1>
                <p class="text-sm text-gray-500">
                    Servizio: {{ $conversation->offering ? $conversation->offering->name : 'N/A' }} | 
                    Booking: {{ $conversation->booking_id ?? 'N/A' }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                @if($conversation->status === 'open')
                    <button wire:click="closeConversation" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Chiudi Conversazione</button>
                @else
                    <span class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Chiusa</span>
                @endif
                <button
                    type="button"
                    wire:click="deleteConversation"
                    wire:confirm="Sei sicuro di voler nascondere questa conversazione?"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none"
                    aria-label="Elimina conversazione"
                >
                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Nascondi
                </button>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg flex-1 flex flex-col overflow-hidden">
            <div class="flex-1 overflow-y-auto p-4 space-y-6 bg-gray-50">
                @foreach($messages as $message)
                    <div class="flex {{ $message->sender_type === 'vendor' ? 'justify-end' : ($message->sender_type === 'admin' ? 'justify-center' : 'justify-start') }}">
                        <div class="rounded-lg px-4 py-3 max-w-[80%] {{ $message->sender_type === 'vendor' ? 'bg-slate-50 border border-slate-200' : ($message->sender_type === 'admin' ? 'bg-yellow-50 border border-yellow-200 text-center' : 'bg-white border border-gray-200') }}">
                            
                            <div class="flex justify-between items-center mb-2 border-b border-gray-200 pb-1">
                                <span class="font-semibold text-sm {{ $message->sender_type === 'vendor' ? 'text-slate-700' : ($message->sender_type === 'admin' ? 'text-yellow-700' : 'text-gray-700') }}">
                                    {{ ucfirst($message->sender_type) }}
                                </span>
                                <span class="text-xs text-gray-500 ml-4">{{ $message->created_at->format('d/m/Y H:i:s') }}</span>
                            </div>

                            @if($message->moderation_status !== 'clean')
                                <div class="mb-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-800 font-mono">
                                    <span class="font-bold">Originale (Nascosto):</span><br>
                                    {{ $message->body_original }}
                                </div>
                                <div class="text-sm">
                                    <span class="font-bold text-gray-500 text-xs uppercase">Filtrato:</span><br>
                                    <p class="whitespace-pre-wrap">{{ $message->body_filtered }}</p>
                                </div>
                                <div class="mt-2 text-xs text-gray-400">
                                    Flag: {{ is_array($message->moderation_flags) ? implode(', ', $message->moderation_flags) : $message->moderation_flags }}
                                </div>
                            @else
                                <p class="text-sm whitespace-pre-wrap">{{ $message->body_original }}</p>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>

            @if($conversation->status === 'open')
            <div class="p-4 bg-white border-t border-gray-200">
                <form wire:submit="sendMessage" class="flex space-x-4">
                    <div class="flex-1">
                        <textarea wire:model="newMessage" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm" placeholder="Invia un messaggio di moderazione..."></textarea>
                        @error('newMessage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700">
                            Invia come Admin
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

    </div>
</div>
