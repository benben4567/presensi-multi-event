<div>
    <x-ui.header title="Presensi" />

    {{-- ── Filter ─────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <div class="w-56">
            <x-ui.select wire:model.live="eventId">
                <option value="">— Semua Event —</option>
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                @endforeach
            </x-ui.select>
        </div>

        @if($eventId)
            <div class="w-56">
                <x-ui.select wire:model.live="sessionId">
                    <option value="">— Semua Hari —</option>
                    @foreach($sessions as $ses)
                        <option value="{{ $ses->id }}">{{ $ses->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        @endif
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────── --}}
    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Peserta</th>
                <th class="px-4 py-3">Hari</th>
                <th class="px-4 py-3">Aksi</th>
                <th class="px-4 py-3">Waktu</th>
                <th class="px-4 py-3">Operator</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($logs as $log)
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800 dark:text-white">
                            {{ $log->eventParticipant?->participant?->name ?? '—' }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $log->eventParticipant?->participant?->phone_e164 ?? '' }}
                        </p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ $log->session?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if($log->action === \App\Enums\AttendanceAction::CheckIn)
                            <x-ui.badge color="green">Check-In</x-ui.badge>
                        @else
                            <x-ui.badge color="blue">Check-Out</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 tabular-nums">
                        {{ $log->scanned_at?->format('d/m/Y H:i:s') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ $log->operator?->name ?? '—' }}
                    </td>
                </tr>
            @empty
                <x-ui.table-empty message="Belum ada data presensi." :colspan="5" />
            @endforelse
        </tbody>
    </x-ui.table>

    <x-ui.pagination :paginator="$logs" />
</div>
