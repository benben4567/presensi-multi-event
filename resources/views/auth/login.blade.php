<x-guest-layout>
    <div class="w-full max-w-md mx-auto">

        {{-- Brand --}}
        <div class="flex flex-col items-center mb-8">
            <div class="flex items-center gap-2 mb-1">
                <x-tabler-qrcode class="w-9 h-9 text-blue-600 dark:text-blue-400" />
                <span class="text-2xl font-semibold text-gray-800 dark:text-white">
                    {{ config('app.name') }}
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Masuk ke akun Anda</p>
        </div>

        {{-- Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">

            {{-- Session Status --}}
            @if (session('status'))
                <x-ui.alert type="success" class="mb-4">
                    {{ session('status') }}
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Alamat Email
                    </label>
                    <x-ui.input
                        id="email"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@contoh.com"
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Kata Sandi
                    </label>
                    <x-ui.input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    />
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember Me + Forgot Password --}}
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                        >
                        <span class="text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a
                            href="{{ route('password.request') }}"
                            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:underline"
                        >
                            Lupa kata sandi?
                        </a>
                    @endif
                </div>

                {{-- Submit --}}
                <x-ui.button variant="primary" type="submit" class="w-full justify-center">
                    <x-tabler-login class="w-4 h-4" />
                    Masuk
                </x-ui.button>
            </form>
        </div>
    </div>
</x-guest-layout>
