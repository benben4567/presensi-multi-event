<div>
    <x-ui.header title="Template Cetak">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.print-templates.create') }}" variant="primary">
                Tambah Template
            </x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3 text-left">Nama</th>
                <th class="px-4 py-3 text-left">Ukuran</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Dibuat</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($templates as $template)
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $template->name }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                        {{ $template->page_width_mm }} × {{ $template->page_height_mm }} mm
                    </td>
                    <td class="px-4 py-3">
                        @if ($template->is_active)
                            <x-ui.badge color="green">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge color="gray">Nonaktif</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ $template->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button href="{{ route('admin.print-templates.edit', $template) }}" size="sm">
                                Edit
                            </x-ui.button>
                            <x-ui.button wire:click="toggleActive({{ $template->id }})" size="sm">
                                {{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-ui.button>
                            <x-ui.button
                                wire:click="delete({{ $template->id }})"
                                wire:confirm="Hapus template ini?"
                                size="sm"
                                variant="danger"
                            >
                                Hapus
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.table-empty colspan="5" message="Belum ada template cetak." />
            @endforelse
        </tbody>
    </x-ui.table>
</div>
