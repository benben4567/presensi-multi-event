<div class="space-y-6 max-w-xl">
    <x-ui.header title="Profil" />

    {{-- ── Informasi Profil ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
            Informasi Profil
        </h3>

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Nama
                </label>
                <x-ui.input wire:model="name" required autofocus autocomplete="name" />
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Email
                </label>
                <x-ui.input type="email" wire:model="email" required autocomplete="username" />
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                @php $user = auth()->user(); @endphp
                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="text-sm mt-2 text-gray-600 dark:text-gray-400">
                        Alamat email Anda belum terverifikasi.
                        <button type="button" wire:click="resendVerification" class="underline hover:no-underline">
                            Kirim ulang email verifikasi.
                        </button>
                    </p>
                @endif
            </div>

            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateProfile" variant="primary">
                <span wire:loading.remove wire:target="updateProfile">Simpan</span>
                <span wire:loading wire:target="updateProfile">Menyimpan...</span>
            </x-ui.button>
        </form>
    </div>

    {{-- ── Ubah Kata Sandi ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
            Ubah Kata Sandi
        </h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 -mt-3 mb-4">
            Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.
        </p>

        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Kata Sandi Saat Ini
                </label>
                <x-ui.input type="password" wire:model="current_password" autocomplete="current-password" />
                @error('current_password')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Kata Sandi Baru
                </label>
                <x-ui.input type="password" wire:model="password" autocomplete="new-password" />
                @error('password')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Konfirmasi Kata Sandi
                </label>
                <x-ui.input type="password" wire:model="password_confirmation" autocomplete="new-password" />
            </div>

            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updatePassword" variant="primary">
                <span wire:loading.remove wire:target="updatePassword">Simpan</span>
                <span wire:loading wire:target="updatePassword">Menyimpan...</span>
            </x-ui.button>
        </form>
    </div>

    {{-- ── Hapus Akun ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
            Hapus Akun
        </h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 -mt-3 mb-4">
            Setelah akun Anda dihapus, semua data dan sumber dayanya akan dihapus secara permanen.
        </p>

        <x-ui.button wire:click="openDeleteForm" variant="danger">Hapus Akun</x-ui.button>
    </div>

    {{-- ── Modal konfirmasi hapus akun ── --}}
    <div
        x-data="{ show: $wire.entangle('showDeleteForm') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @keydown.escape.window="$wire.cancelDeleteForm()"
        style="display: none"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md"
            @click.stop
        >
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <x-tabler-alert-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Yakin ingin menghapus akun Anda?</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Semua data dan sumber daya akan dihapus permanen. Masukkan kata sandi Anda untuk mengonfirmasi.
                    </p>
                </div>
            </div>

            <div class="mb-4">
                <x-ui.input type="password" wire:model="deletePassword" placeholder="Kata sandi" />
                @error('deletePassword')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <x-ui.button wire:click="cancelDeleteForm">Batal</x-ui.button>
                <x-ui.button
                    wire:click="deleteAccount"
                    wire:loading.attr="disabled"
                    wire:target="deleteAccount"
                    variant="danger"
                >
                    Hapus Akun
                </x-ui.button>
            </div>
        </div>
    </div>
</div>
