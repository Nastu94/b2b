<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- ADMIN DASHBOARD --}}
            @role('admin')
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-bold mb-4">Admin Panel</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-100 rounded">
                            <p class="text-sm text-gray-600">Totale Vendor</p>
                            <p class="text-2xl font-bold">
                                {{ \App\Models\VendorAccount::count() }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-100 rounded">
                            <p class="text-sm text-gray-600">Totale Utenti</p>
                            <p class="text-2xl font-bold">
                                {{ \App\Models\User::count() }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-100 rounded">
                            <p class="text-sm text-gray-600">Vendor Attivi</p>
                            <p class="text-2xl font-bold">
                                {{ \App\Models\VendorAccount::where('status', 'ACTIVE')->count() }}
                            </p>
                        </div>
                    </div>
                </div>
            @endrole


            {{-- VENDOR DASHBOARD --}}
            @role('vendor')
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-4">Vendor Dashboard</h3>

                    <p class="mb-2">
                        Benvenuto,
                        <strong>{{ auth()->user()->name }}</strong>
                    </p>

                    <p class="text-sm text-gray-600">
                        Stato account:
                        <strong>
                            {{ auth()->user()->vendorAccount->status ?? 'N/A' }}
                        </strong>
                    </p>

                    <div class="mt-6">
                        <div class="p-4 bg-gray-100 rounded">
                            <p class="text-sm text-gray-600">Categoria</p>
                            <p class="text-lg font-semibold">
                                {{ auth()->user()->vendorAccount->category->name ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <strong>Servizi offerti:</strong>

                        @php
                            $vendorAccount = auth()->user()->vendorAccount;
                            $activeOfferings = $vendorAccount
                                ->offerings()
                                ->wherePivot('is_active', true)
                                ->orderBy('offerings.name')
                                ->get();
                        @endphp

                        @if ($activeOfferings->isEmpty())
                            <div class="text-sm text-gray-600 mt-2">
                                Nessun servizio selezionato.
                                <a href="{{ route('vendor.offerings') }}" class="underline text-indigo-600">Seleziona
                                    servizi</a>
                            </div>
                        @else
                            <ul class="list-disc ml-6 mt-2 text-sm">
                                @foreach ($activeOfferings as $offering)
                                    <li>{{ $offering->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <a href="{{ route('vendor.offerings') }}" class="underline text-indigo-600">
                        Gestisci servizi
                    </a>
                </div>
            @endrole

        </div>
    </div>
</x-app-layout>
