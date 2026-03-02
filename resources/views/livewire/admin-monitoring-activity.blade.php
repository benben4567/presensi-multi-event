<div>
    <x-ui.header title="Activity Log" />

    {{-- ── Search ─────────────────────────────────────────────────────── --}}
    <div class="mb-4 max-w-sm">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            placeholder="Cari deskripsi, event, atau model..."
        />
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────── --}}
    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Event</th>
                <th class="px-4 py-3">Deskripsi</th>
                <th class="px-4 py-3">Subjek</th>
                <th class="px-4 py-3">Oleh</th>
                <th class="px-4 py-3">Waktu</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($logs as $log)
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">
                        @php
                            $eventColor = match($log->event) {
                                'created' => 'green',
                                'updated' => 'blue',
                                'deleted' => 'red',
                                default   => 'gray',
                            };
                        @endphp
                        <x-ui.badge :color="$eventColor">{{ $log->event ?? '—' }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ $log->description }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        @if($log->subject_type)
                            <span class="font-mono text-xs">{{ class_basename($log->subject_type) }}</span>
                            @if($log->subject_id)
                                <span class="text-gray-400 dark:text-gray-500">#{{ $log->subject_id }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ $log->causer?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 tabular-nums whitespace-nowrap">
                        {{ $log->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                    </td>
                </tr>
            @empty
                <x-ui.table-empty message="Belum ada aktivitas tercatat." :colspan="5" />
            @endforelse
        </tbody>
    </x-ui.table>

    <x-ui.pagination :paginator="$logs" />
</div>
