<div
    x-data="{
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
            Pilih sesi terlebih dahulu sebelum mencatat presensi.
        </x-ui.alert>
    @else
        {{-- Result banner --}}
        @if($resultOutcome)
            <div class="mb-4">
                <x-ui.alert
                    :type="$resultOutcome === 'accepted' ? 'success' : ($resultOutcome === 'warning' ? 'warning' : 'error')"
                    :dismissible="true"
                >
                    {{ $resultMessage }}
                </x-ui.alert>
            </div>
        @endif

        @if($selectedEnrollment)
            {{-- Selected participant confirmation card --}}
            <div class="mb-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $selectedEnrollment->participant->name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                            {{ $selectedEnrollment->participant->phone_e164 ?? '—' }}
                        </p>
                        {{-- Existing attendance status badges --}}
                        @if($existingCheckIn || $existingCheckOut)
                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                @if($existingCheckIn)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                        <x-tabler-circle-check class="w-3.5 h-3.5" />
                                        Sudah check-in
                                    </span>
                                @endif
                                @if($existingCheckOut)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                        <x-tabler-circle-check class="w-3.5 h-3.5" />
                                        Sudah check-out
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <button
                        wire:click="clearSelection"
                        class="flex-shrink-0 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        title="Ganti peserta"
                    >
                        <x-tabler-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Action toggle --}}
                <div class="flex gap-2 mb-4">
                    <button
                        wire:click="$set('action', 'check_in')"
                        @class([
                            'flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-colors border-2',
                            'bg-green-600 text-white border-green-600 dark:bg-green-600' => $action === 'check_in',
                            'bg-white text-gray-700 border-gray-200 hover:border-green-300 hover:bg-green-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-green-900/20' => $action !== 'check_in',
                        ])
                    >
                        <x-tabler-login class="w-4 h-4 inline-block me-1.5" />
                        Check In
                    </button>
                    <button
                        wire:click="$set('action', 'check_out')"
                        @class([
                            'flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-colors border-2',
                            'bg-blue-600 text-white border-blue-600 dark:bg-blue-600' => $action === 'check_out',
                            'bg-white text-gray-700 border-gray-200 hover:border-blue-300 hover:bg-blue-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-blue-900/20' => $action !== 'check_out',
                        ])
                    >
                        <x-tabler-logout class="w-4 h-4 inline-block me-1.5" />
                        Check Out
                    </button>
                </div>

                {{-- Optional note --}}
                <div class="mb-4">
                    <x-ui.input
                        wire:model="manualNote"
                        placeholder="Catatan (opsional, maks. 200 karakter)"
                        maxlength="200"
                    />
                    @error('manualNote')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <x-ui.button
                    wire:click="submitManual"
                    wire:loading.attr="disabled"
                    variant="primary"
                    class="w-full justify-center"
                >
                    <span wire:loading.remove wire:target="submitManual">Simpan Presensi</span>
                    <span wire:loading wire:target="submitManual">Menyimpan...</span>
                </x-ui.button>

                @error('sessionId')
                    <p class="mt-2 text-xs text-center text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @else
            {{-- Search --}}
            <div class="mb-3">
                <x-ui.input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau nomor HP peserta..."
                    autofocus
                />
            </div>

            @if(mb_strlen($search) >= 2)
                @if($enrollments->isEmpty())
                    <p class="text-sm text-center text-gray-500 dark:text-gray-400 py-6">
                        Tidak ada peserta ditemukan untuk "<strong>{{ $search }}</strong>".
                    </p>
                @else
                    <div class="space-y-1.5">
                        @foreach($enrollments as $enrollment)
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
                            <button
                                wire:click="selectEnrollment({{ $enrollment->id }})"
                                class="w-full text-left flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">
                                        {{ $enrollment->participant->name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                                        {{ $enrollment->participant->phone_e164 ?? '—' }}
                                    </p>
                                </div>
                                <x-ui.badge :color="$statusColor">{{ $statusLabel }}</x-ui.badge>
                            </button>
                        @endforeach
                    </div>
                @endif
            @elseif(mb_strlen($search) > 0)
                <p class="text-sm text-gray-400 dark:text-gray-500 px-1">
                    Ketik minimal 2 karakter untuk mencari.
                </p>
            @endif
        @endif
    @endif
</div>
