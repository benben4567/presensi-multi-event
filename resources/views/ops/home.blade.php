<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operator — {{ config('app.name') }}</title>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">

    <div class="text-center px-6 max-w-sm">
        <x-tabler-qrcode class="w-16 h-16 text-blue-500 dark:text-blue-400 mx-auto mb-6 opacity-60" />

        <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">
            Selamat datang, {{ Auth::user()->name }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Gunakan URL scan yang diberikan admin untuk memulai presensi.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                <x-tabler-logout class="w-4 h-4" />
                Keluar
            </button>
        </form>
    </div>

</body>
</html>
