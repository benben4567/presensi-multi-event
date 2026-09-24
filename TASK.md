# Task Backlog

Sumber: audit menyeluruh 2026-09-23 (kesesuaian plan, security, UX/fungsi).

## Perlu keputusan dulu (belum boleh dikerjakan sebelum dikonfirmasi)

- [x] **Token QR mentah tersimpan di `invitations.token`** — melanggar spec (`.ai/CONTEXT.md` line 60: harus token_hash saja). Keputusan: **opsi B — enkripsi kolom `token`** (cast `encrypted`), alur cetak ulang tidak berubah. Fixed 2026-09-23 (migration `2026_09_23_174828_encrypt_invitation_tokens`, model cast di `Invitation.php`).
- [ ] **`event_participants` punya UNIQUE(event_id, participant_id)** — spec bilang tidak boleh unique ("mengizinkan duplikasi enrollment"), tapi kode aplikasi sekarang asumsi 1 peserta = 1 enrollment/event. Keputusan: **biarkan constraint apa adanya** — catatan spec dianggap usang, tidak ada use-case jelas untuk duplikasi enrollment. Perlu update `.ai/CONTEXT.md` biar spec ngikut kode (belum dikerjakan).

## Fitur baru — Kirim undangan per peserta via email (selesai 2026-09-24)

Konteks: bulk export Kartu Undangan sebelumnya 1 PDF banyak halaman, dipakai admin buat 2 kebutuhan beda: (a) cetak fisik massal (ID card, dibagi langsung), (b) broadcast digital per peserta — untuk (b) admin manual split+rename+kirim satu-satu, makin ribet kalau tiap peserta juga butuh halaman info tambahan (harus merge manual juga). Solusi: 2 mode terpisah.

- [x] **Mode "Cetak massal"** — tidak berubah (`InvitationCardController::export`).
- [x] **Mode "Kirim per peserta" (baru)** — dropdown "Kirim Undangan via Email" di `AdminEnrollmentList` men-dispatch `SendInvitationCardEmailJob` per peserta (queue `database`), tiap job build PDF (kartu + halaman info statis PDF event bila ada, di-merge via `setasign/fpdi`) lalu `Mail::send(InvitationCardMail)`. Peserta tanpa email/undangan tidak valid otomatis di-skip, ringkasan "X dikirim ke antrean, Y dilewati" muncul di toast.
  - Package **`setasign/fpdi`** terpasang (lihat `app/Support/FpdiCompatibleFpdf.php` + `FpdfExtended.php` untuk kenapa perlu 2 kelas — Codedge `Fpdf` bukan subclass `\FPDF` global, jadi FPDI di-compose lewat trait, bukan extend `\setasign\Fpdi\Fpdi` langsung).
  - Kolom baru: `participants.email` (nullable), `events.invitation_info_pdf_path` (nullable, upload di `AdminEventForm`), `event_participants.invitation_email_sent_at` (tracking, dipakai buat hindari double-send kalau nanti ada UI resend-gagal-saja — belum ada UI khusus itu).
  - Sumber email: kolom `email` opsional di import Excel + field email di modal tambah/edit peserta manual (`AdminEnrollmentList`).
  - Kartu-drawing logic diekstrak dari `InvitationCardController` ke `App\Support\InvitationCardRenderer` (dipakai bareng oleh controller & `BuildInvitationEmailPdfAction`) — DRY, dipakai 2 tempat.
  - Monitoring pengiriman lewat halaman existing Monitoring → Queue (`romanzipp/laravel-queue-monitor`, job pakai trait `IsMonitored`).
  - Belum ada: tombol resend khusus yang gagal (baru bisa lihat status di Queue Monitor), tombol hapus PDF info tanpa upload pengganti.
- [x] **Cetak manual kartu + lampiran (non-email)** — tombol "Cetak + Lampiran" per baris di `AdminEnrollmentList` (muncul cuma kalau event punya `invitation_info_pdf_path`), route baru `InvitationCardController::printWithAttachment` reuse `BuildInvitationEmailPdfAction` yang sama dipakai job email — output PDF inline (kartu + lampiran ter-merge), tanpa perlu kirim email.

## Bug/Security (siap dikerjakan, tinggal approve prioritas)

- [x] IDOR di `RecordAttendanceAction::executeManual()` — fixed 2026-09-23
- [x] `AdminEnrollmentList` (disable/enable/blacklist/edit) tidak cek `event_id` cocok — fixed 2026-09-23 (helper `enrollmentInThisEvent()`, 404 kalau id bukan milik event ini)
- [x] Halaman `/profile` + semua halaman auth Breeze masih bahasa Inggris — fixed 2026-09-23. Root cause lebih luas dari dugaan: `APP_LOCALE` default `en` + gak ada file `lang/id/*`, jadi SEMUA pesan validasi default Laravel (bukan cuma /profile) tampil bahasa Inggris di form manapun yang gak kasih custom message. Fix: `APP_LOCALE=id` + `lang/id/{validation,auth,passwords}.php` (root-cause, nutup celah di semua form sekaligus) + translate teks blade di `resources/views/{auth,profile,layouts/navigation}.blade.php`.

## UX — Operator (tablet/scanner), prioritas tinggi

- [x] `OpsEventScan`: guard double-submit (input disabled selama processing) — fixed 2026-09-23
- [x] `OpsEventScan`: feedback suara (beep, Web Audio API, beda nada accepted/warning/rejected) + indikator visual "Siap menerima scan" / "Memproses..." / "Nonaktif" — fixed 2026-09-23

## UX — Admin, prioritas menengah

- [x] Tambah guard double-submit + label swap "Menyimpan..." konsisten di semua form Simpan — fixed 2026-09-23: `AdminEventForm`, `AdminUserForm`, `AdminPrintTemplateForm` (Alpine `saving` flag, bukan `wire:loading` karena submitnya lewat Alpine method), modal tambah/edit/blacklist peserta di `AdminEnrollmentList`
- [x] Bulk action + filter status akses di `AdminEnrollmentList` — fixed 2026-09-23: dropdown filter status (Semua/Aktif/Nonaktif/Blacklist), checkbox per baris + "pilih semua" (pilih semua yang cocok filter/pencarian, bukan cuma halaman aktif), bulk Aktifkan/Nonaktifkan Terpilih
- [x] Search/filter di halaman Laporan (`AdminLaporan`) — fixed 2026-09-23: search nama/no HP + filter status kehadiran (Semua/Hadir/Tidak Hadir)

## Optimasi — Ukuran file PDF QR (siap dikerjakan)

- [ ] **PDF Kartu Undangan & Lembar Stiker kegedean** — root cause: `generateQrPng()` (`InvitationCardController.php:414-443`) pakai `imagecreatetruecolor()` buat gambar QR yang cuma hitam-putih → PNG 24-bit RGB, padahal FPDF embed stream PNG apa adanya tanpa re-compress (`Image()`). Fix: ganti `imagecreatetruecolor()` → `imagecreate()` (palette 2 warna) supaya GD nulis PNG 1-bit, plus `imagepng($img, $tmpFile, 9)` (max compression level). Zero risk ke kualitas/readability scan — cuma ganti mode storage pixel, bukan resolusi/isi QR. Estimasi size turun ~10-20x per QR image.
- [ ] (opsional, butuh approval + test cetak+scan fisik) turun `pixelSize` (600 kartu / 300 stiker) kalau ketauan oversampled buat ukuran cetak 34mm/14mm — trade-off ke reliability HID scanner, jangan asal potong tanpa test.

## UX — Prioritas rendah

- [x] Konsistensi timing validasi inline antar form admin — fixed 2026-09-23: `AdminEventForm` & `AdminUserForm` sekarang validasi per-field pas blur (`wire:model.blur` + hook generik `updated()`), samain pola sama `AdminPrintTemplateForm::updatedPhoto()`
- [x] Filter tanggal di halaman monitoring/activity log — fixed 2026-09-23: filter "Dari tanggal"/"Sampai tanggal" + tombol reset di `AdminMonitoringActivity`
- [x] `/register` publik — fixed 2026-09-23: route dimatiin (dihapus dari `routes/auth.php`), `RegisteredUserController`, `auth/register.blade.php`, `welcome.blade.php` (juga orphan, gak ada route yang render), dan `RegistrationTest.php` (test fitur yang sengaja dimatiin) dihapus semua. Semua akun tetep dibuat admin lewat `AdminUserForm`.
- [x] `/profile` layout inconsistency — fixed 2026-09-23: dikonversi total dari 3 partial Blade + `ProfileController` (pola Breeze lama) jadi 1 komponen Livewire (`ProfileEdit`), pake `layouts.admin` buat admin / `layouts.ops` buat operator (dipilih dinamis dari role). Dead code ikut dibersihkan: `ProfileController`, `ProfileUpdateRequest`, `layouts/app.blade.php`, `layouts/navigation.blade.php`, `dashboard.blade.php` (ternyata juga orphan), `app/View/Components/AppLayout.php`.
