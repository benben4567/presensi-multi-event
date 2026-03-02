<div>
    <x-ui.header :title="$userId ? 'Edit Pengguna' : 'Tambah Pengguna'">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.users.index') }}">Batal</x-ui.button>
            <x-ui.button wire:click="save" variant="primary">Simpan</x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    <div class="max-w-lg">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
                Informasi Pengguna
            </h3>

            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                <div class="pb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Nama <span class="text-red-500">*</span>
                    </label>
                    <x-ui.input wire:model="name" placeholder="Nama lengkap" />
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="py-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <x-ui.input type="email" wire:model="email" placeholder="email@contoh.com" />
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="py-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Password
                        @if($userId)
                            <span class="text-gray-400 text-xs font-normal ml-1">(kosongkan jika tidak diubah)</span>
                        @else
                            <span class="text-red-500">*</span>
                        @endif
                    </label>
                    <x-ui.input type="password" wire:model="password" placeholder="Minimal 8 karakter" />
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <x-ui.select wire:model="role" :disabled="$userId === auth()->id()">
                        <option value="admin">Admin</option>
                        <option value="operator">Operator</option>
                    </x-ui.select>
                    @if($userId === auth()->id())
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            Role tidak dapat diubah untuk akun sendiri.
                        </p>
                    @endif
                    @error('role')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
