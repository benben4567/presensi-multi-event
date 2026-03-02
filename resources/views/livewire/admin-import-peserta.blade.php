<div>
    <x-ui.header :title="'Impor Peserta — ' . $event->name">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.events.participants', $event) }}">
                <x-tabler-arrow-left class="w-4 h-4" />
                Kembali
            </x-ui.button>
        </x-slot:actions>
    </x-ui.header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Upload form --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Unggah File</h3>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Upload file Excel (<code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">.xlsx</code>,
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">.xls</code>,
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">.csv</code>).
                    Kolom wajib: <strong>nama</strong>, <strong>no_hp</strong>.
                    Kolom lain otomatis masuk ke data meta peserta.
                </p>

                <div>
                    <input
                        type="file"
                        wire:model="file"
                        accept=".xlsx,.xls,.csv"
                        class="block w-full text-sm text-gray-600 dark:text-gray-300
                               file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0
                               file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                               hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300"
                    />
                    @error('file')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div wire:loading wire:target="file" class="mt-1 text-xs text-gray-500">Mengupload...</div>
                </div>

                <x-ui.button wire:click="import" variant="primary">
                    <x-tabler-file-import class="w-4 h-4" />
                    <span wire:loading.remove wire:target="import">Impor Peserta</span>
                    <span wire:loading wire:target="import">Memproses...</span>
                </x-ui.button>
            </div>

            {{-- Results --}}
            @if($result !== null)
                <div class="mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Hasil Impor</h3>

                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-3">
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $result['imported'] }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">Ditambahkan</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-3">
                            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $result['skipped'] }}</p>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-0.5">Dilewati (duplikat)</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-3">
                            <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $result['errors'] }}</p>
                            <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">Error</p>
                        </div>
                    </div>

                    {{-- Error rows --}}
                    @if(!empty($result['error_rows']))
                        <div>
                            <h4 class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide mb-2">Baris Error</h4>
                            <x-ui.table>
                                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2">Baris</th>
                                        <th class="px-3 py-2">Nama</th>
                                        <th class="px-3 py-2">No HP</th>
                                        <th class="px-3 py-2">Alasan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($result['error_rows'] as $row)
                                        <tr class="bg-white dark:bg-gray-800">
                                            <td class="px-3 py-2 text-xs text-gray-500">{{ $row['baris'] }}</td>
                                            <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{{ $row['nama'] ?? '—' }}</td>
                                            <td class="px-3 py-2 text-xs font-mono text-gray-600 dark:text-gray-300">{{ $row['no_hp'] ?? '—' }}</td>
                                            <td class="px-3 py-2 text-xs text-red-600 dark:text-red-400">{{ $row['alasan'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.table>
                        </div>
                    @endif

                    {{-- Skipped rows --}}
                    @if(!empty($result['skipped_rows']))
                        <div>
                            <h4 class="text-xs font-semibold text-yellow-600 dark:text-yellow-400 uppercase tracking-wide mb-2">Dilewati (Sudah Terdaftar)</h4>
                            <ul class="space-y-1">
                                @foreach($result['skipped_rows'] as $row)
                                    <li class="text-xs text-gray-600 dark:text-gray-300">
                                        Baris {{ $row['baris'] }}: {{ $row['nama'] }} ({{ $row['no_hp'] }})
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <x-ui.button href="{{ route('admin.events.participants', $event) }}" variant="primary">
                        Lihat Daftar Peserta
                    </x-ui.button>
                </div>
            @endif
        </div>

        {{-- Sidebar: format guide --}}
        <div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Format File</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Baris pertama adalah header. Contoh:</p>
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-mono">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-2 py-1.5 text-left">nama</th>
                                <th class="px-2 py-1.5 text-left">no_hp</th>
                                <th class="px-2 py-1.5 text-left">unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-500 dark:text-gray-400">
                            <tr>
                                <td class="px-2 py-1">Budi Santoso</td>
                                <td class="px-2 py-1">08123456789</td>
                                <td class="px-2 py-1">IT</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1">Ani Rahayu</td>
                                <td class="px-2 py-1">+62812345678</td>
                                <td class="px-2 py-1">HR</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <ul class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <li class="flex items-start gap-1.5">
                        <x-tabler-circle-check class="w-3.5 h-3.5 text-green-500 mt-0.5 flex-shrink-0" />
                        Nomor HP distandarkan ke format E.164
                    </li>
                    <li class="flex items-start gap-1.5">
                        <x-tabler-circle-check class="w-3.5 h-3.5 text-green-500 mt-0.5 flex-shrink-0" />
                        Nomor duplikat dalam event akan dilewati
                    </li>
                    <li class="flex items-start gap-1.5">
                        <x-tabler-circle-check class="w-3.5 h-3.5 text-green-500 mt-0.5 flex-shrink-0" />
                        Kolom selain nama & no_hp masuk ke data meta
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>
