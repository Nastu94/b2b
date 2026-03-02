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
            Puoi anche impostare l'anticipo minimo e un orario limite di prenotazione.
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

                        {{-- Riga lead time: visibile solo se almeno un giorno è aperto --}}
                        @php
                            $hasOpenDay = collect(array_keys($days))->contains(
                                fn($d) => !empty($schedule[$slot->id][$d]['is_open'])
                            );
                        @endphp

                        @if($hasOpenDay)
                            <tr class="bg-indigo-50">
                                <td class="px-4 py-2 text-xs text-indigo-700 font-medium">
                                    Anticipo minimo (ore)
                                </td>
                                @foreach($days as $day => $dayLabel)
                                    <td class="px-3 py-2 text-center">
                                        @if(!empty($schedule[$slot->id][$day]['is_open']))
                                            <input
                                                type="number"
                                                wire:model="schedule.{{ $slot->id }}.{{ $day }}.min_notice_hours"
                                                min="0"
                                                max="720"
                                                class="w-14 text-center border border-gray-300 rounded px-1 py-1 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                            />
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            <tr class="bg-indigo-50 border-b border-indigo-100">
                                <td class="px-4 py-2 text-xs text-indigo-700 font-medium">
                                    Cutoff (ora limite)
                                </td>
                                @foreach($days as $day => $dayLabel)
                                    <td class="px-3 py-2 text-center">
                                        @if(!empty($schedule[$slot->id][$day]['is_open']))
                                            <input
                                                type="time"
                                                wire:model="schedule.{{ $slot->id }}.{{ $day }}.cutoff_time"
                                                class="w-20 border border-gray-300 rounded px-1 py-1 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                            />
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif
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
    @endif
</div>