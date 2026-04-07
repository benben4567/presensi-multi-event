<div>
    <x-ui.header :title="$templateId ? 'Edit Template Cetak' : 'Tambah Template Cetak'">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.print-templates.index') }}">Batal</x-ui.button>
            <x-ui.button wire:click="save" variant="primary">Simpan</x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ── Kolom kiri: form fields ── --}}
        <div class="space-y-6">

            {{-- Informasi Template --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
                    Informasi Template
                </h3>

                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <div class="pb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Nama Template <span class="text-red-500">*</span>
                        </label>
                        <x-ui.input wire:model="name" placeholder="Contoh: Template SEMNAS 2026" />
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="py-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Gambar Latar (PNG/JPG)
                            @if ($templateId)
                                <span class="text-gray-400 text-xs font-normal ml-1">(kosongkan jika tidak diganti)</span>
                            @else
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if ($existingImagePath && ! $photo)
                            <div class="mb-2">
                                <img
                                    src="{{ Storage::disk('public')->url($existingImagePath) }}"
                                    alt="Gambar latar saat ini"
                                    class="h-24 rounded border border-gray-200 dark:border-gray-600 object-contain bg-gray-50 dark:bg-gray-700"
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Gambar latar saat ini</p>
                            </div>
                        @endif

                        @if ($photo)
                            <div class="mb-2">
                                <img
                                    src="{{ $photo->temporaryUrl() }}"
                                    alt="Preview gambar baru"
                                    class="h-24 rounded border border-gray-200 dark:border-gray-600 object-contain bg-gray-50 dark:bg-gray-700"
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Preview gambar baru</p>
                            </div>
                        @endif

                        <input
                            type="file"
                            wire:model="photo"
                            accept="image/png,image/jpeg"
                            class="block w-full text-sm text-gray-700 dark:text-gray-300
                                   file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                   file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700
                                   dark:file:bg-gray-700 dark:file:text-gray-300
                                   hover:file:bg-gray-200 dark:hover:file:bg-gray-600"
                        />
                        @error('photo')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ukuran Halaman (mm)
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Lebar</label>
                                <x-ui.input type="number" wire:model.live="pageWidthMm" min="50" max="300" />
                            </div>
                            <span class="text-gray-400 mt-5">×</span>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tinggi</label>
                                <x-ui.input type="number" wire:model.live="pageHeightMm" min="50" max="400" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Koordinat Area QR (disinkronisasi dari canvas) --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
                    Koordinat Area QR
                    <span class="text-xs font-normal text-gray-400 ml-1">(atur via canvas di sebelah kanan)</span>
                </h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Posisi X (mm)</label>
                        <x-ui.input type="number" wire:model.live="qrXMm" step="0.5" min="0" />
                        @error('qrXMm')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Posisi Y (mm)</label>
                        <x-ui.input type="number" wire:model.live="qrYMm" step="0.5" min="0" />
                        @error('qrYMm')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Lebar (mm) <span class="text-red-400">min 30</span></label>
                        <x-ui.input type="number" wire:model.live="qrWMm" step="0.5" min="30" />
                        @error('qrWMm')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tinggi (mm) <span class="text-red-400">min 30</span></label>
                        <x-ui.input type="number" wire:model.live="qrHMm" step="0.5" min="30" />
                        @error('qrHMm')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                    Area QR akan dikelilingi kotak putih (quiet zone) saat cetak agar QR tetap terbaca meskipun latar berwarna gelap.
                </p>
            </div>
        </div>

        {{-- ── Kolom kanan: canvas preview drag/resize ── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
                Preview Posisi QR
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Seret kotak biru untuk memindahkan area QR. Seret sudut untuk mengubah ukuran.
                Perubahan otomatis tersimpan ke kolom koordinat di kiri.
            </p>

            {{-- Canvas area --}}
            <div
                x-data="qrCanvas(@js($pageWidthMm), @js($pageHeightMm), @js($qrXMm), @js($qrYMm), @js($qrWMm), @js($qrHMm))"
                x-init="init()"
                @mousemove.window="onMouseMove($event)"
                @mouseup.window="stopDrag()"
                @touchmove.window.prevent="onTouchMove($event)"
                @touchend.window="stopDrag()"
            >
                {{-- Wrapper to scale canvas to fit the column --}}
                <div class="flex justify-center">
                    <div
                        class="relative border border-gray-300 dark:border-gray-600 overflow-hidden bg-gray-100 dark:bg-gray-700 select-none"
                        :style="`width: ${canvasW}px; height: ${canvasH}px;`"
                        x-ref="canvas"
                    >
                        {{-- Background image --}}
                        @if ($photo)
                            <img
                                src="{{ $photo->temporaryUrl() }}"
                                class="absolute inset-0 w-full h-full object-fill pointer-events-none"
                                alt=""
                            />
                        @elseif ($existingImagePath)
                            <img
                                src="{{ Storage::disk('public')->url($existingImagePath) }}"
                                class="absolute inset-0 w-full h-full object-fill pointer-events-none"
                                alt=""
                            />
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-xs text-gray-400 dark:text-gray-500">
                                Unggah gambar latar untuk preview
                            </div>
                        @endif

                        {{-- White quiet-zone box (preview) --}}
                        <div
                            class="absolute bg-white/70 pointer-events-none"
                            :style="`
                                left: ${qr.x - 2 * scale}px;
                                top: ${qr.y - 2 * scale}px;
                                width: ${qr.w + 4 * scale}px;
                                height: ${qr.h + 4 * scale}px;
                            `"
                        ></div>

                        {{-- QR area overlay --}}
                        <div
                            class="absolute border-2 border-blue-500 bg-blue-400/20 cursor-move"
                            :style="`left: ${qr.x}px; top: ${qr.y}px; width: ${qr.w}px; height: ${qr.h}px;`"
                            @mousedown.stop="startDrag($event)"
                            @touchstart.prevent.stop="startTouchDrag($event)"
                        >
                            {{-- QR icon placeholder --}}
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <svg class="text-blue-600 opacity-60" :width="Math.min(qr.w, qr.h) * 0.6" :height="Math.min(qr.w, qr.h) * 0.6" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 3h7v7H3V3zm2 2v3h3V5H5zm8-2h7v7h-7V3zm2 2v3h3V5h-3zM3 13h7v7H3v-7zm2 2v3h3v-3H5zm11 0h2v2h-2v-2zm-3-2h2v2h-2v-2zm0 4h2v2h-2v-2zm4-2h2v2h-2v-2zm-2 2h2v2h-2v-2zm4 2h2v-2h-2v2z"/>
                                </svg>
                            </div>

                            {{-- Size label --}}
                            <div class="absolute bottom-0 left-0 right-0 text-center pointer-events-none">
                                <span
                                    class="text-blue-700 font-mono bg-blue-50/80 px-1 rounded-sm"
                                    :style="`font-size: ${Math.max(8, scale * 3)}px;`"
                                    x-text="`${qrWMm.toFixed(1)}×${qrHMm.toFixed(1)} mm`"
                                ></span>
                            </div>

                            {{-- Resize handles (4 corners) --}}
                            <template x-for="handle in ['nw','ne','sw','se']" :key="handle">
                                <div
                                    class="absolute w-3 h-3 bg-white border-2 border-blue-500 rounded-sm"
                                    :class="{
                                        'top-0 left-0 -translate-x-1/2 -translate-y-1/2 cursor-nw-resize': handle === 'nw',
                                        'top-0 right-0 translate-x-1/2 -translate-y-1/2 cursor-ne-resize': handle === 'ne',
                                        'bottom-0 left-0 -translate-x-1/2 translate-y-1/2 cursor-sw-resize': handle === 'sw',
                                        'bottom-0 right-0 translate-x-1/2 translate-y-1/2 cursor-se-resize': handle === 'se',
                                    }"
                                    @mousedown.stop="startResize($event, handle)"
                                    @touchstart.prevent.stop="startTouchResize($event, handle)"
                                ></div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Scale note --}}
                <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-2">
                    Skala: <span x-text="scale.toFixed(1)"></span> px/mm |
                    Halaman: <span x-text="pageWMm"></span> × <span x-text="pageHMm"></span> mm
                </p>
            </div>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('qrCanvas', (pageWMm, pageHMm, initX, initY, initW, initH) => ({
    pageWMm,
    pageHMm,
    scale: 1,
    canvasW: 0,
    canvasH: 0,
    minSizePx: 0,
    qrXMm: initX,
    qrYMm: initY,
    qrWMm: initW,
    qrHMm: initH,
    qr: { x: 0, y: 0, w: 0, h: 0 }, // px
    _drag: null,

    init() {
        // Scale to fit ~300px wide
        this.scale = Math.min(3, Math.floor(300 / pageWMm * 10) / 10);
        this.canvasW = Math.round(pageWMm * this.scale);
        this.canvasH = Math.round(pageHMm * this.scale);
        this.minSizePx = 30 * this.scale;

        this.qr = {
            x: this.qrXMm * this.scale,
            y: this.qrYMm * this.scale,
            w: this.qrWMm * this.scale,
            h: this.qrHMm * this.scale,
        };

        this.$watch('qrXMm', v => { this.qr.x = v * this.scale; });
        this.$watch('qrYMm', v => { this.qr.y = v * this.scale; });
        this.$watch('qrWMm', v => { this.qr.w = v * this.scale; });
        this.$watch('qrHMm', v => { this.qr.h = v * this.scale; });
    },

    _eventXY(e) {
        if (e.touches) {
            const rect = this.$refs.canvas.getBoundingClientRect();
            return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
        }
        const rect = this.$refs.canvas.getBoundingClientRect();
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    },

    startDrag(e) {
        const pos = this._eventXY(e);
        this._drag = { type: 'move', startX: pos.x, startY: pos.y, startQr: { ...this.qr } };
    },

    startTouchDrag(e) { this.startDrag(e); },

    startResize(e, handle) {
        const pos = this._eventXY(e);
        this._drag = { type: 'resize', handle, startX: pos.x, startY: pos.y, startQr: { ...this.qr } };
    },

    startTouchResize(e, handle) { this.startResize(e, handle); },

    onMouseMove(e) {
        if (!this._drag) return;
        const pos = this._eventXY(e);
        this._applyDrag(pos.x, pos.y);
    },

    onTouchMove(e) {
        if (!this._drag) return;
        const pos = this._eventXY(e);
        this._applyDrag(pos.x, pos.y);
    },

    _applyDrag(cx, cy) {
        const dx = cx - this._drag.startX;
        const dy = cy - this._drag.startY;
        const sq = this._drag.startQr;
        const minSz = this.minSizePx;
        const maxW = this.canvasW;
        const maxH = this.canvasH;

        if (this._drag.type === 'move') {
            const newX = Math.max(0, Math.min(maxW - sq.w, sq.x + dx));
            const newY = Math.max(0, Math.min(maxH - sq.h, sq.y + dy));
            this.qr = { ...this.qr, x: newX, y: newY };
        } else {
            let { x, y, w, h } = sq;
            const handle = this._drag.handle;

            if (handle === 'se') {
                w = Math.max(minSz, Math.min(maxW - x, w + dx));
                h = Math.max(minSz, Math.min(maxH - y, h + dy));
            } else if (handle === 'sw') {
                const newX = Math.max(0, Math.min(x + w - minSz, x + dx));
                w = x + w - newX;
                h = Math.max(minSz, Math.min(maxH - y, h + dy));
                x = newX;
            } else if (handle === 'ne') {
                w = Math.max(minSz, Math.min(maxW - x, w + dx));
                const newY = Math.max(0, Math.min(y + h - minSz, y + dy));
                h = y + h - newY;
                y = newY;
            } else if (handle === 'nw') {
                const newX = Math.max(0, Math.min(x + w - minSz, x + dx));
                const newY = Math.max(0, Math.min(y + h - minSz, y + dy));
                w = x + w - newX;
                h = y + h - newY;
                x = newX;
                y = newY;
            }

            this.qr = { x, y, w, h };
        }

        this._syncMm();
    },

    stopDrag() {
        if (!this._drag) return;
        this._drag = null;
        this._pushToLivewire();
    },

    _syncMm() {
        this.qrXMm = parseFloat((this.qr.x / this.scale).toFixed(2));
        this.qrYMm = parseFloat((this.qr.y / this.scale).toFixed(2));
        this.qrWMm = parseFloat((this.qr.w / this.scale).toFixed(2));
        this.qrHMm = parseFloat((this.qr.h / this.scale).toFixed(2));
    },

    _pushToLivewire() {
        this.$wire.set('qrXMm', this.qrXMm);
        this.$wire.set('qrYMm', this.qrYMm);
        this.$wire.set('qrWMm', this.qrWMm);
        this.$wire.set('qrHMm', this.qrHMm);
    },
}));
</script>
@endscript
