<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Richieste in attesa</h1>
        <a href="{{ route('vendor.dashboard') }}" class="px-3 py-2 bg-gray-100 rounded">Dashboard</a>
    </div>

    <div class="bg-white shadow rounded-lg divide-y">
        @forelse($bookings as $b)
            <div class="p-4 flex items-center justify-between">
                <div class="space-y-1">
                    <div class="font-medium">
                        Booking #{{ $b->id }} — {{ $b->event_date->format('d/m/Y') }}
                    </div>
                    <div class="text-sm text-gray-600">
                        Ordine PS: {{ $b->prestashop_order_id }} / Linea: {{ $b->prestashop_order_line_id }}
                    </div>
                    <div class="text-sm text-gray-600">
                        Totale: {{ $b->total_amount }}
                    </div>
                </div>

                <div>
                    <a class="px-3 py-2 bg-blue-600 text-white rounded"
                       href="{{ route('vendor.bookings.show', $b) }}">
                        Apri
                    </a>
                </div>
            </div>
        @empty
            <div class="p-4 text-gray-600">Nessuna richiesta pendente </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $bookings->links() }}
    </div>
</div>