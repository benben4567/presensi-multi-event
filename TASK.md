# Task Backlog

Sumber: audit menyeluruh 2026-09-23 (kesesuaian plan, security, UX/fungsi).

## Perlu keputusan dulu (belum boleh dikerjakan sebelum dikonfirmasi)

- [x] **Token QR mentah tersimpan di `invitations.token`** — melanggar spec (`.ai/CONTEXT.md` line 60: harus token_hash saja). Keputusan: **opsi B — enkripsi kolom `token`** (cast `encrypted`), alur cetak ulang tidak berubah. Fixed 2026-09-23 (migration `2026_09_23_174828_encrypt_invitation_tokens`, model cast di `Invitation.php`).
- [ ] **`event_participants` punya UNIQUE(event_id, participant_id)** — spec bilang tidak boleh unique ("mengizinkan duplikasi enrollment"), tapi kode aplikasi sekarang asumsi 1 peserta = 1 enrollment/event. Keputusan: **biarkan constraint apa adanya** — catatan spec dianggap usang, tidak ada use-case jelas untuk duplikasi enrollment. Perlu update `.ai/CONTEXT.md` biar spec ngikut kode (belum dikerjakan).

## Bug/Security (siap dikerjakan, tinggal approve prioritas)

- [x] IDOR di `RecordAttendanceAction::executeManual()` — fixed 2026-09-23
- [ ] `AdminEnrollmentList` (disable/enable/blacklist/edit) tidak cek `event_id` cocok — low risk saat ini (admin akses blanket), tapi jadi jebakan kalau ada per-event admin scoping nanti
- [ ] Halaman `/profile` (Breeze default) masih bahasa Inggris — pelanggaran aturan "semua UI Bahasa Indonesia"

## UX — Operator (tablet/scanner), prioritas tinggi

- [ ] `OpsEventScan`: tambah guard biar gak double-submit kalau scanner HID auto-repeat / dobel scan cepat
- [ ] `OpsEventScan`: tambah feedback suara (beep sukses/gagal) + indikator visual "scanner siap/fokus"

## UX — Admin, prioritas menengah

- [ ] Tambah `wire:loading.attr="disabled"` konsisten di semua form Simpan (sekarang cuma ada di `OpsEventManual` & `AdminImportPeserta`) — target: `AdminEventForm`, `AdminPrintTemplateForm`, modal tambah/edit peserta
- [ ] Bulk action + filter status akses di `AdminEnrollmentList` (buat event 500+ peserta)
- [ ] Search/filter di halaman Laporan (`AdminLaporan`)

## UX — Prioritas rendah

- [ ] Konsistensi timing validasi inline antar form admin
- [ ] Filter tanggal di halaman monitoring/activity log
