<div>
    <x-ui.header title="Laporan">
        @if($eventId && $sessionId)
            <x-slot:actions>
                <x-ui.button
                    href="{{ route('admin.laporan.export', ['event_id' => $eventId, 'session_id' => $sessionId]) }}"
                    variant="primary"
                >
                    <x-tabler-file-spreadsheet class="w-4 h-4" />
                    Unduh Excel
                </x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.header>

    {{-- ── Filter ─────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <div class="w-56">
            <x-ui.select wire:model.live="eventId">
                <option value="">— Pilih Event —</option>
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                @endforeach
            </x-ui.select>
        </div>

        @if($eventId)
            <div class="w-56">
                <x-ui.select wire:model.live="sessionId">
                    <option value="">— Pilih Hari —</option>
                    @foreach($sessions as $ses)
                        <option value="{{ $ses->id }}">{{ $ses->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        @endif
    </div>

    {{-- ── Instruction / empty state ──────────────────────────────────── --}}
    @if(!$eventId || !$sessionId)
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-6">
            Pilih event dan hari untuk melihat rekap presensi.
        </p>
    @else
        {{-- ── Table ──────────────────────────────────────────────────── --}}
        <x-ui.table>
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Peserta</th>
                    <th class="px-4 py-3">No HP</th>
                    <th class="px-4 py-3">Check-In</th>
                    <th class="px-4 py-3">Check-Out</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($rekap as $enrollment)
                    <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                            {{ $enrollment->participant->name }}
                        </td>
                        <td class="px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-300">
                            {{ $enrollment->participant->phone_e164 ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 tabular-nums">
                            {{ $enrollment->checkInAt?->format('H:i:s') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 tabular-nums">
                            {{ $enrollment->checkOutAt?->format('H:i:s') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($enrollment->checkInAt)
                                <x-ui.badge color="green">Hadir</x-ui.badge>
                            @else
                                <x-ui.badge color="gray">Tidak Hadir</x-ui.badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-ui.table-empty message="Belum ada peserta terdaftar." :colspan="5" />
                @endforelse
            </tbody>
        </x-ui.table>

        <x-ui.pagination :paginator="$rekap" />
    @endif
</div>
