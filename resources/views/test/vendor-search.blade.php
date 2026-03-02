<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ricerca Vendor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="max-w-5xl mx-auto p-6">
        <div class="flex items-end justify-between mb-4">
            <div>
                <h1 class="text-xl font-semibold">Ricerca Vendor</h1>
            </div>
        </div>

        @if (!empty($error))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded">
                {{ $error }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('test.vendor-search') }}"
            class="bg-white p-5 rounded-xl border space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-700">Categoria</label>
                    <select name="category_id" class="w-full border rounded-lg px-3 py-2"
                        onchange="this.form.method='GET'; this.form.submit();">
                        <option value="">Tutte</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(($old['category_id'] ?? '') == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-700">Servizio</label>
                    <select name="offering_id" class="w-full border rounded-lg px-3 py-2">
                        @foreach ($offerings as $off)
                            <option value="{{ $off->id }}" @selected(($old['offering_id'] ?? '') == $off->id)>
                                {{ $off->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm text-gray-700">Data evento</label>
                    <input type="date" name="date" class="w-full border rounded-lg px-3 py-2"
                        value="{{ $old['date'] ?? now()->toDateString() }}">
                </div>

                <div>
                    <label class="text-sm text-gray-700">Slot</label>
                    <select name="slot_slug" class="w-full border rounded-lg px-3 py-2">
                        @foreach ($slots as $s)
                            <option value="{{ $s->slug }}" @selected(($old['slot_slug'] ?? '') == $s->slug)>
                                {{ $s->label }}
                                @if ($s->start_time && $s->end_time)
                                    ({{ substr($s->start_time, 0, 5) }}-{{ substr($s->end_time, 0, 5) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm text-gray-700">Raggio (km)</label>
                    <input type="number" name="radius_km" class="w-full border rounded-lg px-3 py-2"
                        value="{{ $old['radius_km'] ?? 30 }}">
                </div>

                <div class="hidden md:block"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-700">Indirizzo</label>
                    <input type="text" name="address_line1" class="w-full border rounded-lg px-3 py-2"
                        value="{{ $old['address_line1'] ?? '' }}">
                </div>
                <div>
                    <label class="text-sm text-gray-700">Interno / Note</label>
                    <input type="text" name="address_line2" class="w-full border rounded-lg px-3 py-2"
                        value="{{ $old['address_line2'] ?? '' }}">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-gray-700">CAP</label>
                    <input type="text" name="postal_code" class="w-full border rounded-lg px-3 py-2"
                        value="{{ $old['postal_code'] ?? '' }}">
                </div>
                <div>
                    <label class="text-sm text-gray-700">Città</label>
                    <input type="text" name="city" class="w-full border rounded-lg px-3 py-2"
                        value="{{ $old['city'] ?? '' }}">
                </div>
                <div>
                    <label class="text-sm text-gray-700">Regione</label>
                    <input type="text" name="region" class="w-full border rounded-lg px-3 py-2"
                        value="{{ $old['region'] ?? '' }}">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Cerca vendor
                </button>

                <a href="{{ route('test.vendor-search') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 border">
                    Azzera
                </a>

                @if ($coords)
                    <div class="text-sm text-gray-600">
                        Coordinate: <strong>{{ $coords['lat'] }}</strong>, <strong>{{ $coords['lng'] }}</strong>
                    </div>
                @endif
            </div>
        </form>

        <div class="mt-6">
            <h2 class="text-lg font-medium mb-2">Risultati</h2>

            @if (empty($results))
                <div class="text-gray-500 text-sm">Nessun vendor trovato.</div>
            @else
                <div class="bg-white border rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3">Vendor</th>
                                <th class="text-left px-4 py-3">Distanza</th>
                                <th class="text-left px-4 py-3">Slot</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($results as $r)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">
                                            #{{ $r['vendor_account_id'] }} — {{ $r['company_name'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $r['distance_km'] }} km</td>
                                    <td class="px-4 py-3">
                                        {{ $r['slot']['label'] }}
                                        <span class="text-gray-500">
                                            ({{ $r['slot']['start_time'] ?? '' }}-{{ $r['slot']['end_time'] ?? '' }})
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</body>

</html>
