<div class="min-h-screen bg-gray-50 flex items-center justify-center px-6">

    <div class="w-full max-w-6xl ">

        {{-- Header --}}
        <div class="text-center mb-8">
            {{ $logo }}

            <div class="mt-2 text-sm text-gray-600">
                {{ $title }}
            </div>
        </div>

        {{-- Card --}}
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mt-5 ">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <div class="mt-6 flex items-center justify-between text-xs text-gray-500">
            <a href="{{ route('home') }}"
               class="text-gray-600 hover:text-gray-900 transition">
                Torna alla home
            </a>

            <span>
                © {{ date('Y') }} {{ config('app.name', 'Party Legacy Management Engine') }}. All rights reserved.
            </span>
        </div>

    </div>

</div>