<div class="space-y-6">
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Stato Finanziario Fornitore</h2>
        
        @if (session('billing_success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                {{ session('billing_success') }}
            </div>
        @endif

        @if (session('billing_error'))
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800 text-sm">
                {{ session('billing_error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="p-4 rounded-lg border border-slate-200 bg-slate-50 relative flex flex-col justify-center">
                <div class="text-sm font-medium text-slate-500">Modello di Pagamento</div>
                <div class="mt-1 flex items-center justify-between gap-4">
                    <div>
                        <div class="text-lg font-bold text-slate-900">
                            @php $isValidSub = $isSubscribed && $subscription && $subscription->valid(); @endphp
                            {{ $isValidSub ? 'Abbonamento Premium' : 'Commissione' }}
                        </div>
                        @if(!$isValidSub)
                            <div class="text-sm mt-0.5 {{ $vendorAccount->custom_commission_rate === null ? 'text-slate-500' : 'text-emerald-600 font-medium' }}">
                                Trattenuta: {{ $vendorAccount->custom_commission_rate !== null ? $vendorAccount->custom_commission_rate . '% (Personalizzata)' : 'Standard (da Categoria)' }}
                            </div>
                        @endif
                    </div>
                    @if(!$isValidSub)
                        <button type="button" wire:click="editCommission" class="shrink-0 inline-flex items-center gap-2 rounded bg-white border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                            <x-app-icon name="pencil" class="w-3.5 h-3.5" />
                            <span>Modifica</span>
                        </button>
                    @endif
                </div>
            </div>

            @if($isSubscribed && $subscription)
                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50 flex flex-col justify-center">
                    <div class="text-sm font-medium text-slate-500">Status su Stripe</div>
                    <div class="mt-1 text-lg font-bold {{ $subscription->onGracePeriod() ? 'text-amber-600' : 'text-emerald-600' }}">
                        @if($subscription->onGracePeriod())
                            In Scadenza (Rinnovo disdetto)
                        @else
                            Attivo
                        @endif
                    </div>
                </div>

                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50 flex flex-col justify-center">
                    <div class="text-sm font-medium text-slate-500">Prossimo Termine / Scadenza</div>
                    <div class="mt-1 text-lg font-bold text-slate-900">
                        {{ $subscription->ends_at ? $subscription->ends_at->format('d/m/Y H:i') : 'Auto-rinnovo' }}
                    </div>
                </div>
            @endif

            @if($paymentMethod)
                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50 flex flex-col justify-center">
                    <div class="text-sm font-medium text-slate-500">Metodo di Pagamento Default</div>
                    <div class="mt-1 text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="uppercase bg-slate-200 px-2 py-0.5 rounded text-sm text-slate-700">{{ $paymentMethod->card->brand }}</span>
                        <span class="font-mono">•••• {{ $paymentMethod->card->last4 }}</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">
                        Scade: {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($isSubscribed)
    <div class="bg-white border border-rose-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-2">Azioni Amministrative</h2>
        <p class="text-sm text-slate-500 mb-6">Come Amministratore, hai il controllo forzato degli abbonamenti ignorando le restrizioni utente.</p>
        
        <div class="flex flex-wrap items-center gap-4">
            @if(!$subscription->onGracePeriod())
                <button wire:click="cancelSubscription" 
                    onclick="confirm('Stai annullando il rinnovo automatico di questo Vendor. Continuerà a godere dei benefici Premium fino al termine del periodo che ha già pagato. Sei sicuro?') || event.stopImmediatePropagation()"
                    class="inline-flex items-center gap-2 rounded-lg bg-white border border-amber-500 px-4 py-2.5 text-sm font-semibold text-amber-600 hover:bg-amber-50 shadow-sm transition">
                    <x-app-icon name="no-symbol" class="w-4 h-4" />
                    <span>Disdici Rinnovo Automatico</span>
                </button>
            @endif

            <button wire:click="revokeSubscriptionNow" 
                onclick="confirm('ATTENZIONE: Stai stracciando l\'abbonamento con effetto immediato! Il fornitore tornerà a pagare le commissioni a partire da questo esatto istante e i benefici Stripe si annulleranno adesso. Procedere?') || event.stopImmediatePropagation()"
                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 border border-transparent px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 shadow-sm transition">
                <x-app-icon name="trash" class="w-4 h-4" />
                <span>Revoca d'Emergenza Immediata (Downgrade a Commissione)</span>
            </button>
        </div>
    </div>
    @else
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900 mb-1">Nessun Controllo Necessario</h2>
        <p class="text-sm text-slate-600">Questo fornitore è attualmente basato sul piano a <b>Commissione (20%)</b>. Non possiede abbonamenti attivi prelevabili da sospendere.</p>
    </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Storico Fatture Emesse (Stripe)</h2>
            <p class="mt-1 text-sm text-slate-500">Tutte le transazioni generate e incassate verso Party Legacy da questo account.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="pl-table w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-medium">Data Emissione</th>
                        <th class="px-6 py-3 font-medium">Importo</th>
                        <th class="px-6 py-3 font-medium">Stato Pagamento</th>
                        <th class="px-6 py-3 font-medium">Codice Stripe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 whitespace-nowrap">{{ $invoice->date()->format('d/m/Y - H:i') }}</td>
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $invoice->total() }}</td>
                            <td class="px-6 py-4">
                                @if($invoice->status === 'paid')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Pagata con Successo</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-600/20">{{ ucfirst($invoice->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4"><span class="font-mono text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded">{{ $invoice->id }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                <x-app-icon name="receipt-refund" class="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                Nessuna fattura trovata su Stripe per questo fornitore.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Modal Edit Commission --}}
    @if($editingCommission)
        <div class="fixed inset-0 z-[999] bg-slate-900/40 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-xl p-6 relative" @click.away="$wire.cancelCommission()">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                        <x-app-icon name="pencil-square" class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Modifica Commissione Personalizzata</h3>
                    </div>
                </div>

                <p class="text-sm text-slate-600 mb-6">
                    Se imposti un valore numerico in questo campo, il sistema applicherà questa percentuale <b>ignorando</b> le regole standard della Categoria di appartenenza del fornitore. Utile per accordi commerciali VIP.
                </p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-900 mb-1.5">Nuova Commissione Applicata (%)</label>
                    <div class="relative">
                        <input type="number" wire:model="newCommissionRate" min="0" max="100" class="w-full rounded-lg border-slate-300 pr-8 focus:border-slate-800 focus:ring-slate-800 placeholder:text-slate-400 font-medium text-slate-900" placeholder="Es. 15 (lascia vuoto per resettare)">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-slate-500 sm:text-sm">%</span>
                        </div>
                    </div>
                    @error('newCommissionRate') <span class="text-xs text-rose-600 mt-1.5 flex items-center gap-1"><x-app-icon name="exclamation-circle" class="w-3.5 h-3.5" /> {{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <button type="button" wire:click="cancelCommission"
                        class="inline-flex w-full sm:w-auto items-center justify-center gap-2 text-sm px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                        <span>Annulla</span>
                    </button>

                    <button type="button" wire:click="saveCommission"
                        class="inline-flex w-full sm:w-auto items-center justify-center gap-2 text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm">
                        <x-app-icon name="check" class="w-4 h-4" />
                        <span>Salva Configurazione</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
