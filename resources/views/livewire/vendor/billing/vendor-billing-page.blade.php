<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $showPlans ? 'Scegli il tuo piano' : 'Gestisci il tuo Abbonamento' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $showPlans ? 'Passa a Premium e azzera le commissioni sulle tue vendite.' : 'Visualizza lo stato del tuo piano tariffario e i prossimi rinnovi.' }}
            </p>
        </div>
        
        <div>
            @if($showPlans)
                <button wire:click="togglePlans" class="inline-flex items-center gap-1.5 py-2 px-4 rounded-lg text-sm font-medium bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 transition">
                    <x-app-icon name="arrow-left" class="w-4 h-4" />
                    Torna al Riepilogo
                </button>
            @else
                @if($isSubscribed && !$onGracePeriod)
                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800">
                        <x-app-icon name="check-circle" class="w-4 h-4" />
                        Abbonamento Attivo
                    </span>
                @elseif($onGracePeriod)
                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                        <x-app-icon name="exclamation-circle" class="w-4 h-4" />
                        In Scadenza
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-sm font-medium bg-slate-100 text-slate-600 border border-slate-200">
                        Piano a Commissione
                    </span>
                @endif
            @endif
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-3">
            <x-app-icon name="check-circle" class="w-5 h-5 shrink-0" />
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200 flex items-center gap-3">
            <x-app-icon name="exclamation-circle" class="w-5 h-5 shrink-0" />
            <p class="text-sm font-medium">{{ session('error') }}</p>
        </div>
    @endif


    @if(!$showPlans)
        <!-- ========================================== -->
        <!-- SCHERMATA DI RIEPILOGO (DASHBOARD)         -->
        <!-- ========================================== -->
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-full {{ $isSubscribed ? ($onGracePeriod ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600') : 'bg-indigo-100 text-indigo-600' }} flex items-center justify-center">
                        <x-app-icon name="{{ $isSubscribed ? 'star' : 'receipt-percent' }}" class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">
                            @if(!$isSubscribed)
                                Piano Base (Commissione)
                            @elseif($activePlan === 'MONTHLY')
                                Premium Mensile
                            @elseif($activePlan === 'YEARLY')
                                Premium Annuale
                            @endif
                        </h2>
                        <p class="text-sm text-slate-500">
                            @if(!$isSubscribed || $onGracePeriod)
                                @if($onGracePeriod)
                                    Abbonamento annullato. Nessun costo fisso fino al termine del periodo pagato, dopodiché pagherai il 20% di trattenuta sulle prenotazioni ricevute.
                                @else
                                    Nessun costo fisso. Paghi il 20% di trattenuta solo sulle prenotazioni ricevute.
                                @endif
                            @else
                                Zero commissioni sulle prenotazioni. Mantieni il 100% dei profitti.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="bg-slate-50 rounded-lg p-4 sm:p-6 border border-slate-100 mb-8">
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Stato</dt>
                            <dd class="mt-1 flex items-center gap-2 text-sm font-semibold {{ $isSubscribed ? ($onGracePeriod ? 'text-amber-600' : 'text-emerald-600') : 'text-slate-900' }}">
                                @if(!$isSubscribed)
                                    Attivo
                                @elseif($onGracePeriod)
                                    In scadenza (Rinnovo annullato)
                                @else
                                    Rinnovo automatico attivo
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Costo</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">
                                @if(!$isSubscribed)
                                    20% a prenotazione
                                @elseif($activePlan === 'MONTHLY')
                                    €49,00 / mese
                                @elseif($activePlan === 'YEARLY')
                                    €490,00 / anno
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">
                                {{ $onGracePeriod ? 'Benefici validi fino al' : 'Prossimo rinnovo' }}
                            </dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">
                                @if(!$isSubscribed)
                                    -
                                @else
                                    {{ $endsAt ?? 'N/D' }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-slate-100">
                    <button wire:click="togglePlans" class="inline-flex justify-center items-center px-4 py-2.5 rounded-lg text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 transition">
                        @if($isSubscribed && !$onGracePeriod)
                            Passa ad un altro piano
                        @else
                            Fai l'Upgrade a Premium
                        @endif
                    </button>

                    @if($isSubscribed && !$onGracePeriod)
                        <button wire:click="cancelSubscription" wire:loading.attr="disabled" onclick="confirm('Sei sicuro di voler disdire il rinnovo automatico? Manterrai i privilegi Premium fino alla fine del periodo, dopodiché tornerai a pagare le commissioni.') || event.stopImmediatePropagation()" class="inline-flex justify-center items-center px-4 py-2.5 rounded-lg text-sm font-medium bg-white border border-red-200 text-red-600 hover:bg-red-50 transition">
                            Disdici Rinnovo
                        </button>
                    @endif
                </div>
            </div>
        </div>

    @else
        <!-- ========================================== -->
        <!-- SCHERMATA DI VETRINA E ACQUISTO (PLANS)    -->
        <!-- ========================================== -->

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Commissione (Free) -->
            <div class="bg-white rounded-xl shadow-sm border {{ (!$isSubscribed || $onGracePeriod) ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-slate-200' }} overflow-hidden flex flex-col relative">
                @if(!$isSubscribed || $onGracePeriod)
                <div class="absolute top-0 right-0 p-3">
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-700">
                        Il tuo piano
                    </span>
                </div>
                @endif

                <div class="p-6 flex-1">
                    <h3 class="text-lg font-bold text-slate-900">Piano a Commissione</h3>
                    <div class="mt-4 flex items-baseline text-4xl font-extrabold text-slate-900">
                        20%
                        <span class="ml-1 text-xl font-medium text-slate-500">/prenotazione</span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">Ideale per iniziare senza costi fissi. Paghi solo quando ricevi una prenotazione.</p>
                </div>
                <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-end">
                    @if(!$isSubscribed || $onGracePeriod)
                        <button disabled class="w-full block text-center rounded-lg px-4 py-2.5 text-sm font-medium bg-indigo-50 text-indigo-700 cursor-not-allowed">
                            Attivo
                        </button>
                    @else
                        <!-- Un utente abbonato regolarmente per disdire deve usare il bottone apposito nel riepilogo -->
                        <button wire:click="togglePlans" class="w-full block text-center rounded-lg px-4 py-2.5 text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                            Torna al Riepilogo
                        </button>
                    @endif
                </div>
            </div>

            <!-- Mensile -->
            <div class="bg-white rounded-xl shadow-sm border {{ ($activePlan === 'MONTHLY' && !$onGracePeriod) ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-slate-200' }} overflow-hidden flex flex-col relative">
                @if($activePlan === 'MONTHLY' && !$onGracePeriod)
                <div class="absolute top-0 right-0 p-3">
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                        Attivo
                    </span>
                </div>
                @endif
                
                <div class="p-6 flex-1">
                    <h3 class="text-lg font-bold text-slate-900">Premium Mensile</h3>
                    <div class="mt-4 flex items-baseline text-4xl font-extrabold text-slate-900">
                        €49
                        <span class="ml-1 text-xl font-medium text-slate-500">/mese</span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">Zero commissioni sulle prenotazioni. Fatturato mensilmente. Disdici quando vuoi.</p>
                    <ul class="mt-6 space-y-3">
                        <li class="flex gap-2 text-sm text-slate-600">
                            <x-app-icon name="check" class="w-5 h-5 text-emerald-500 shrink-0" />
                            Trattieni il 100% dei guadagni
                        </li>
                        <li class="flex gap-2 text-sm text-slate-600">
                            <x-app-icon name="check" class="w-5 h-5 text-emerald-500 shrink-0" />
                            Visibilità prioritaria in ricerca
                        </li>
                    </ul>
                </div>
                <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-end">
                    @if($activePlan === 'MONTHLY' && !$onGracePeriod)
                        <button disabled class="w-full block text-center rounded-lg px-4 py-2.5 text-sm font-medium bg-emerald-50 text-emerald-700 cursor-not-allowed">
                            Attivo
                        </button>
                    @else
                        <button wire:click="subscribeToPlan('MONTHLY')" wire:loading.attr="disabled" class="w-full block text-center rounded-lg px-4 py-2.5 text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 transition">
                            {{ $isSubscribed ? 'Passa al Mensile' : 'Attiva Mensile' }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- Annuale -->
            <div class="bg-slate-900 rounded-xl shadow-lg border-none overflow-hidden flex flex-col relative text-white">
                @if($activePlan === 'YEARLY' && !$onGracePeriod)
                <div class="absolute top-0 right-0 p-3">
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded text-xs font-semibold bg-emerald-500 text-white">
                        Attivo
                    </span>
                </div>
                @else
                <div class="absolute top-0 right-0 p-3">
                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded text-xs font-semibold bg-indigo-500 text-white">
                        Risparmia 16%
                    </span>
                </div>
                @endif
                
                <div class="p-6 flex-1">
                    <h3 class="text-lg font-bold">Premium Annuale</h3>
                    <div class="mt-4 flex items-baseline text-4xl font-extrabold">
                        €490
                        <span class="ml-1 text-xl font-medium text-slate-400">/anno</span>
                    </div>
                    <p class="mt-4 text-sm text-slate-400">Tutti i vantaggi del piano Premium con due mesi gratis inclusi.</p>
                    <ul class="mt-6 space-y-3">
                        <li class="flex gap-2 text-sm text-slate-300">
                            <x-app-icon name="check" class="w-5 h-5 text-emerald-400 shrink-0" />
                            Tutto ciò che include il mensile
                        </li>
                        <li class="flex gap-2 text-sm text-slate-300">
                            <x-app-icon name="check" class="w-5 h-5 text-emerald-400 shrink-0" />
                            2 mesi gratuiti
                        </li>
                    </ul>
                </div>
                <div class="p-6 bg-slate-800 border-t border-slate-700 flex items-end">
                    @if($activePlan === 'YEARLY' && !$onGracePeriod)
                        <button disabled class="w-full block text-center rounded-lg px-4 py-2.5 text-sm font-medium bg-emerald-900 text-emerald-100 cursor-not-allowed border border-emerald-700">
                            Attivo
                        </button>
                    @else
                        <button wire:click="subscribeToPlan('YEARLY')" wire:loading.attr="disabled" class="w-full block text-center rounded-lg px-4 py-2.5 text-sm font-medium bg-indigo-500 text-white hover:bg-indigo-400 transition">
                            {{ $isSubscribed ? 'Passa all\'Annuale' : 'Attiva Annuale' }}
                        </button>
                    @endif
                </div>
            </div>

        </div>

    @endif
</div>
