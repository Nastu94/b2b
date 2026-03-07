<div class="p-6">
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    Prenotazione #{{ $b->id }}
                </h1>
                <p class="mt-1 text-slate-600">
                    Stato: <span class="font-medium text-slate-900">{{ $b->status }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.bookings', ['tab' => 'all']) }}"
                   class="inline-flex items-center rounded-md bg-slate-100 px-4 py-2 text-sm font-medium text-slate-800
                          hover:bg-slate-200 transition">
                    ← Indietro
                </a>

                <button
                    type="button"
                    onclick="if(confirm('Eliminare la prenotazione #{{ $b->id }}?')) { @this.deleteBooking() }"
                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white
                           hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 transition">
                    Elimina
                </button>
            </div>
        </div>

        {{-- Card dati principali --}}
        <div class="bg-white shadow rounded-lg p-6 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <div class="text-slate-500">Data evento</div>
                    <div class="font-medium text-slate-900">{{ $b->event_date?->format('d/m/Y') ?? '—' }}</div>
                </div>

                <div>
                    <div class="text-slate-500">Totale</div>
                    <div class="font-medium text-slate-900">
                        € {{ number_format($b->total_amount ?? 0, 2, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="text-slate-500">Pagamento</div>
                    <div class="font-medium text-slate-900">
                        {{ $b->paid_at ? $b->paid_at->format('d/m/Y H:i') : '—' }}
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <div class="text-slate-500">Ordine PrestaShop</div>
                    <div class="font-medium text-slate-900">{{ $b->prestashop_order_id }}</div>
                </div>

                <div>
                    <div class="text-slate-500">Linea ordine</div>
                    <div class="font-medium text-slate-900">{{ $b->prestashop_order_line_id }}</div>
                </div>

                <div>
                    <div class="text-slate-500">Vendor</div>
                    <div class="font-medium text-slate-900">
                        {{ $b->vendorAccount?->business_name ?? ('#'.$b->vendor_account_id) }}
                    </div>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="border-t border-slate-200 pt-6">
                <div class="text-sm font-medium text-slate-900">Cliente</div>
                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    <div>
                        <div class="text-slate-500">Nome</div>
                        <div class="font-medium text-slate-900">{{ data_get($b->customer_data, 'name', '—') }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Email</div>
                        <div class="font-medium text-slate-900">{{ data_get($b->customer_data, 'email', '—') }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Telefono</div>
                        <div class="font-medium text-slate-900">{{ data_get($b->customer_data, 'phone', '—') }}</div>
                    </div>
                </div>
            </div>

            {{-- Lock/slot info --}}
            <div class="border-t border-slate-200 pt-6">
                <div class="text-sm font-medium text-slate-900">Slot / Lock</div>

                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    <div>
                        <div class="text-slate-500">SlotLock ID</div>
                        <div class="font-medium text-slate-900">{{ $b->slot_lock_id ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Lock status</div>
                        <div class="font-medium text-slate-900">{{ $b->slotLock?->status ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Lock attiva</div>
                        <div class="font-medium text-slate-900">
                            {{ is_null($b->slotLock) ? '—' : ($b->slotLock->is_active ? 'Sì' : 'No') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-500">Vendor slot</div>
                        <div class="font-medium text-slate-900">
                            {{ $b->vendorSlot?->name ?? ('#'.$b->vendor_slot_id) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-500">Vendor slot ID</div>
                        <div class="font-medium text-slate-900">{{ $b->vendor_slot_id ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-slate-500">Hold token</div>
                        <div class="font-medium text-slate-900 break-all">
                            {{ $b->slotLock?->hold_token ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Audit --}}
            <div class="border-t border-slate-200 pt-6">
                <div class="text-sm font-medium text-slate-900">Audit</div>
                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    <div>
                        <div class="text-slate-500">Confermata il</div>
                        <div class="font-medium text-slate-900">
                            {{ $b->confirmed_at ? $b->confirmed_at->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-slate-500">Rifiutata il</div>
                        <div class="font-medium text-slate-900">
                            {{ $b->declined_at ? $b->declined_at->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-slate-500">Eliminata</div>
                        <div class="font-medium text-slate-900">
                            {{ $b->deleted_at ? $b->deleted_at->format('d/m/Y H:i') : 'No' }}
                        </div>
                    </div>
                </div>

                @if($b->decline_reason || $b->vendor_notes)
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <div class="text-slate-500">Motivo rifiuto</div>
                            <div class="font-medium text-slate-900 whitespace-pre-line">
                                {{ $b->decline_reason ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-slate-500">Note vendor</div>
                            <div class="font-medium text-slate-900 whitespace-pre-line">
                                {{ $b->vendor_notes ?? '—' }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>