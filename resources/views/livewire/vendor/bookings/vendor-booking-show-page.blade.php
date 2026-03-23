<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Booking #{{ $booking->id }}</h1>
        <a href="{{ route('vendor.bookings') }}" class="px-3 py-2 bg-gray-100 rounded">← Indietro</a>
    </div>

    <div class="p-6 space-y-4">
        <div class="bg-white shadow rounded-lg p-4 space-y-2">
            <div><strong>Stato:</strong> {{ $booking->status }}</div>
            <div><strong>Data:</strong> {{ $booking->event_date->format('d/m/Y') }}</div>
            <div><strong>Ordine PS:</strong> {{ $booking->prestashop_order_id }} /
                {{ $booking->prestashop_order_line_id }}</div>
            <div><strong>Totale:</strong> {{ $booking->total_amount }}</div>

            <div class="pt-2 space-y-1">
                <strong>Cliente:</strong>

                <div class="text-sm text-gray-700">
                    {{ data_get($booking->customer_data, 'firstname') }}
                    {{ data_get($booking->customer_data, 'lastname') }}
                </div>

                <div class="text-sm text-gray-700">
                    {{ data_get($booking->customer_data, 'email') }}
                </div>

                @php
                    $phone =
                        data_get($booking->customer_data, 'delivery_address.phone_mobile') ?:
                        data_get($booking->customer_data, 'delivery_address.phone');
                @endphp

                @if ($phone)
                    <div class="text-sm text-gray-700">
                        {{ $phone }}
                    </div>
                @endif
            </div>

            @php
                $addr = data_get($booking->customer_data, 'delivery_address');
            @endphp

            @if (is_array($addr))
                <div class="pt-2">
                    <strong>Indirizzo evento:</strong>

                    <div class="text-sm text-gray-700">
                        @if (!empty($addr['company']))
                            <div>{{ $addr['company'] }}</div>
                        @endif

                        @if (!empty($addr['address_line1']))
                            <div>{{ $addr['address_line1'] }}</div>
                        @endif

                        @if (!empty($addr['address_line2']))
                            <div>{{ $addr['address_line2'] }}</div>
                        @endif

                        <div>
                            {{ $addr['postcode'] ?? '' }}
                            {{ $addr['city'] ?? '' }}
                        </div>

                        @if (!empty($addr['state']))
                            <div>{{ $addr['state'] }}</div>
                        @endif

                        @if (!empty($addr['country']))
                            <div>{{ $addr['country'] }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <label class="block text-sm font-medium">Note vendor</label>
            <textarea wire:model="vendorNotes" class="w-full border rounded p-2" rows="3"></textarea>

            @if ($booking->status === 'PENDING_VENDOR_CONFIRMATION')
                <div class="flex gap-2">
                    <button wire:click="confirm"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                        <x-app-icon name="check-circle" class="w-4 h-4" />
                        <span>Conferma</span>
                    </button>
                </div>

                <div class="border-t pt-3">
                    <label class="block text-sm font-medium">Motivo rifiuto (opzionale)</label>
                    <textarea wire:model="declineReason" class="w-full border rounded p-2" rows="3"></textarea>

                    <button wire:click="decline"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        <x-app-icon name="x-circle" class="w-4 h-4" />
                        <span>Rifiuta</span>
                    </button>
                </div>
            @endif
        </div>

    </div>

</div>
