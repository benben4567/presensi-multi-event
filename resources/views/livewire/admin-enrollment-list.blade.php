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
        </x-slot:actions>
    </x-ui.header>

    {{-- Search --}}
    <div class="mb-4 max-w-sm">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            placeholder="Cari nama atau nomor HP..."
        />
    </div>

    {{-- Table --}}
    <x-ui.table>
        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
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
                            <a
                                href="{{ route('admin.events.participants.qr', [$event, $enrollment]) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                <x-tabler-qrcode class="w-4 h-4" />
                                @if($enrollment->invitation->isRevoked())
                                    <span class="line-through opacity-50">Lihat QR</span>
                                @else
                                    Lihat QR
                                @endif
                            </a>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1.5">

                            {{-- Cetak kartu undangan individu --}}
                            @if($enrollment->invitation?->token && !$enrollment->invitation->isRevoked())
                                <x-ui.button
                                    href="{{ route('admin.events.participants.card', [$event, $enrollment]) }}"
                                    size="sm"
                                    target="_blank"
                                >
                                    Cetak
                                </x-ui.button>
                            @endif

                            {{-- Aktifkan Kembali (disabled or blacklisted) --}}
                            @if($enrollment->access_status !== \App\Enums\AccessStatus::Allowed)
                                <x-ui.button
                                    wire:click="confirmEnable({{ $enrollment->id }})"
                                    size="sm"
                                >
                                    Aktifkan Kembali
                                </x-ui.button>
                            @endif

                            {{-- Nonaktifkan (allowed or blacklisted) --}}
                            @if($enrollment->access_status !== \App\Enums\AccessStatus::Disabled)
                                <x-ui.button
                                    wire:click="confirmDisable({{ $enrollment->id }})"
                                    size="sm"
                                >
                                    Nonaktifkan
                                </x-ui.button>
                            @endif

                            {{-- Blacklist (allowed or disabled) --}}
                            @if($enrollment->access_status !== \App\Enums\AccessStatus::Blacklisted)
                                <x-ui.button
                                    wire:click="openBlacklist({{ $enrollment->id }})"
                                    variant="danger"
                                    size="sm"
                                >
                                    Blacklist
                                </x-ui.button>
                            @endif

                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.table-empty message="Belum ada peserta terdaftar." :colspan="5" />
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
                <x-ui.button wire:click="confirmBlacklist" variant="danger">Blacklist</x-ui.button>
            </div>
        </div>
    </div>
</div>
