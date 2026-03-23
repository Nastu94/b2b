<div class="bg-white rounded-lg divide-y divide-slate-200">

    @if (session('success'))
        <div class="p-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md mb-4">
            {{ session('success') }}
        </div>
    @endif

    @forelse($bookings as $b)
        <div class="p-6 flex items-start justify-between">

            <div class="space-y-4 flex-1">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-slate-900">
                        Booking #{{ $b->id }}
                    </h3>

                    @if ($b->status === 'PENDING_VENDOR_CONFIRMATION')
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                            <x-app-icon name="clock" class="w-3.5 h-3.5" />
                            <span>In attesa</span>
                        </span>
                    @elseif($b->status === 'CONFIRMED')
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                            <x-app-icon name="check-circle" class="w-3.5 h-3.5" />
                            <span>Confermata</span>
                        </span>
                    @elseif($b->status === 'DECLINED')
                        <span
                            class="inline-flex items-center gap-1 rounded-md bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                            <x-app-icon name="x-circle" class="w-3.5 h-3.5" />
                            <span>Rifiutata</span>
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <div class="text-slate-500">Data evento</div>
                        <div class="font-medium text-slate-900">{{ $b->event_date?->format('d/m/Y') ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-slate-500">Ordine PS</div>
                        <div class="font-medium text-slate-900">{{ $b->prestashop_order_id }}</div>
                    </div>

                    <div>
                        <div class="text-slate-500">Linea</div>
                        <div class="font-medium text-slate-900">{{ $b->prestashop_order_line_id }}</div>
                    </div>

                    <div>
                        <div class="text-slate-500">Totale</div>
                        <div class="font-medium text-slate-900">
                            € {{ number_format($b->total_amount ?? 0, 2, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-500">Vendor</div>
                        <div class="font-medium text-slate-900">
                            {{ $b->vendorAccount?->company_name ?? '#' . $b->vendor_account_id }}
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-500">Pagamento</div>
                        <div class="font-medium text-slate-900">
                            {{ $b->paid_at ? $b->paid_at->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="ml-6 flex flex-col gap-2 items-end">
                <a href="{{ route('admin.bookings.show', $b) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                    <x-app-icon name="eye" class="w-4 h-4" />
                    <span>Apri</span>
                </a>

                @if (in_array($b->status, ['DECLINED'], true))
                    <button type="button"
                        onclick="if(confirm('Eliminare la prenotazione #{{ $b->id }}?')) { @this.deleteBooking({{ $b->id }}) }"
                        class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                        <x-app-icon name="trash" class="w-4 h-4" />
                        <span>Elimina</span>
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="p-8 text-center text-slate-500 text-sm">
            Nessuna prenotazione trovata.
        </div>
    @endforelse

    @if ($bookings->hasPages())
        <div class="p-5 border-t border-slate-200">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
