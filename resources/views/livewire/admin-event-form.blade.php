<div>
    <x-ui.header :title="$eventId ? 'Edit Event' : 'Tambah Event'">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.events.index') }}">Batal</x-ui.button>
            <x-ui.button wire:click="save" variant="primary">Simpan</x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Main form ───────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Informasi Event --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
                    Informasi Event
                </h3>

                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <div class="pb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Nama Event <span class="text-red-500">*</span>
                        </label>
                        <x-ui.input wire:model="name" placeholder="Contoh: Seminar Nasional 2026" />
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="py-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Kode Event
                            <span class="text-gray-400 text-xs font-normal ml-1">(opsional, unik, maks. 10 karakter)</span>
                        </label>
                        <x-ui.input wire:model="code" placeholder="Contoh: SN-2026" maxlength="10" />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Wajib diisi untuk menggunakan <strong>Export Lembar Stiker</strong>. Biarkan kosong jika hanya butuh kartu undangan QR. Format kode undangan: <span class="font-mono">KODE-NNNN</span> — gunakan kode pendek agar terbaca di stiker 16&nbsp;mm.
                        </p>
                        @error('code')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Mulai <span class="text-red-500">*</span>
                            </label>
                            <x-ui.input type="datetime-local" wire:model="startAt" />
                            @error('startAt')
                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Selesai <span class="text-red-500">*</span>
                            </label>
                            <x-ui.input type="datetime-local" wire:model="endAt" />
                            @error('endAt')
                                <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <x-ui.select wire:model="status">
                            <option value="draft">Draf</option>
                            <option value="open">Aktif</option>
                            <option value="closed">Selesai</option>
                        </x-ui.select>
                        @error('status')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Tampilan Operator --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Tampilan di Layar Operator
                    </h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Pilih field peserta yang ditampilkan saat scan QR atau presensi manual.
                    </p>
                </div>

                <div class="space-y-5">
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="operatorDisplayFields" value="name"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700" />
                            Nama peserta
                        </label>
                        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" wire:model="operatorDisplayFields" value="phone_e164"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700" />
                            Nomor HP
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Field meta tambahan
                            <span class="text-gray-400 text-xs font-normal ml-1">(pisahkan dengan koma)</span>
                        </label>
                        <x-ui.input wire:model="extraDisplayFields" placeholder="Contoh: unit, jabatan, angkatan" />
                        @error('extraDisplayFields')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
        <div class="space-y-6">

            {{-- Pengaturan Presensi --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-5 border-b border-gray-100 dark:border-gray-700">
                    Pengaturan Presensi
                </h3>

                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="checkbox" wire:model="enableCheckout" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700" />
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                            Aktifkan Check-out
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Peserta harus melakukan check-in dan check-out setiap hari.
                        </p>
                    </div>
                </label>
            </div>

            {{-- Daftar Hari (edit mode) --}}
            @if ($eventId)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                        Hari Event
                        <span class="ml-1.5 text-xs font-normal text-gray-400">({{ $sessions->count() }})</span>
                    </h3>

                    @if ($sessions->isEmpty())
                        <p class="text-xs text-gray-400 dark:text-gray-500">Belum ada hari. Simpan event untuk membuat hari.</p>
                    @else
                        <ul class="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                            @foreach ($sessions as $session)
                                <li class="flex items-center gap-2">
                                    <x-tabler-calendar class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                                    {{ $session->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- URL Operator --}}
                <div
                    x-data="{
                        copied: null,
                        copy(key, url) {
                            navigator.clipboard.writeText(url).then(() => {
                                this.copied = key;
                                setTimeout(() => this.copied = null, 2000);
                            });
                        }
                    }"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"
                >
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                        URL Operator
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        Bagikan URL ini ke operator untuk memulai presensi.
                    </p>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Scan QR</p>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 min-w-0 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-1.5 text-gray-700 dark:text-gray-300 truncate block">
                                    {{ route('ops.events.scan', $event) }}
                                </code>
                                <button
                                    type="button"
                                    @click="copy('scan', '{{ route('ops.events.scan', $event) }}')"
                                    class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-colors"
                                    :title="copied === 'scan' ? 'Tersalin!' : 'Salin URL'"
                                >
                                    <x-tabler-check class="w-4 h-4 text-green-500" x-show="copied === 'scan'" />
                                    <x-tabler-copy class="w-4 h-4" x-show="copied !== 'scan'" />
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Manual</p>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 min-w-0 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-1.5 text-gray-700 dark:text-gray-300 truncate block">
                                    {{ route('ops.events.manual', $event) }}
                                </code>
                                <button
                                    type="button"
                                    @click="copy('manual', '{{ route('ops.events.manual', $event) }}')"
                                    class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-colors"
                                    :title="copied === 'manual' ? 'Tersalin!' : 'Salin URL'"
                                >
                                    <x-tabler-check class="w-4 h-4 text-green-500" x-show="copied === 'manual'" />
                                    <x-tabler-copy class="w-4 h-4" x-show="copied !== 'manual'" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Grace Override --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="pb-4 mb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Buka Presensi
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Perpanjang waktu presensi setelah event selesai.
                        </p>
                        @if ($event?->override_until && now()->lessThanOrEqualTo($event->override_until))
                            <p class="mt-1.5 text-xs font-medium text-blue-600 dark:text-blue-400">
                                Aktif hingga {{ $event->override_until->format('H:i, d M Y') }}.
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([15, 30, 60, 120] as $min)
                            <x-ui.button wire:click="openAttendance({{ $min }})" size="sm">
                                {{ $min }} menit
                            </x-ui.button>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
