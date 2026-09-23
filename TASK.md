# Task Backlog

Sumber: audit menyeluruh 2026-09-23 (kesesuaian plan, security, UX/fungsi).

## Perlu keputusan dulu (belum boleh dikerjakan sebelum dikonfirmasi)

- [x] **Token QR mentah tersimpan di `invitations.token`** — melanggar spec (`.ai/CONTEXT.md` line 60: harus token_hash saja). Keputusan: **opsi B — enkripsi kolom `token`** (cast `encrypted`), alur cetak ulang tidak berubah. Fixed 2026-09-23 (migration `2026_09_23_174828_encrypt_invitation_tokens`, model cast di `Invitation.php`).
- [ ] **`event_participants` punya UNIQUE(event_id, participant_id)** — spec bilang tidak boleh unique ("mengizinkan duplikasi enrollment"), tapi kode aplikasi sekarang asumsi 1 peserta = 1 enrollment/event. Keputusan: **biarkan constraint apa adanya** — catatan spec dianggap usang, tidak ada use-case jelas untuk duplikasi enrollment. Perlu update `.ai/CONTEXT.md` biar spec ngikut kode (belum dikerjakan).

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

## UX — Prioritas rendah

- [ ] Konsistensi timing validasi inline antar form admin
- [ ] Filter tanggal di halaman monitoring/activity log
- [ ] Ditemukan pas translate: `/register` publik masih aktif & reachable (siapa aja bisa bikin akun baru tanpa role — gak exploitable karena user tanpa role langsung mental balik ke login, tapi tetep nyampah data/rawan spam). Juga `/profile` masih pake layout Breeze default (`x-app-layout`), beda total dari `layouts.admin`/`layouts.ops` — gak ada link masuk dari nav manapun (halaman "mati", cuma bisa diakses ketik URL langsung). Pertimbangkan: matikan route register, atau restyle /profile pake layout app yang sebenernya.
