<div
    x-data="{
        qrValue: '',
        initDevice() {
            let uuid = localStorage.getItem('device_uuid');
            if (!uuid) {
                uuid = crypto.randomUUID();
                localStorage.setItem('device_uuid', uuid);
            }
            $wire.set('deviceUuid', uuid);
        }
    }"
    x-init="initDevice()"
>
    {{-- Session selector (only shown when event has multiple sessions) --}}
    @if($event->sessions->count() > 1)
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                Sesi aktif
            </label>
            <select
                wire:model.live="sessionId"
                class="w-full sm:w-72 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
                <option value="">— Pilih sesi —</option>
                @foreach($event->sessions as $s)
                    <option value="{{ $s->id }}" @selected($sessionId == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if(! $sessionId)
        {{-- No session selected --}}
        <x-ui.alert type="warning">
            Pilih sesi terlebih dahulu sebelum memulai scan.
        </x-ui.alert>
    @else
        {{-- Result banner --}}
        @if($resultOutcome)
            @php
                [$bgClass, $borderClass, $textClass, $iconColor, $iconComponent] = match($resultOutcome) {
                    'accepted' => [
                        'bg-green-50 dark:bg-green-900/30',
                        'border-green-300 dark:border-green-700',
                        'text-green-800 dark:text-green-200',
                        'text-green-500',
                        'tabler-circle-check',
                    ],
                    'warning' => [
                        'bg-yellow-50 dark:bg-yellow-900/30',
                        'border-yellow-300 dark:border-yellow-700',
                        'text-yellow-800 dark:text-yellow-200',
                        'text-yellow-500',
                        'tabler-alert-triangle',
                    ],
                    default => [
                        'bg-red-50 dark:bg-red-900/30',
                        'border-red-300 dark:border-red-700',
                        'text-red-800 dark:text-red-200',
                        'text-red-500',
                        'tabler-circle-x',
                    ],
                };
            @endphp
            <div class="mb-4 rounded-xl border-2 {{ $bgClass }} {{ $borderClass }} p-4 flex items-start gap-3">
                <x-dynamic-component :component="$iconComponent" class="w-7 h-7 flex-shrink-0 mt-0.5 {{ $iconColor }}" />
                <div class="flex-1 min-w-0">
                    @if($resultName)
                        <p class="text-lg font-semibold {{ $textClass }}">{{ $resultName }}</p>
                        <p class="text-sm mt-0.5 {{ $textClass }}">{{ $resultMessage }}</p>
                    @else
                        <p class="text-base font-semibold {{ $textClass }}">{{ $resultMessage }}</p>
                    @endif
                </div>
                <button
                    wire:click="clearResult"
                    class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity {{ $textClass }}"
                    title="Tutup"
                >
                    <x-tabler-x class="w-5 h-5" />
                </button>
            </div>
        @endif

        {{-- QR scan area --}}
        <div
            class="relative mb-5 rounded-xl border-2 border-dashed border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-10 text-center cursor-text select-none"
            @click="$refs.qrInput.focus()"
        >
            <x-tabler-scan class="w-16 h-16 text-blue-400 dark:text-blue-500 mx-auto mb-3" />
            <p class="text-base font-medium text-gray-700 dark:text-gray-200">Arahkan QR ke scanner</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Klik area ini lalu scan QR peserta</p>

            {{-- Hidden input — always focused for HID scanner --}}
            <input
                x-ref="qrInput"
                x-model="qrValue"
                x-init="$el.focus()"
                @keydown.enter.prevent="
                    let val = qrValue.trim();
                    qrValue = '';
                    if (val) $wire.processQrValue(val);
                "
                @blur="setTimeout(() => $el.focus(), 100)"
                inputmode="none"
                autocomplete="off"
                class="absolute inset-0 w-full h-full opacity-0 cursor-default"
                aria-label="Input QR scanner"
            />
        </div>

        {{-- Recent scans --}}
        @if(count($recentScans) > 0)
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
                    Riwayat Scan
                </h3>
                <div class="space-y-1.5">
                    @foreach($recentScans as $scan)
                        @php
                            $dotColor = match($scan['outcome']) {
                                'accepted' => 'bg-green-500',
                                'warning'  => 'bg-yellow-500',
                                default    => 'bg-red-500',
                            };
                        @endphp
                        <div class="flex items-center gap-2.5 py-2 px-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-sm">
                            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $dotColor }}"></span>
                            <span class="font-mono text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{ $scan['time'] }}</span>
                            <span class="text-gray-700 dark:text-gray-300 truncate">
                                @if($scan['name']){{ $scan['name'] }} — @endif{{ $scan['message'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
