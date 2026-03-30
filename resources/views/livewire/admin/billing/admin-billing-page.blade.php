<div class="space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Gestione Abbonamenti</h1>
            <p class="mt-1 text-sm text-slate-500">Riepilogo abbonamenti Vendor.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-center">

            <input type="text" class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                placeholder="Cerca vendor..." wire:model.live="search" />

            <select class="w-full rounded-lg border-slate-200 focus:border-slate-400 focus:ring-slate-400"
                wire:model.live="paymentModel">
                <option value="ALL">Tutti i modelli</option>
                <option value="COMMISSION">Solo a Commissione</option>
                <option value="SUBSCRIPTION">Solo Abbonati Premium</option>
            </select>

            <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                <x-app-icon name="arrow-path" class="w-4 h-4" />
                <span>Azzera filtri</span>
            </button>

            <div class="text-sm text-slate-500 lg:text-right">
                Totale:
                <span class="ml-2 font-semibold text-slate-900">
                    {{ $vendors->total() }}
                </span>
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden w-full"
         x-data="{
             syncTop() { this.$refs.bottom.scrollLeft = this.$refs.top.scrollLeft; },
             syncBottom() { this.$refs.top.scrollLeft = this.$refs.bottom.scrollLeft; },
             init() {
                 const observer = new ResizeObserver(() => {
                     if(this.$refs.table) {
                         this.$refs.dummy.style.width = this.$refs.table.scrollWidth + 'px';
                     }
                 });
                 if(this.$refs.table) observer.observe(this.$refs.table);
             }
         }">

        <!-- Scrollbar superiore (visibile solo su Desktop/Tablet dove c'è la tabella) -->
        <style>
            .thin-scrollbar-top { scrollbar-width: thin; scrollbar-color: #94a3b8 transparent; }
            .thin-scrollbar-top::-webkit-scrollbar { height: 8px; }
            .thin-scrollbar-top::-webkit-scrollbar-track { background: transparent; }
            .thin-scrollbar-top::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 9999px; }
        </style>
        <div class="hidden md:block overflow-x-auto overflow-y-hidden border-b border-slate-100 thin-scrollbar-top" x-ref="top" @scroll="syncTop" style="height: 10px;">
            <div x-ref="dummy" style="height: 1px;"></div>
        </div>

        <div class="table-wrap table-wrap-fade overflow-x-auto" x-ref="bottom" @scroll="syncBottom">
            <table class="pl-table pl-table-sticky-first" x-ref="table">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3">Vendor</th>
                        <th class="text-left px-6 py-3">Email</th>
                        <th class="text-left px-6 py-3">Modello</th>
                        <th class="text-left px-6 py-3">Piano Stripe</th>
                        <th class="text-left px-6 py-3">Scadenza</th>
                        <th class="text-right px-6 py-3">Azioni</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
                    @forelse($vendors as $vendor)
                        @php
                            $displayName =
                                $vendor->account_type === 'COMPANY' && !empty($vendor->company_name)
                                    ? $vendor->company_name
                                    : trim(($vendor->first_name ?? '') . ' ' . ($vendor->last_name ?? ''));

                            if ($displayName === '') {
                                $displayName = 'Vendor #' . $vendor->id;
                            }

                            $isSubscribed = $vendor->subscribed('default');
                            $subscription = $isSubscribed ? $vendor->subscription('default') : null;
                            $onGracePeriod = $isSubscribed && $subscription->onGracePeriod();

                            $isValidSub = $isSubscribed && $subscription->valid();
                            $paymentModelBadge = $isValidSub 
                                ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                                : 'bg-slate-50 border-slate-200 text-slate-800';
                            
                            $badgeText = $isValidSub ? 'Premium' : 'Commissione';

                            $stripePlanText = '-';
                            if ($isSubscribed) {
                                $monthlyPrice = config('services.stripe.price_monthly', env('STRIPE_PRICE_MONTHLY', ''));
                                $yearlyPrice = config('services.stripe.price_yearly', env('STRIPE_PRICE_YEARLY', ''));
                                
                                if ($monthlyPrice && $subscription->hasPrice($monthlyPrice)) {
                                    $stripePlanText = 'Mensile (€49)';
                                } elseif ($yearlyPrice && $subscription->hasPrice($yearlyPrice)) {
                                    $stripePlanText = 'Annuale (€490)';
                                } else {
                                    $stripePlanText = 'Piano Custom';
                                }
                            }

                            $expirationText = '-';
                            if ($isSubscribed) {
                                $expirationText = $subscription->ends_at 
                                    ? $subscription->ends_at->format('d/m/Y')
                                    : 'Auto-rinnovo';
                            }
                        @endphp

                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="shrink-0">
                                        @if ($vendor->profile_image_path)
                                            <img src="{{ route('media.public', ['path' => $vendor->profile_image_path]) }}" alt="Logo" class="w-10 h-10 rounded-full object-cover border border-slate-200">
                                        @else
                                            <div class="w-10 h-10 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center text-slate-400">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="font-semibold text-slate-900">
                                        {{ $displayName }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                {{ $vendor->user?->email ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-xs px-2 py-1 rounded-full border {{ $paymentModelBadge }}">
                                    {{ $badgeText }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                @if($onGracePeriod)
                                    <span class="text-amber-600 font-medium">{{ $stripePlanText }} (Disdetto)</span>
                                @else
                                    <span class="font-medium">{{ $stripePlanText }}</span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 text-slate-700">
                                {{ $expirationText }}
                            </td>

                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.vendors.edit', ['vendorAccount' => $vendor, 'activeTab' => 'billing']) }}"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    <x-app-icon name="eye" class="w-4 h-4" />
                                    <span>Gestisci</span>
                                </a>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                Nessun vendor trovato.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $vendors->links() }}
        </div>
    </div>
</div>
