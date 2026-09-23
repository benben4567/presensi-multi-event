<div
    x-data="qrScanner()"
    x-init="init()"
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
                        @if($resultPhone)
                            <p class="text-sm font-mono {{ $textClass }} opacity-80">{{ $resultPhone }}</p>
                        @endif
                        @foreach($resultMeta as $key => $value)
                            <p class="text-sm {{ $textClass }} opacity-80">
                                <span class="font-medium uppercase tracking-wide text-xs opacity-70">{{ $key }}</span>
                                {{ $value }}
                            </p>
                        @endforeach
                        <p class="text-sm mt-1 {{ $textClass }}">{{ $resultMessage }}</p>
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
            {{-- Scanner status indicator --}}
            <div class="absolute top-3 right-3 flex items-center gap-1.5 text-xs font-medium">
                <template x-if="processing">
                    <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                        <span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></span>
                        Memproses...
                    </span>
                </template>
                <template x-if="!processing && focused">
                    <span class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        Siap menerima scan
                    </span>
                </template>
                <template x-if="!processing && !focused">
                    <span class="flex items-center gap-1.5 text-red-500 dark:text-red-400">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Nonaktif — klik area ini
                    </span>
                </template>
            </div>

            <x-tabler-scan class="w-16 h-16 text-blue-400 dark:text-blue-500 mx-auto mb-3" />
            <p class="text-base font-medium text-gray-700 dark:text-gray-200">Arahkan QR ke scanner</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Klik area ini lalu scan QR peserta</p>

            {{-- Hidden input — always focused for HID scanner --}}
            <input
                x-ref="qrInput"
                x-model="qrValue"
                x-init="$el.focus()"
                :disabled="processing"
                @keydown.enter.prevent="submit()"
                @focus="focused = true"
                @blur="focused = false; setTimeout(() => $el.focus(), 100)"
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

@script
<script>
Alpine.data('qrScanner', () => ({
    qrValue: '',
    processing: false,
    focused: false,
    _audioCtx: null,

    init() {
        let uuid = localStorage.getItem('device_uuid');
        if (!uuid) {
            uuid = crypto.randomUUID();
            localStorage.setItem('device_uuid', uuid);
        }
        this.$wire.set('deviceUuid', uuid);

        this.$wire.on('scan-completed', ({ outcome }) => this.beep(outcome));
    },

    submit() {
        if (this.processing) return;

        const val = this.qrValue.trim();
        this.qrValue = '';
        if (!val) return;

        this.processing = true;

        this.$wire.processQrValue(val).then(() => {
            this.processing = false;
            this.$nextTick(() => this.$refs.qrInput.focus());
        });
    },

    // Short beep via Web Audio API — no audio asset files needed.
    beep(outcome) {
        this._audioCtx ??= new (window.AudioContext || window.webkitAudioContext)();

        const tones = {
            accepted: [{ freq: 880, duration: 0.12 }],
            warning: [{ freq: 500, duration: 0.18 }],
        }[outcome] ?? [
            { freq: 220, duration: 0.12 },
            { freq: 220, duration: 0.12, delay: 0.16 },
        ];

        tones.forEach(({ freq, duration, delay = 0 }) => {
            const ctx = this._audioCtx;
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.frequency.value = freq;
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            gain.gain.setValueAtTime(0.2, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + duration);
            oscillator.start(ctx.currentTime + delay);
            oscillator.stop(ctx.currentTime + delay + duration);
        });
    },
}));
</script>
@endscript
