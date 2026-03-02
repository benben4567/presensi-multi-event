<!DOCTYPE html>
<html lang="id" x-data="themeToggle()" :class="{ 'dark': isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Presensi' }} — {{ config('app.name') }}</title>

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

{{-- ===== HEADER OPERATOR (minimal) ===== --}}
<header class="sticky top-0 z-30 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-4 py-3 flex items-center justify-between gap-3">

        {{-- Breadcrumb: Presensi / Nama Event --}}
        <div class="flex items-center gap-2 min-w-0">
            <x-tabler-qrcode class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" />
            <div class="flex items-center gap-1 text-sm font-medium min-w-0">
                <span class="text-gray-500 dark:text-gray-400 flex-shrink-0">Presensi</span>
                @isset($eventName)
                    <span class="text-gray-400 dark:text-gray-600 flex-shrink-0">/</span>
                    <span class="text-gray-800 dark:text-white truncate">{{ $eventName }}</span>
                @endisset
            </div>
        </div>

        {{-- Right side: Hari aktif + mode toggle + dark toggle + keluar --}}
        <div class="flex items-center gap-2 flex-shrink-0">

            {{-- Hari aktif --}}
            @isset($hariAktif)
                <span class="hidden sm:inline-flex items-center gap-1 px-2 py-1 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 text-xs font-medium">
                    <x-tabler-calendar class="w-3.5 h-3.5" />
                    {{ $hariAktif }}
                </span>
            @endisset

            {{-- Scan / Manual toggle --}}
            @isset($scanRoute, $manualRoute)
                <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden text-xs font-medium">
                    <a
                        href="{{ $scanRoute }}"
                        @class([
                            'flex items-center gap-1 px-2.5 py-1.5 transition-colors',
                            'bg-blue-600 text-white' => $activeMode === 'scan',
                            'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $activeMode !== 'scan',
                        ])
                    >
                        <x-tabler-scan class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Scan</span>
                    </a>
                    <a
                        href="{{ $manualRoute }}"
                        @class([
                            'flex items-center gap-1 px-2.5 py-1.5 transition-colors',
                            'bg-blue-600 text-white' => $activeMode === 'manual',
                            'bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $activeMode !== 'manual',
                        ])
                    >
                        <x-tabler-keyboard class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Manual</span>
                    </a>
                </div>
            @endisset

            {{-- Panduan --}}
            <a
                href="{{ route('ops.panduan') }}"
                title="Panduan"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors"
            >
                <x-tabler-help-circle class="w-5 h-5" />
                <span class="sr-only">Panduan</span>
            </a>

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

            {{-- Keluar --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    title="Keluar"
                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors"
                >
                    <x-tabler-logout class="w-5 h-5" />
                    <span class="sr-only">Keluar</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Hari aktif — mobile only --}}
    @isset($hariAktif)
        <div class="sm:hidden px-4 pb-2 text-xs text-gray-500 dark:text-gray-400">
            <x-tabler-calendar class="w-3.5 h-3.5 inline-block me-1" />
            {{ $hariAktif }}
        </div>
    @endisset
</header>

{{-- ===== PAGE CONTENT ===== --}}
<main class="p-4 md:p-6 max-w-2xl mx-auto">
    {{ $slot }}
</main>

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
