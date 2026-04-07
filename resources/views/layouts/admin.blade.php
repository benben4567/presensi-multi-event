<!DOCTYPE html>
<html lang="id" x-data="themeToggle()" :class="{ 'dark': isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }} — Presensi</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Apply dark mode before paint -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">

{{-- ===== SIDEBAR (desktop fixed / mobile drawer) ===== --}}
<aside
    id="admin-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
    aria-label="Sidebar admin"
>
    <div class="flex flex-col h-full px-3 py-4 overflow-y-auto bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700">

        {{-- Brand --}}
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 ps-2 mb-6">
            <x-tabler-qrcode class="w-7 h-7 text-blue-600 dark:text-blue-400" />
            <span class="text-lg font-semibold text-gray-800 dark:text-white">Presensi QR</span>
        </a>

        {{-- Menu utama --}}
        <ul class="flex-1 space-y-1">
            <li>
                <a
                    href="{{ route('admin.dashboard') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.dashboard'),
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.dashboard'),
                    ])
                >
                    <x-tabler-layout-dashboard class="w-5 h-5 flex-shrink-0" />
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('admin.events.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.events.*'),
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.events.*'),
                    ])
                >
                    <x-tabler-calendar-event class="w-5 h-5 flex-shrink-0" />
                    <span>Event</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('admin.presensi.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.presensi.*'),
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.presensi.*'),
                    ])
                >
                    <x-tabler-clipboard-check class="w-5 h-5 flex-shrink-0" />
                    <span>Presensi</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('admin.laporan.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.laporan.*'),
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.laporan.*'),
                    ])
                >
                    <x-tabler-file-report class="w-5 h-5 flex-shrink-0" />
                    <span>Laporan</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('admin.users.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.users.*'),
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.users.*'),
                    ])
                >
                    <x-tabler-users class="w-5 h-5 flex-shrink-0" />
                    <span>Pengguna</span>
                </a>
            </li>

            <li>
                <a
                    href="{{ route('admin.print-templates.index') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.print-templates.*'),
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.print-templates.*'),
                    ])
                >
                    <x-tabler-file-certificate class="w-5 h-5 flex-shrink-0" />
                    <span>Template Cetak</span>
                </a>
            </li>
            <li>
                <a
                    href="{{ route('admin.panduan') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                        'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.panduan'),
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.panduan'),
                    ])
                >
                    <x-tabler-help-circle class="w-5 h-5 flex-shrink-0" />
                    <span>Panduan</span>
                </a>
            </li>

            {{-- Monitoring group --}}
            <li class="pt-4">
                <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    Monitoring
                </p>
                <ul class="space-y-1">
                    <li>
                        <a
                            href="{{ route('log-viewer.index') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                            target="_blank"
                        >
                            <x-tabler-bug class="w-5 h-5 flex-shrink-0" />
                            <span>Error Log</span>
                            <x-tabler-external-link class="w-3 h-3 ms-auto opacity-50" />
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('admin.monitoring.activity') }}"
                            @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.monitoring.activity'),
                                'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.monitoring.activity'),
                            ])
                        >
                            <x-tabler-list-details class="w-5 h-5 flex-shrink-0" />
                            <span>Activity Log</span>
                        </a>
                    </li>
                    <li>
                        <a
                            href="{{ route('admin.monitoring.queue') }}"
                            @class([
                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => request()->routeIs('admin.monitoring.queue'),
                                'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => !request()->routeIs('admin.monitoring.queue'),
                            ])
                        >
                            <x-tabler-list-check class="w-5 h-5 flex-shrink-0" />
                            <span>Queue Monitor</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

        {{-- User info at bottom --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
            <div class="flex items-center gap-2 px-3 py-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 dark:text-white truncate">
                        {{ Auth::user()->name ?? '-' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ Auth::user()->email ?? '' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        title="Keluar"
                        class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors"
                    >
                        <x-tabler-logout class="w-5 h-5" />
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

{{-- ===== MAIN WRAPPER ===== --}}
<div class="sm:ml-64">

    {{-- ===== HEADER ===== --}}
    <header class="sticky top-0 z-30 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-4 py-3 flex items-center justify-between gap-3">

            {{-- Mobile hamburger + page title --}}
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    data-drawer-target="admin-sidebar"
                    data-drawer-toggle="admin-sidebar"
                    aria-controls="admin-sidebar"
                    class="sm:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors"
                >
                    <x-tabler-menu-2 class="w-5 h-5" />
                    <span class="sr-only">Buka menu</span>
                </button>

                <h1 class="text-base font-semibold text-gray-800 dark:text-white">
                    {{ $title ?? config('app.name') }}
                </h1>
            </div>

            {{-- Dark mode toggle --}}
            <button
                type="button"
                @click="toggle()"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors"
                :title="isDark ? 'Mode terang' : 'Mode gelap'"
            >
                <x-tabler-sun class="w-5 h-5" x-show="isDark" />
                <x-tabler-moon class="w-5 h-5" x-show="!isDark" />
            </button>
        </div>
    </header>

    {{-- ===== PAGE CONTENT ===== --}}
    <main class="p-4 md:p-6">
        {{ $slot }}
    </main>
</div>

{{-- Global Livewire UI components --}}
<livewire:ui-toast />
<livewire:ui-confirm-dialog />

@livewireScripts

<script>
    function themeToggle() {
        return {
            isDark: localStorage.getItem('theme') === 'dark' ||
                (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
            toggle() {
                this.isDark = !this.isDark;
                localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            },
        };
    }
</script>

</body>
</html>
