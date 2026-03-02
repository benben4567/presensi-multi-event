<div>
    <x-ui.header title="Queue Monitor" />

    {{-- ── Filter status ──────────────────────────────────────────────── --}}
    <div class="mb-4 w-48">
        <x-ui.select wire:model.live="status">
            <option value="">— Semua Status —</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.select>
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────── --}}
    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Job</th>
                <th class="px-4 py-3">Queue</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Mulai</th>
                <th class="px-4 py-3">Durasi</th>
                <th class="px-4 py-3">Keterangan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($jobs as $job)
                @php
                    $statusColor = match($job->status) {
                        0 => 'blue',   // RUNNING
                        1 => 'green',  // SUCCEEDED
                        2 => 'red',    // FAILED
                        3 => 'yellow', // STALE
                        4 => 'gray',   // QUEUED
                        default => 'gray',
                    };
                    $statusLabel = $statuses[$job->status] ?? '—';

                    $duration = null;
                    if ($job->started_at && $job->finished_at) {
                        $duration = round($job->finished_at->diffInSeconds($job->started_at), 1) . 's';
                    }
                @endphp
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-sm text-gray-800 dark:text-white font-mono">
                            {{ class_basename($job->name ?? '—') }}
                        </p>
                        @if($job->retried)
                            <p class="text-xs text-yellow-600 dark:text-yellow-400">Dicoba ulang</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 font-mono">
                        {{ $job->queue ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <x-ui.badge :color="$statusColor">{{ $statusLabel }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 tabular-nums whitespace-nowrap">
                        {{ $job->started_at?->format('d/m/Y H:i:s') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 tabular-nums">
                        {{ $duration ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 max-w-xs truncate">
                        {{ $job->exception_message ?? '' }}
                    </td>
                </tr>
            @empty
                <x-ui.table-empty message="Belum ada job tercatat." :colspan="6" />
            @endforelse
        </tbody>
    </x-ui.table>

    <x-ui.pagination :paginator="$jobs" />
</div>
