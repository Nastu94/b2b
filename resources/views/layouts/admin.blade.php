<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - Party Legacy</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="min-h-screen flex">

        @php
            $linkBase = "block px-3 py-2.5 rounded-lg transition";
            $linkIdle = "text-slate-600 hover:text-slate-900 hover:bg-slate-100";
            $linkActive = "bg-slate-200 text-slate-900 font-medium";
            $is = fn ($name) => request()->routeIs($name);
        @endphp

        <aside class="w-64 shrink-0 border-r border-slate-200 bg-white flex flex-col">
            <div class="p-6 border-b border-slate-200">
                <div class="text-lg font-semibold text-slate-900">Party Legacy</div>
                <div class="text-sm text-slate-500">Admin Panel</div>
            </div>

            <nav class="p-4 space-y-1 flex-1">
                <a href="{{ route('admin.dashboard') }}"
                   class="{{ $linkBase }} {{ $is('admin.dashboard') ? $linkActive : $linkIdle }}">
                    Dashboard
                </a>
            </nav>

            <div class="p-4 border-t border-slate-200 bg-slate-50">
                <div class="text-sm text-slate-600">{{ auth()->user()?->name }}</div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="w-full text-sm px-3 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
                <div>
                    <div class="text-xs text-slate-500">Admin</div>
                    <div class="text-lg font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</div>
                </div>
                <div class="text-sm text-slate-500">Party Legacy Management Engine</div>
            </header>

            <main class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>
        </div>

    </div>

    @livewireScripts
</body>
</html>