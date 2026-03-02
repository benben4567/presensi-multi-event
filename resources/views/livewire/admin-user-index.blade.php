<div>
    <x-ui.header title="Pengguna">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.users.create') }}" variant="primary">
                <x-tabler-plus class="w-4 h-4" />
                Tambah Pengguna
            </x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    {{-- Search --}}
    <div class="mb-4 max-w-sm">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            placeholder="Cari nama atau email..."
        />
    </div>

    {{-- Table --}}
    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3 w-32 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($users as $user)
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                        {{ $user->name }}
                        @if($user->id === auth()->id())
                            <span class="ml-1.5 text-xs text-gray-400 dark:text-gray-500">(Anda)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                        {{ $user->email }}
                    </td>
                    <td class="px-4 py-3">
                        @foreach($user->roles as $role)
                            <x-ui.badge :color="$role->name === 'admin' ? 'blue' : 'gray'">
                                {{ ucfirst($role->name) }}
                            </x-ui.badge>
                        @endforeach
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button
                                href="{{ route('admin.users.edit', $user) }}"
                                size="sm"
                            >
                                Edit
                            </x-ui.button>
                            @if($user->id !== auth()->id())
                                <x-ui.button
                                    wire:click="confirmDelete({{ $user->id }})"
                                    variant="danger"
                                    size="sm"
                                >
                                    Hapus
                                </x-ui.button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.table-empty message="Belum ada pengguna." :colspan="4" />
            @endforelse
        </tbody>
    </x-ui.table>

    <x-ui.pagination :paginator="$users" />
</div>
