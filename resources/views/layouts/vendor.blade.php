{{-- resources/views/layouts/vendor.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? auth()->user()?->name }} - Party Legacy</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-screen overflow-hidden bg-slate-100 text-slate-800 antialiased">
    <div class="h-screen w-full flex overflow-hidden">

        {{-- Sidebar --}}
        @php
            $is = fn($name) => request()->routeIs($name);
        @endphp

        <aside class="w-64 shrink-0 pl-sidebar flex flex-col">

            {{-- Brand --}}
            <div class="p-6 border-b border-slate-200">
                <div class="text-lg pl-sidebar-brand">
                    Party Legacy
                </div>
                <div class="text-sm text-slate-500">
                    Vendor Panel
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="p-4 space-y-1 flex-1">
                <a href="{{ route('vendor.dashboard') }}"
                    class="pl-sidebar-link {{ $is('vendor.dashboard') ? 'pl-sidebar-link-active' : '' }} flex items-center gap-3">
                    <x-app-icon name="home" class="w-5 h-5" />
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('vendor.profile') }}"
                    class="pl-sidebar-link {{ $is('vendor.profile') ? 'pl-sidebar-link-active' : '' }} flex items-center gap-3">
                    <x-app-icon name="user-circle" class="w-5 h-5" />
                    <span>Profilo</span>
                </a>

                <a href="{{ route('vendor.offerings') }}"
                    class="pl-sidebar-link {{ $is('vendor.offerings') ? 'pl-sidebar-link-active' : '' }} flex items-center gap-3">
                    <x-app-icon name="briefcase" class="w-5 h-5" />
                    <span>Servizi</span>
                </a>

                <a href="{{ route('vendor.pricings') }}"
                    class="pl-sidebar-link {{ $is('vendor.pricings') ? 'pl-sidebar-link-active' : '' }} flex items-center gap-3">
                    <x-app-icon name="banknotes" class="w-5 h-5" />
                    <span>Listini</span>
                </a>

                <a href="{{ route('vendor.billing') }}"
                    class="pl-sidebar-link {{ $is('vendor.billing') ? 'pl-sidebar-link-active' : '' }} flex items-center gap-3">
                    <x-app-icon name="credit-card" class="w-5 h-5" />
                    <span>Abbonamento</span>
                </a>

                <a href="{{ route('vendor.bookings') }}"
                    class="pl-sidebar-link {{ $is('vendor.bookings') ? 'pl-sidebar-link-active' : '' }} flex items-center gap-3">
                    <x-app-icon name="calendar-days" class="w-5 h-5" />
                    <span>Prenotazioni</span>
                </a>
            </nav>

            {{-- User box --}}
            <div class="p-4 border-t border-slate-800">
                <div class="text-sm text-slate-300">
                    {{ auth()->user()?->name }}
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="pl-btn-logout inline-flex items-center gap-2">
                        <x-app-icon name="arrow-left-on-rectangle" class="w-5 h-5" />
                        <span>Logout</span>
                    </button>
                </form>

                <div class="mt-3 pt-3 border-t border-slate-700">
                    <a href="{{ route('vendor.billing', ['upgrade' => 1]) }}"
                        class="inline-flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                        <x-app-icon name="star" class="w-5 h-5" />
                        <span>Fai l'Upgrade</span>
                    </a>
                </div>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <header class="h-16 shrink-0 pl-topbar flex items-center justify-between px-6">
                <div>
                    <div class="text-xs pl-topbar-muted">Vendor</div>
                    <div class="text-lg pl-topbar-title">
                        {{ $title ?? 'Dashboard' }}
                    </div>
                </div>

                <div class="text-sm pl-topbar-muted">
                    Party Legacy Management Engine
                </div>
            </header>

            {{-- Content --}}
            <main class="flex-1 p-6 overflow-y-auto scrollbar-hide">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>

        </div>
    </div>

    @livewireScripts
</body>

</html>
