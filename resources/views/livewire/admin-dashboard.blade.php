<div wire:poll.30s>

    <x-ui.header title="Dashboard" />

    {{-- ── 1. Snapshot Cards ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Event aktif hari ini --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30">
                    <x-tabler-calendar-event class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Event Aktif</p>
            </div>
            <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $activeEvents->count() }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">event berlangsung hari ini</p>
        </div>

        {{-- Check-in hari ini --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-green-50 dark:bg-green-900/30">
                    <x-tabler-user-check class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Check-in</p>
            </div>
            <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $snapshotCheckins }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">presensi masuk hari ini</p>
        </div>

        {{-- Peringatan hari ini --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-yellow-50 dark:bg-yellow-900/30">
                    <x-tabler-alert-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                </div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Peringatan</p>
            </div>
            <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $snapshotWarnings }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">scan dengan peringatan</p>
        </div>

        {{-- Ditolak hari ini --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 rounded-lg bg-red-50 dark:bg-red-900/30">
                    <x-tabler-ban class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Ditolak</p>
            </div>
            <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ $snapshotRejected }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">scan ditolak hari ini</p>
        </div>

    </div>

    {{-- ── 2. Grafik Trend + Top 5 Ditolak ───────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        {{-- Grafik Trend per Jam --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-1">Trend Scan per Jam</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                {{ now()->translatedFormat('l, d F Y') }} — 24 jam
            </p>

            {{-- Legend --}}
            <div class="flex items-center gap-4 mb-3">
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm bg-green-500 inline-block"></span>Diterima
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm bg-yellow-500 inline-block"></span>Peringatan
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="w-2.5 h-2.5 rounded-sm bg-red-500 inline-block"></span>Ditolak
                </span>
            </div>

            {{-- Bars --}}
            <div class="flex items-end h-36 gap-0.5">
                @foreach($hourlyData as $hour => $bucket)
                    @php
                        $total = $bucket['accepted'] + $bucket['warning'] + $bucket['rejected'];
                        $heightPct = $total > 0 ? max(3, round($total / $hourlyMax * 100)) : 0;
                        $tooltip = sprintf('%02d:00 — Diterima: %d, Peringatan: %d, Ditolak: %d',
                            $hour, $bucket['accepted'], $bucket['warning'], $bucket['rejected']);
                    @endphp
                    <div
                        class="flex-1 flex flex-col-reverse rounded-t-sm overflow-hidden cursor-default"
                        style="height: {{ $heightPct }}%"
                        title="{{ $tooltip }}"
                    >
                        @if($bucket['accepted'] > 0)
                            <div class="bg-green-500 dark:bg-green-600" style="flex: {{ $bucket['accepted'] }}"></div>
                        @endif
                        @if($bucket['warning'] > 0)
                            <div class="bg-yellow-400 dark:bg-yellow-500" style="flex: {{ $bucket['warning'] }}"></div>
                        @endif
                        @if($bucket['rejected'] > 0)
                            <div class="bg-red-500 dark:bg-red-600" style="flex: {{ $bucket['rejected'] }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- X-axis labels --}}
            <div class="flex gap-0.5 mt-1">
                @foreach(range(0, 23) as $h)
                    <div class="flex-1 text-center">
                        @if($h % 6 === 0)
                            <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ sprintf('%02d', $h) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top 5 Alasan Ditolak --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-1">Top 5 Alasan Ditolak</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Hari ini</p>

            @if($topRejected->isEmpty())
                <div class="flex flex-col items-center justify-center h-32 text-center">
                    <x-tabler-circle-check class="w-8 h-8 text-green-400 mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada penolakan hari ini.</p>
                </div>
            @else
                <ul class="space-y-3">
                    @foreach($topRejected as $row)
                        @php
                            $codeEnum = $row->code instanceof \App\Enums\ScanResultCode
                                ? $row->code
                                : \App\Enums\ScanResultCode::tryFrom((string) $row->code);
                            $label = $codeEnum?->label() ?? (string) $row->code;
                            $pct = $snapshotRejected > 0
                                ? round($row->total / $snapshotRejected * 100)
                                : 0;
                        @endphp
                        <li>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-700 dark:text-gray-300 truncate pr-2">{{ $label }}</span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-white flex-shrink-0">
                                    {{ $row->total }}
                                    <span class="text-gray-400 font-normal">({{ $pct }}%)</span>
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>

    {{-- ── 3. Tabel Event Aktif Hari Ini ──────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Event Aktif Hari Ini</h3>
        </div>

        <x-ui.table>
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-3">Nama Event</th>
                    <th scope="col" class="px-4 py-3 hidden md:table-cell">Rentang</th>
                    <th scope="col" class="px-4 py-3 hidden lg:table-cell">Hari Aktif</th>
                    <th scope="col" class="px-4 py-3 text-center">Check-in</th>
                    <th scope="col" class="px-4 py-3 text-center hidden sm:table-cell">Peringatan</th>
                    <th scope="col" class="px-4 py-3 text-center hidden sm:table-cell">Ditolak</th>
                    <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($activeEvents as $event)
                    @php
                        $eventScanMetrics = $scanByEventResult->get($event->id, collect());
                        $eventWarnings    = (int) ($eventScanMetrics->firstWhere('result', 'warning')?->total ?? 0);
                        $eventRejected    = (int) ($eventScanMetrics->firstWhere('result', 'rejected')?->total ?? 0);
                        $eventCheckins    = (int) ($checkinByEvent->get($event->id, 0));
                        $sessionName      = $todaySessionByEvent->get($event->id)?->name ?? 'Hari ini';
                        $needsOverride    = now()->greaterThan($event->end_at);
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $event->name }}</p>
                            @if($event->code)
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $event->code }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $event->start_at->format('d M Y') }}
                                @if($event->start_at->toDateString() !== $event->end_at->toDateString())
                                    — {{ $event->end_at->format('d M Y') }}
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $sessionName }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ $eventCheckins }}</span>
                        </td>
                        <td class="px-4 py-3 text-center hidden sm:table-cell">
                            <span class="text-sm font-semibold {{ $eventWarnings > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $eventWarnings }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center hidden sm:table-cell">
                            <span class="text-sm font-semibold {{ $eventRejected > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $eventRejected }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                <x-ui.button
                                    href="{{ route('admin.events.edit', $event) }}"
                                    size="sm"
                                >Detail</x-ui.button>
                                <x-ui.button
                                    href="{{ route('admin.events.participants', $event) }}"
                                    size="sm"
                                >Peserta</x-ui.button>
                                <x-ui.button
                                    href="{{ route('admin.presensi.index') }}"
                                    size="sm"
                                >Presensi</x-ui.button>
                                @if($needsOverride)
                                    <x-ui.button
                                        href="{{ route('admin.events.edit', $event) }}"
                                        size="sm"
                                        class="text-orange-600 border-orange-300 hover:bg-orange-50 dark:text-orange-400 dark:border-orange-700 dark:hover:bg-orange-900/20"
                                    >Override</x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.table-empty message="Tidak ada event aktif hari ini." :colspan="7" />
                @endforelse
            </tbody>
        </x-ui.table>
    </div>

    {{-- ── 4. Alert Operasional + Monitoring Mini ──────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Panel Alert Operasional --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                Alert Operasional
            </h3>

            {{-- Override aktif --}}
            <div class="mb-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">
                    Override Aktif
                </p>
                @if($overrideEvents->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada override aktif.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($overrideEvents as $ov)
                            <li class="flex items-start gap-2">
                                <x-tabler-clock-exclamation class="w-4 h-4 text-orange-500 flex-shrink-0 mt-0.5" />
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $ov->name }}</p>
                                    <p class="text-xs text-orange-500 dark:text-orange-400">
                                        Aktif hingga {{ $ov->override_until->format('H:i, d M Y') }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Top 3 revoked --}}
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">
                    Token Dicabut Paling Sering Scan
                </p>
                @if($topRevoked->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada scan token dicabut hari ini.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($topRevoked as $rv)
                            <li class="flex items-start gap-2">
                                <x-tabler-shield-x class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-gray-600 dark:text-gray-300 truncate" title="{{ $rv->message }}">
                                        {{ Str::limit($rv->message, 80) }}
                                    </p>
                                    <p class="text-xs text-red-500 dark:text-red-400">{{ $rv->total }}× hari ini</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Monitoring Mini --}}
        @role('admin')
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                Monitoring
            </h3>

            <ul class="space-y-3">

                {{-- Error Log --}}
                <li class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-tabler-bug class="w-4 h-4 text-red-500 flex-shrink-0" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Error Log hari ini</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold {{ $errorLogCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $errorLogCount }}
                        </span>
                        <a
                            href="{{ route('log-viewer.index') }}"
                            target="_blank"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-0.5"
                        >Lihat <x-tabler-external-link class="w-3 h-3" /></a>
                    </div>
                </li>

                {{-- Activity Log --}}
                <li class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-tabler-list-details class="w-4 h-4 text-blue-500 flex-shrink-0" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Activity Log hari ini</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $activityLogCount }}</span>
                        <a
                            href="{{ route('admin.monitoring.activity') }}"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                        >Lihat</a>
                    </div>
                </li>

                {{-- Queue --}}
                <li class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-tabler-list-check class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Queue</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500 italic">Belum digunakan</span>
                        <a
                            href="{{ route('admin.monitoring.queue') }}"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                        >Lihat</a>
                    </div>
                </li>

            </ul>
        </div>
        @endrole

    </div>

</div>
