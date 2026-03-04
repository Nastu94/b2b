<div>
    {{-- Flash message --}}
    @if (session('status'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('status') }}
        </div>
    @endif

    <p class="text-sm text-gray-600 mb-4">
        Imposta il preavviso minimo richiesto per ogni giorno della settimana.
        Il cutoff è l'ora limite entro cui accettare prenotazioni per quel giorno.
    </p>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-700 border-b">Giorno</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-700 border-b">Preavviso minimo (ore)</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-700 border-b">Cutoff (ora limite)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($days as $day => $label)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $label }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <input
                                type="number"
                                wire:model="leadTimes.{{ $day }}.min_notice_hours"
                                min="0"
                                max="720"
                                class="w-20 text-center border border-gray-300 rounded-lg px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            @error("leadTimes.{$day}.min_notice_hours")
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </td>
                        <td class="px-4 py-3 text-center">
                            <input
                                type="time"
                                wire:model="leadTimes.{{ $day }}.cutoff_time"
                                class="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            @error("leadTimes.{$day}.cutoff_time")
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex items-center gap-3">
        <button
            wire:click="save"
            type="button"
            class="px-4 py-2 bg-slate-600 text-white text-sm font-medium rounded-lg hover:bg-slate-700"
        >
            Salva preavvisi
        </button>
        <span class="text-xs text-gray-400">
            Es. Sabato: 72h — Lunedì: 48h
        </span>
    </div>
</div>