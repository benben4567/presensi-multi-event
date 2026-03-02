<div x-data="{
    open: [true, false, false, false],
    toggle(i) { this.open[i] = !this.open[i]; }
}">
    <x-ui.header title="Panduan" />

    <div class="max-w-3xl space-y-3">

        {{-- ── 1. Mulai Cepat Operator ──────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <button type="button" @click="toggle(0)" class="flex w-full items-center justify-between px-5 py-4 text-left">
                <div class="flex items-center gap-3">
                    <x-tabler-scan class="w-5 h-5 flex-shrink-0 text-blue-500" />
                    <span class="font-semibold text-gray-800 dark:text-white">Mulai Cepat — Operator</span>
                </div>
                <x-tabler-chevron-down class="w-5 h-5 flex-shrink-0 text-gray-400 transition-transform duration-200" ::class="open[0] && 'rotate-180'" />
            </button>
            <div x-show="open[0]" x-collapse class="border-t border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <ol class="list-decimal list-inside space-y-2">
                        <li>Login ke sistem dengan akun operator yang telah diberikan oleh admin.</li>
                        <li>Pilih event yang sedang berjalan dari halaman utama operator.</li>
                        <li>Klik <strong>Scan</strong> untuk membuka mode pemindaian QR, atau <strong>Manual</strong> untuk pencarian nama / nomor HP.</li>
                        <li>Pada mode <strong>Scan</strong>: arahkan kamera/scanner HID ke QR code peserta. Sistem akan langsung merekam presensi secara otomatis.</li>
                        <li>Pada mode <strong>Manual</strong>: ketik sebagian nama atau nomor HP peserta, lalu klik tombol <strong>Catat Presensi</strong> pada baris yang sesuai.</li>
                        <li>Hasil scan ditampilkan secara real-time di layar (hijau = berhasil, kuning = peringatan, merah = ditolak).</li>
                    </ol>
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 dark:bg-amber-900/20 dark:border-amber-800">
                        <p class="font-medium text-amber-800 dark:text-amber-300">Penting</p>
                        <p class="mt-0.5 text-amber-700 dark:text-amber-400">Halaman presensi memerlukan koneksi internet aktif. Pastikan perangkat terhubung ke jaringan selama sesi berlangsung.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 2. Mulai Cepat Admin ─────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <button type="button" @click="toggle(1)" class="flex w-full items-center justify-between px-5 py-4 text-left">
                <div class="flex items-center gap-3">
                    <x-tabler-settings class="w-5 h-5 flex-shrink-0 text-blue-500" />
                    <span class="font-semibold text-gray-800 dark:text-white">Mulai Cepat — Admin</span>
                </div>
                <x-tabler-chevron-down class="w-5 h-5 flex-shrink-0 text-gray-400 transition-transform duration-200" ::class="open[1] && 'rotate-180'" />
            </button>
            <div x-show="open[1]" x-collapse class="border-t border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <ol class="list-decimal list-inside space-y-2">
                        <li>Login ke sistem dengan akun admin.</li>
                        <li>Buat event baru di menu <strong>Event → Tambah Event</strong>. Isi nama event, kode singkat (contoh: <code>SEMNAS26</code>), tanggal mulai/selesai, dan sesi-sesi yang
                            direncanakan.</li>
                        <li>Impor daftar peserta melalui <strong>Peserta → Impor Peserta</strong> dengan file Excel/CSV. Format kolom: <em>Nama</em> dan <em>Nomor HP</em> (opsional).</li>
                        <li>Undangan QR dibuat otomatis saat impor. Setiap peserta mendapat token unik dan kode undangan (contoh: <code>SEMNAS26-0001</code>).</li>
                        <li>Cetak kartu undangan massal via <strong>Export → Kartu Undangan (PDF)</strong>, atau cetak per peserta dengan tombol <strong>Cetak</strong> pada baris masing-masing.</li>
                        <li>Pantau presensi secara real-time di menu <strong>Dashboard</strong> dan unduh rekap di menu <strong>Laporan</strong>.</li>
                        <li>Kelola akun operator di menu <strong>Pengguna</strong>.</li>
                    </ol>
                    <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 dark:bg-gray-700/50 dark:border-gray-600">
                        <p class="font-medium text-gray-700 dark:text-gray-200 mb-2">Spesifikasi Kartu Undangan</p>
                        <table class="w-full text-xs text-gray-600 dark:text-gray-400">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300 w-1/3">Ukuran</td>
                                    <td class="py-1">ID Card B3 : 80 × 105 mm (portrait)</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">Format file</td>
                                    <td class="py-1">PDF — 1 halaman per peserta</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">Isi kartu</td>
                                    <td class="py-1">Nama event, QR code, nama peserta, nomor HP</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">Rekomendasi cetak</td>
                                    <td class="py-1">Kertas A4, potong setelah dicetak (2 kolom × 2 baris per lembar)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 3. Panduan Stiker Mapping ───────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <button type="button" @click="toggle(2)" class="flex w-full items-center justify-between px-5 py-4 text-left">
                <div class="flex items-center gap-3">
                    <x-tabler-sticker class="w-5 h-5 flex-shrink-0 text-blue-500" />
                    <span class="font-semibold text-gray-800 dark:text-white">Panduan Stiker Mapping</span>
                </div>
                <x-tabler-chevron-down class="w-5 h-5 flex-shrink-0 text-gray-400 transition-transform duration-200" ::class="open[2] && 'rotate-180'" />
            </button>
            <div x-show="open[2]" x-collapse class="border-t border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <p>Fitur stiker memungkinkan presensi tanpa membawa kartu cetak besar — cukup tempel stiker QR pada benda bawaan peserta (name tag, buku, dll.).</p>
                    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 dark:bg-amber-900/20 dark:border-amber-800">
                        <p class="font-medium text-amber-800 dark:text-amber-300">Prasyarat</p>
                        <p class="mt-0.5 text-amber-700 dark:text-amber-400">Fitur stiker hanya tersedia jika <strong>Kode Event</strong> diisi saat membuat event. Tanpa kode, menu Export Lembar
                            Stiker dan Mapping Stiker tidak akan menghasilkan file.</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 dark:bg-gray-700/50 dark:border-gray-600">
                        <p class="font-medium text-gray-700 dark:text-gray-200 mb-2">Spesifikasi Lembar Stiker</p>
                        <table class="w-full text-xs text-gray-600 dark:text-gray-400">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300 w-1/3">Ukuran per stiker</td>
                                    <td class="py-1">16 × 22 mm</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">Area QR</td>
                                    <td class="py-1">14 × 14 mm (di dalam stiker)</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">Ukuran lembar</td>
                                    <td class="py-1">A4 (210 × 297 mm)</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">Stiker per lembar</td>
                                    <td class="py-1">± 110 stiker (10 kolom × 11 baris)</td>
                                </tr>
                                <tr>
                                    <td class="py-1 pr-4 font-medium text-gray-700 dark:text-gray-300">Rekomendasi cetak</td>
                                    <td class="py-1">Kertas stiker A4 berperekat</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <ol class="list-decimal list-inside space-y-2">
                        <li>Pastikan event sudah memiliki <strong>Kode Event</strong> (atur di halaman Edit Event, maks. 10 karakter).</li>
                        <li>Di halaman <strong>Peserta</strong>, klik <strong>Export → Lembar Stiker (PDF)</strong>. File A4 berisi grid stiker kecil (16 × 22 mm) siap cetak.</li>
                        <li>Klik <strong>Export → Mapping Stiker (CSV)</strong> untuk mendapatkan tabel referensi: <em>Kode Undangan ↔ Nama Peserta</em>.</li>
                        <li>Cetak lembar stiker di kertas stiker A4 berperekat.</li>
                        <li>Gunakan file CSV sebagai panduan penempelan: temukan nama peserta di CSV, lalu tempel stiker dengan kode yang sesuai.</li>
                        <li>Saat presensi, operator cukup scan QR pada stiker — tidak perlu kartu fisik terpisah.</li>
                    </ol>
                    <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 dark:bg-blue-900/20 dark:border-blue-800">
                        <p class="font-medium text-blue-800 dark:text-blue-300">Format kode undangan</p>
                        <p class="mt-0.5 text-blue-700 dark:text-blue-400">Setiap kode berbentuk <code>KODE_EVENT-NNNN</code> (contoh: <code>SEMNAS26-0042</code>) dan bersifat unik per peserta per
                            event.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── 4. FAQ & Troubleshooting ────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <button type="button" @click="toggle(3)" class="flex w-full items-center justify-between px-5 py-4 text-left">
                <div class="flex items-center gap-3">
                    <x-tabler-help-circle class="w-5 h-5 flex-shrink-0 text-blue-500" />
                    <span class="font-semibold text-gray-800 dark:text-white">FAQ & Troubleshooting</span>
                </div>
                <x-tabler-chevron-down class="w-5 h-5 flex-shrink-0 text-gray-400 transition-transform duration-200" ::class="open[3] && 'rotate-180'" />
            </button>
            <div x-show="open[3]" x-collapse class="border-t border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ([
        [
            'q' => 'QR code tidak terbaca oleh scanner.',
            'a' => 'Pastikan layar peserta cukup terang dan tidak terpantul cahaya. Coba naikkan kecerahan layar. Jika QR tercetak, pastikan stiker/kartu tidak kusut atau terpotong.',
        ],
        [
            'q' => 'Peserta tidak ditemukan di mode Manual.',
            'a' => 'Periksa ejaan nama — coba ketik 2–3 karakter pertama. Alternatif, cari menggunakan nomor HP (format internasional, contoh: +6281234567890).',
        ],
        [
            'q' => 'Export PDF kartu undangan / lembar stiker menghasilkan file kosong atau redirect.',
            'a' => 'Pastikan sudah ada peserta dengan undangan aktif (token tidak dicabut). Admin dapat memeriksa di kolom QR pada tabel peserta.',
        ],
        [
            'q' => 'Scan berhasil terbaca tapi presensi tidak terekam.',
            'a' => 'Periksa koneksi internet perangkat operator. Semua pencatatan presensi membutuhkan koneksi aktif ke server.',
        ],
        [
            'q' => 'Tombol "Cetak" tidak muncul pada baris peserta.',
            'a' => 'Undangan peserta tersebut belum dibuat, atau sudah dicabut (revoked). Admin perlu memeriksa status undangan di halaman peserta.',
        ],
        [
            'q' => 'Peserta sudah scan tapi statusnya tetap "belum hadir" di laporan.',
            'a' => 'Laporan mengambil data real-time. Muat ulang halaman Laporan. Jika masih tidak muncul, cek menu Monitoring → Activity Log untuk memastikan rekaman presensi tersimpan.',
        ],
    ] as $faq)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <p class="font-medium text-gray-800 dark:text-white">{{ $faq['q'] }}</p>
                            <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $faq['a'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>
