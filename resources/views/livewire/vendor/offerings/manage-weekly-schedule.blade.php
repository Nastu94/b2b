<div>
    {{-- Flash message --}}
    @if (session('status'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Nessuno slot definito --}}
    @if($slots->isEmpty())
        <div class="text-center py-8 text-gray-400 text-sm border border-dashed rounded-lg">
            Nessuno slot trovato. Vai prima alla tab <strong>Slot</strong> e crea almeno uno slot.
        </div>
    @else
        <p class="text-sm text-gray-600 mb-4">
            Definisci quali giorni della settimana sono aperti per ogni slot.
        </p>

        {{-- Tabella template --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-700 border-b">
                            Slot
                        </th>
                        @foreach($days as $day => $label)
                            <th class="text-center px-3 py-3 font-medium text-gray-700 border-b">
                                {{ substr($label, 0, 3) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($slots as $slot)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $slot->label }}</div>
                                <div class="text-xs text-gray-400">{{ $slot->timeLabel() }}</div>
                            </td>

                            @foreach($days as $day => $dayLabel)
                                <td class="px-3 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        wire:model.live="schedule.{{ $slot->id }}.{{ $day }}.is_open"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Bottone salva --}}
        <div class="mt-4 flex items-center gap-3">
            <button
                wire:click="save"
                type="button"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
            >
                Salva template settimanale
            </button>
            <span class="text-xs text-gray-400">
                Le modifiche saranno visibili subito nel calendario disponibilità.
            </span>
        </div>

        <div class="mt-3 text-xs text-gray-500">
            Nota: l’anticipo minimo e l’eventuale cutoff si gestiscono nella sezione <strong>Lead Time</strong>.
        </div>
    @endif
</div>