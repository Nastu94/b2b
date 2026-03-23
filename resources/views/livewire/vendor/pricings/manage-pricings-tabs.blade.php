<div class="space-y-6">
    {{-- Header sezione --}}
    <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            Listini
        </h1>

        <p class="text-sm leading-6 text-slate-500">
            Configura il prezzo base del servizio, aggiungi regole commerciali e verifica il comportamento del listino.
        </p>
    </div>

    {{-- Selettore servizio --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <label for="selectedOfferingId" class="mb-2 block text-sm font-medium text-slate-700">
                    Servizio da configurare
                </label>

                <select id="selectedOfferingId" wire:model.live="selectedOfferingId"
                    class="block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-400 focus:ring-slate-300">
                    <option value="">-- Seleziona un servizio --</option>

                    @foreach ($offerings as $offering)
                        <option value="{{ $offering->id }}">
                            {{ $offering->name }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-2 text-xs text-slate-500">
                    Scegli il servizio su cui vuoi lavorare prima di modificare listino, regole o simulazione.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                <div class="text-sm font-semibold text-slate-800">
                    Stato configurazione
                </div>

                @if ($this->selectedOffering)
                    <div class="mt-3 space-y-2 text-sm text-slate-600">
                        <div>
                            <span class="font-medium text-slate-700">Servizio:</span>
                            {{ $this->selectedOffering->name }}
                        </div>

                        <div>
                            <span class="font-medium text-slate-700">Listino base:</span>

                            @if ($this->pricingStatus === 'active')
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    Configurato
                                </span>
                            @elseif ($this->pricingStatus === 'inactive')
                                <span
                                    class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    Inattivo
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                    Da creare
                                </span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-3 text-sm leading-6 text-slate-500">
                        Seleziona un servizio per iniziare a configurare il relativo listino.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white shadow rounded-lg">
        @php
            $tabs = [
                'base-pricing' => [
                    'label' => 'Listino base',
                    'icon' => 'banknotes',
                ],
                'pricing-rules' => [
                    'label' => 'Regole',
                    'icon' => 'adjustments-horizontal',
                ],
                'pricing-summary' => [
                    'label' => 'Riepilogo',
                    'icon' => 'document-text',
                ],
                'pricing-simulation' => [
                    'label' => 'Simulazione',
                    'icon' => 'calculator',
                ],
            ];
        @endphp

        <div class="border-b border-slate-200 px-6">
            <nav class="-mb-px flex flex-wrap gap-8" aria-label="Tabs">
                @foreach ($tabs as $tabKey => $tab)
                    <button type="button" wire:click="selectTab('{{ $tabKey }}')"
                        class="inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition {{ $activeTab === $tabKey ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                        <x-app-icon :name="$tab['icon']" class="w-4 h-4" />
                        <span>{{ $tab['label'] }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Contenuto tab --}}
        <div class="p-6">
            @if ($selectedOfferingId === null)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                    Per continuare, seleziona prima un servizio dalla lista qui sopra.
                </div>
            @else
                @switch($activeTab)
                    @case('base-pricing')
                        <livewire:vendor.pricings.tabs.pricing-base-tab :vendor-account-id="$vendorAccount->id" :offering-id="$selectedOfferingId" :pricing-id="$selectedPricingId"
                            :key="'pricing-base-tab-' .
                                $vendorAccount->id .
                                '-' .
                                $selectedOfferingId .
                                '-' .
                                ($selectedPricingId ?? 'new')" />
                    @break

                    @case('pricing-rules')
                        <livewire:vendor.pricings.tabs.pricing-rules-tab :pricing-id="$selectedPricingId" :key="'pricing-rules-tab-' . ($selectedPricingId ?? 'missing')" />
                    @break

                    @case('pricing-summary')
                        <livewire:vendor.pricings.tabs.pricing-summary-tab :pricing-id="$selectedPricingId" :key="'pricing-summary-tab-' . ($selectedPricingId ?? 'missing')" />
                    @break

                    @case('pricing-simulation')
                        <livewire:vendor.pricings.tabs.pricing-simulation-tab :pricing-id="$selectedPricingId" :key="'pricing-simulation-tab-' . ($selectedPricingId ?? 'missing')" />
                    @break
                @endswitch
            @endif
        </div>
    </div>
</div>
