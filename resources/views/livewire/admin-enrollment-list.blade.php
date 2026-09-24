<div>
    <x-ui.header :title="'Peserta — ' . $event->name">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.events.index') }}">
                <x-tabler-arrow-left class="w-4 h-4" />
                Kembali
            </x-ui.button>
            {{-- Export dropdown --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-1 transition-colors bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                >
                    <x-tabler-download class="w-4 h-4" />
                    Export
                    <span class="inline-flex transition-transform" :class="open && 'rotate-180'">
                        <x-tabler-chevron-down class="w-3.5 h-3.5" />
                    </span>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 z-20 mt-1 w-56 origin-top-right rounded-lg border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800"
                    style="display: none"
                >
                    <div class="py-1">
                        <a
                            href="{{ route('admin.events.invitation-cards', $event) }}"
                            target="_blank"
                            class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <x-tabler-id class="w-4 h-4 shrink-0 text-blue-500" />
                            Kartu Undangan (PDF)
                        </a>
                        <a
                            href="{{ route('admin.events.invitation-cards.sticker', $event) }}"
                            target="_blank"
                            class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <x-tabler-sticker class="w-4 h-4 shrink-0 text-blue-500" />
                            Lembar Stiker (PDF)
                        </a>
                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                        <a
                            href="{{ route('admin.events.invitation-cards.mapping', $event) }}"
                            target="_blank"
                            class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <x-tabler-table-export class="w-4 h-4 shrink-0 text-green-500" />
                            Mapping Stiker (CSV)
                        </a>
                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                        <button
                            type="button"
                            wire:click="confirmSendInvitationEmails"
                            @click="open = false"
                            class="flex items-center w-full gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <x-tabler-mail class="w-4 h-4 shrink-0 text-blue-500" />
                            Kirim Undangan via Email
                        </button>
                    </div>
                </div>
            </div>
            <x-ui.button
                href="{{ route('admin.events.participants.import', $event) }}"
                variant="primary"
            >
                <x-tabler-file-import class="w-4 h-4" />
                Impor Peserta
            </x-ui.button>
            <x-ui.button wire:click="openAddForm" variant="primary">
                <x-tabler-user-plus class="w-4 h-4" />
                Tambah Peserta
            </x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    {{-- Search + filter --}}
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="max-w-sm flex-1 min-w-[200px]">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau nomor HP..."
            />
        </div>
        <div class="w-48">
            <x-ui.select wire:model.live="statusFilter">
                <option value="">Semua Status</option>
                <option value="allowed">Aktif</option>
                <option value="disabled">Nonaktif</option>
                <option value="blacklisted">Diblacklist</option>
            </x-ui.select>
        </div>
    </div>

    {{-- Bulk action bar --}}
    @if(count($selected) > 0)
        <div class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 dark:border-blue-800 dark:bg-blue-900/30">
            <span class="text-sm text-blue-800 dark:text-blue-200">
                {{ count($selected) }} peserta dipilih
            </span>
            <div class="flex items-center gap-2">
                <x-ui.button wire:click="confirmBulkEnable" size="sm">Aktifkan Terpilih</x-ui.button>
                <x-ui.button wire:click="confirmBulkDisable" size="sm">Nonaktifkan Terpilih</x-ui.button>
                <x-ui.button wire:click="clearSelection" size="sm">Batal Pilih</x-ui.button>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3 w-10">
                    <input
                        type="checkbox"
                        wire:model.live="selectAll"
                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                        title="Pilih semua peserta yang cocok dengan pencarian/filter saat ini"
                    />
                </th>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Nomor HP</th>
                <th class="px-4 py-3">Status Akses</th>
                <th class="px-4 py-3">QR</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($enrollments as $enrollment)
                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">
                        <input
                            type="checkbox"
                            wire:model.live="selected"
                            value="{{ $enrollment->id }}"
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800 dark:text-white">{{ $enrollment->participant->name }}</p>
                        @if($enrollment->access_reason)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-xs">{{ $enrollment->access_reason }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-sm text-gray-600 dark:text-gray-300">
                        {{ $enrollment->participant->phone_e164 ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $statusColor = match($enrollment->access_status) {
                                \App\Enums\AccessStatus::Allowed     => 'green',
                                \App\Enums\AccessStatus::Disabled    => 'yellow',
                                \App\Enums\AccessStatus::Blacklisted => 'red',
                            };
                            $statusLabel = match($enrollment->access_status) {
                                \App\Enums\AccessStatus::Allowed     => 'Aktif',
                                \App\Enums\AccessStatus::Disabled    => 'Nonaktif',
                                \App\Enums\AccessStatus::Blacklisted => 'Diblacklist',
                            };
                        @endphp
                        <x-ui.badge :color="$statusColor">{{ $statusLabel }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-3">
                        @if($enrollment->invitation?->token)
                            <button
                                type="button"
                                wire:click="viewQr({{ $enrollment->id }})"
                                class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                <x-tabler-qrcode class="w-4 h-4" />
                                @if($enrollment->invitation->isRevoked())
                                    <span class="line-through opacity-50">Lihat QR</span>
                                @else
                                    Lihat QR
                                @endif
                            </button>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">

                            {{-- Edit data peserta (paling sering dipakai, tetap inline) --}}
                            <x-ui.button
                                wire:click="openEditForm({{ $enrollment->id }})"
                                size="sm"
                            >
                                Edit
                            </x-ui.button>

                            {{-- Aksi lain — dropdown biar baris gak penuh tombol --}}
                            <div
                                class="relative inline-block text-left"
                                x-data="{
                                    open: false,
                                    top: 0,
                                    left: 0,
                                    toggle() {
                                        this.open = !this.open;
                                        if (this.open) {
                                            const r = $refs.trigger.getBoundingClientRect();
                                            this.top = r.bottom + 4;
                                            this.left = r.right - 224;
                                        }
                                    },
                                }"
                            >
                                <button
                                    type="button"
                                    x-ref="trigger"
                                    @click="toggle()"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                                >
                                    <x-tabler-dots-vertical class="w-4 h-4" />
                                </button>

                                {{-- Teleport ke <body> biar gak ke-clip overflow-x-auto tabel --}}
                                <template x-teleport="body">
                                    <div
                                        x-show="open"
                                        @click.outside="if (!$refs.trigger.contains($event.target)) open = false"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        :style="`top:${top}px; left:${left}px;`"
                                        class="fixed z-50 w-56 rounded-lg border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800"
                                        style="display: none"
                                    >
                                        <div class="py-1">
                                            {{-- Cetak kartu undangan individu --}}
                                            @if($enrollment->invitation?->token && !$enrollment->invitation->isRevoked())
                                                <a
                                                    href="{{ route('admin.events.participants.card', [$event, $enrollment]) }}"
                                                    target="_blank"
                                                    @click="open = false"
                                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                                >
                                                    <x-tabler-printer class="w-4 h-4 shrink-0 text-blue-500" />
                                                    Cetak
                                                </a>

                                                @if($event->invitation_info_pdf_path)
                                                    <a
                                                        href="{{ route('admin.events.participants.card-with-attachment', [$event, $enrollment]) }}"
                                                        target="_blank"
                                                        @click="open = false"
                                                        class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                                    >
                                                        <x-tabler-file-type-pdf class="w-4 h-4 shrink-0 text-blue-500" />
                                                        Cetak + Lampiran
                                                    </a>
                                                @endif

                                                <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                                            @endif

                                            {{-- Aktifkan Kembali (disabled or blacklisted) --}}
                                            @if($enrollment->access_status !== \App\Enums\AccessStatus::Allowed)
                                                <button
                                                    type="button"
                                                    wire:click="confirmEnable({{ $enrollment->id }})"
                                                    @click="open = false"
                                                    class="flex items-center w-full gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                                >
                                                    <x-tabler-player-play class="w-4 h-4 shrink-0 text-green-500" />
                                                    Aktifkan Kembali
                                                </button>
                                            @endif

                                            {{-- Nonaktifkan (allowed or blacklisted) --}}
                                            @if($enrollment->access_status !== \App\Enums\AccessStatus::Disabled)
                                                <button
                                                    type="button"
                                                    wire:click="confirmDisable({{ $enrollment->id }})"
                                                    @click="open = false"
                                                    class="flex items-center w-full gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                                >
                                                    <x-tabler-player-pause class="w-4 h-4 shrink-0 text-yellow-500" />
                                                    Nonaktifkan
                                                </button>
                                            @endif

                                            {{-- Blacklist (allowed or disabled) --}}
                                            @if($enrollment->access_status !== \App\Enums\AccessStatus::Blacklisted)
                                                <button
                                                    type="button"
                                                    wire:click="openBlacklist({{ $enrollment->id }})"
                                                    @click="open = false"
                                                    class="flex items-center w-full gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                >
                                                    <x-tabler-ban class="w-4 h-4 shrink-0" />
                                                    Blacklist
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.table-empty message="Belum ada peserta terdaftar." :colspan="6" />
            @endforelse
        </tbody>
    </x-ui.table>

    <x-ui.pagination :paginator="$enrollments" />

    {{-- ── Blacklist reason modal ──────────────────────────────────────── --}}
    <div
        x-data="{ show: $wire.entangle('showBlacklistForm') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @keydown.escape.window="$wire.cancelBlacklist()"
        style="display: none"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md"
            @click.stop
        >
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                    <x-tabler-ban class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Blacklist Peserta</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Masukkan alasan blacklist (maks. 100 karakter). QR peserta akan langsung dicabut.
                    </p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Alasan <span class="text-red-500">*</span>
                </label>
                <x-ui.input
                    wire:model="blacklistReason"
                    placeholder="Contoh: Melanggar tata tertib"
                    maxlength="100"
                />
                @error('blacklistReason')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <x-ui.button wire:click="cancelBlacklist">Batal</x-ui.button>
                <x-ui.button wire:click="confirmBlacklist" wire:loading.attr="disabled" wire:target="confirmBlacklist" variant="danger">
                    <span wire:loading.remove wire:target="confirmBlacklist">Blacklist</span>
                    <span wire:loading wire:target="confirmBlacklist">Memproses...</span>
                </x-ui.button>
            </div>
        </div>
    </div>

    {{-- ── Tambah peserta modal ────────────────────────────────────────── --}}
    <div
        x-data="{ show: $wire.entangle('showAddForm') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @keydown.escape.window="$wire.cancelAddForm()"
        style="display: none"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md"
            @click.stop
        >
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <x-tabler-user-plus class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Tambah Peserta</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Peserta akan langsung terdaftar dan mendapatkan QR undangan.
                    </p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Nama <span class="text-red-500">*</span>
                </label>
                <x-ui.input wire:model="newName" placeholder="Nama peserta" maxlength="150" />
                @error('newName')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    No HP <span class="text-red-500">*</span>
                </label>
                <x-ui.input wire:model="newPhone" placeholder="08xxxxxxxxxx" />
                @error('newPhone')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Email
                </label>
                <x-ui.input type="email" wire:model="newEmail" placeholder="nama@email.com (opsional)" maxlength="255" />
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Dipakai untuk kirim undangan lewat email.</p>
                @error('newEmail')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Atribut Tambahan
                    </label>
                    <button
                        type="button"
                        wire:click="addMetaField"
                        class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                    >
                        + Tambah Atribut
                    </button>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">
                    Opsional. Contoh: instansi, jabatan, dll.
                </p>

                @foreach($newMeta as $i => $row)
                    <div class="flex items-center gap-2 mb-2" wire:key="meta-row-{{ $i }}">
                        <x-ui.input wire:model="newMeta.{{ $i }}.key" placeholder="Nama atribut" class="flex-1" />
                        <x-ui.input wire:model="newMeta.{{ $i }}.value" placeholder="Nilai" class="flex-1" />
                        <button
                            type="button"
                            wire:click="removeMetaField({{ $i }})"
                            class="shrink-0 p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                        >
                            <x-tabler-x class="w-4 h-4" />
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3">
                <x-ui.button wire:click="cancelAddForm">Batal</x-ui.button>
                <x-ui.button wire:click="confirmAdd" wire:loading.attr="disabled" wire:target="confirmAdd" variant="primary">
                    <span wire:loading.remove wire:target="confirmAdd">Simpan</span>
                    <span wire:loading wire:target="confirmAdd">Menyimpan...</span>
                </x-ui.button>
            </div>
        </div>
    </div>

    {{-- ── Edit peserta modal ──────────────────────────────────────────── --}}
    <div
        x-data="{ show: $wire.entangle('showEditForm') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @keydown.escape.window="$wire.cancelEditForm()"
        style="display: none"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md"
            @click.stop
        >
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <x-tabler-edit class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">Edit Data Peserta</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Perubahan berlaku untuk peserta ini di semua event yang diikutinya.
                    </p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Nama <span class="text-red-500">*</span>
                </label>
                <x-ui.input wire:model="editName" placeholder="Nama peserta" maxlength="150" />
                @error('editName')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    No HP <span class="text-red-500">*</span>
                </label>
                <x-ui.input wire:model="editPhone" placeholder="08xxxxxxxxxx" />
                @error('editPhone')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Email
                </label>
                <x-ui.input type="email" wire:model="editEmail" placeholder="nama@email.com (opsional)" maxlength="255" />
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Dipakai untuk kirim undangan lewat email.</p>
                @error('editEmail')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <x-ui.button wire:click="cancelEditForm">Batal</x-ui.button>
                <x-ui.button wire:click="confirmEdit" wire:loading.attr="disabled" wire:target="confirmEdit" variant="primary">
                    <span wire:loading.remove wire:target="confirmEdit">Simpan</span>
                    <span wire:loading wire:target="confirmEdit">Menyimpan...</span>
                </x-ui.button>
            </div>
        </div>
    </div>

    {{-- ── Lihat QR modal ───────────────────────────────────────────────── --}}
    <div
        x-data="{ show: $wire.entangle('showQrModal') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @keydown.escape.window="$wire.closeQrModal()"
        style="display: none"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-xs text-center"
            @click.stop
        >
            @if($viewingQr)
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                    {{ $viewingQr->participant->name }}
                </h3>
                <p class="mt-0.5 text-xs font-mono text-gray-400 dark:text-gray-500">
                    {{ $viewingQr->invitation?->invitation_code ?? '—' }}
                </p>

                <div class="mx-auto mt-4 w-56 h-56 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-3">
                    <img
                        src="{{ route('admin.events.participants.qr', [$event, $viewingQr]) }}"
                        alt="QR {{ $viewingQr->participant->name }}"
                        class="w-full h-full"
                    />
                </div>

                @if($viewingQr->invitation?->isRevoked())
                    <p class="mt-3 text-xs text-red-600 dark:text-red-400">
                        QR ini sudah dicabut, tidak berlaku lagi.
                    </p>
                @endif

                <div class="mt-5 flex items-center justify-center gap-2">
                    <x-ui.button
                        href="{{ route('admin.events.participants.qr', [$event, $viewingQr]) }}"
                        download="qr-{{ str($viewingQr->participant->name)->slug() }}.svg"
                    >
                        <x-tabler-download class="w-4 h-4" />
                        Download
                    </x-ui.button>
                    <x-ui.button wire:click="closeQrModal" variant="primary">Tutup</x-ui.button>
                </div>
            @endif
        </div>
    </div>
</div>
