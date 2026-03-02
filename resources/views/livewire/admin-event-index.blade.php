<div>
    <x-ui.header title="Event">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.events.create') }}" variant="primary">
                <x-tabler-plus class="w-4 h-4" />
                Tambah Event
            </x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    {{-- Search --}}
    <div class="mb-4 max-w-sm">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            placeholder="Cari nama event..."
        />
    </div>

    {{-- Table --}}
    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Nama Event</th>
                <th class="px-4 py-3">Tanggal</th>
                <th class="px-4 py-3">Hari</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 w-32 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($events as $event)
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                        {{ $event->code ?? '—' }}
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                        {{ $event->name }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                        {{ $event->start_at->format('d M Y') }}
                        @if($event->start_at->toDateString() !== $event->end_at->toDateString())
                            – {{ $event->end_at->format('d M Y') }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                        {{ $event->sessions_count ?? $event->sessions()->count() }}
                    </td>
                    <td class="px-4 py-3">
                        <x-ui.badge :color="$event->status->color()">
                            {{ $event->status->label() }}
                        </x-ui.badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button
                                href="{{ route('admin.events.participants', $event) }}"
                                size="sm"
                            >
                                Peserta
                            </x-ui.button>
                            <x-ui.button
                                href="{{ route('admin.events.edit', $event) }}"
                                size="sm"
                            >
                                Edit
                            </x-ui.button>
                            <x-ui.button
                                wire:click="confirmDelete({{ $event->id }})"
                                variant="danger"
                                size="sm"
                            >
                                Hapus
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.table-empty message="Belum ada event." :colspan="6" />
            @endforelse
        </tbody>
    </x-ui.table>

    <x-ui.pagination :paginator="$events" />
</div>
