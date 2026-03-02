# Presensi Multi-Event (Web + HID Scanner) — AI Context/Guide (Final Terpadu)

> **Aturan UI**: Semua teks UI (menu, label, tombol, pesan, placeholder) wajib menggunakan **Bahasa Indonesia**.  
> **Tujuan**: Dokumen ini menjadi context/guide untuk AI agent agar implementasi tidak halu dan konsisten.

---

## 0) Goal & Perangkat
Membangun aplikasi **Presensi Multi-Event** berbasis **Web App** untuk operasional lapangan dengan:
- **Android Tablet**
- **QR Scanner eksternal** mode **HID keyboard** (scanner mengetik ke input lalu Enter)
- Operasional presensi **online** (butuh koneksi internet stabil)

---

## 1) Istilah UI (Konsisten)
- **Presensi**
- **Peserta**
- **Hari**

---

## 2) Business Rules (Final)

### 2.1 Event Multi-Hari
- Event memiliki `start_at` dan `end_at`
- Event bisa 1 hari atau **multi-hari**
- Presensi dihitung **per Hari**
  - Peserta harus melakukan check-in **setiap Hari**

### 2.2 Hari (Session Harian)
- Untuk event multi-hari, sistem membuat **Hari** otomatis (session harian)
- Nama Hari jelas dengan format tanggal lengkap:
  - contoh: **“Sabtu, 28 Februari 2026”**
- Operator memilih Hari **manual**
- Default Hari pada operator:
  - **auto-select Hari ini** (tanggal server) jika tersedia

### 2.3 Mode Presensi
- Event mendukung:
  - **Check-in saja**
  - **Check-in + Check-out**
- Dedupe berdasarkan:
  - `(event_participant_id, session_id, action)`
- Duplicate:
  - tidak menambah presensi
  - tampil sebagai **peringatan** (kuning) dan tetap menampilkan detail peserta

### 2.4 Check-out Tanpa Check-in
- Check-out **tetap boleh** walaupun belum ada check-in pada Hari yang sama
- UI peringatan:
  - “Check-out tercatat, namun peserta belum check-in pada hari ini.”
- Check-out tetap dicatat (attendance log dibuat)

### 2.5 QR Token Opaque (Tersamarkan)
- QR unik per peserta per event (per enrollment)
- **1 QR berlaku untuk semua Hari** selama event
- Payload QR harus **opaque** (tidak memuat data event/peserta):
  - format: `itsk:att:v1:<TOKEN>`
- DB menyimpan **token_hash/fingerprint**, bukan token mentah

### 2.6 Expiry Token
- Token berlaku hingga akhir event:
  - `expires_at = events.end_at`
- Scan ditolak jika:
  - token tidak dikenal
  - event mismatch
  - expired
  - revoked
  - duplicate (warning)

### 2.7 Disable / Blacklist Peserta (Per Event)
Status akses enrollment:
- `allowed` (default)
- `disabled`
- `blacklisted` (alasan wajib, max 100 karakter)

Aturan:
- disabled/blacklisted → invitation **di-revoke** (`revoked_at` diisi)
- Saat scan revoked → operator melihat alasan (pendek)
- Kembali `allowed` → **UNREVOKE** (QR lama valid lagi) dan alasan dibersihkan/overwrite

### 2.8 Grace Override Setelah Event Selesai
- Jika `now > end_at`, presensi tetap bisa dilakukan jika admin override
- Override via tombol cepat di halaman event:
  - “Buka presensi …” pilih durasi (15/30/60/120 menit)
- Simpan `override_until = now + durasi`
- Valid jika:
  - `now <= end_at` OR `now <= override_until`

### 2.9 Manual Search (Darurat)
- Operator memiliki halaman manual terpisah:
  - `/ops/events/{event}/manual`
- Operator bisa cari peserta (nama/no HP) dan melakukan presensi manual **untuk kondisi mendesak**
- Manual action:
  - **tidak bypass** validasi revoked/expired/event mismatch/dedupe
  - tetap audit di `scan_attempts`
- Catatan manual (`manual_note`) **opsional**

---

## 3) Audit Logging (Wajib)

### 3.1 `attendance_logs`
- Mencatat presensi yang benar-benar “terhitung”
- Duplicate **tidak** membuat attendance log baru

### 3.2 `scan_attempts`
- Mencatat **semua** aktivitas presensi (accepted/rejected/warning), termasuk manual
- `source = qr|manual`
- `message` disimpan **full text Bahasa Indonesia**
- Fingerprint token disimpan (hash), bukan token mentah

---

## 4) Web Device UUID (Wajib)
Walaupun web, tetap gunakan `device_uuid` untuk:
- audit perangkat
- rate limiting per device
- monitoring/analitik operasional

Aturan:
- `device_uuid` dibuat sekali di browser dan disimpan di **localStorage**
- dikirim di setiap aksi scan/manual
- server melakukan upsert ke tabel `devices` dan update `last_seen_at`

---

## 5) Format Impor Excel Peserta
Kolom wajib:
- `nama`
- `no_hp`

Kolom opsional:
- semua kolom lain masuk `meta` JSON

Aturan:
- `no_hp` invalid/kosong → **skip + catat error**
- duplikat dalam event (berdasarkan `phone_e164`) → **auto-skip + informatif**
- no HP distandarkan ke **E.164**

---

## 6) Tech Stack & Library (Final)

### 6.1 Stack
- Laravel 12
- Livewire 4 (class + blade)
- Auth: Laravel Breeze (Blade)
- UI: Tailwind CSS + Flowbite
- Icon: Tabler Icons (Blade)
- Infra: Docker (Nginx, PHP-FPM 8.3, MySQL 8, Redis 7)

### 6.2 Library yang digunakan
1) RBAC: `spatie/laravel-permission`
2) Error Log: `opcodesio/log-viewer`
3) Activity Log: `spatie/laravel-activitylog`
4) Queue Monitoring: `romanzipp/laravel-queue-monitor`
5) QR Generator: `simplesoftwareio/simple-qrcode`
6) Excel: `rap2hpoutre/fast-excel`
7) PDF: `codedge/laravel-fpdf`
8) Icon: `secondnetwork/blade-tabler-icons`

Disarankan (opsional tapi kuat):
- Phone E.164: `giggsey/libphonenumber-for-php`

### 6.3 Redis (Recommended)
- cache lookup token hash (mempercepat scan)
- rate limiting per device/operator

---

## 7) Fitur & Menu

### 7.1 RBAC
- Role: `admin`, `operator`

### 7.2 Menu Admin
- Dashboard
- Event
- Peserta (per event)
- Presensi (log/rekap)
- Laporan (export)
- Pengguna
- Monitoring
  - Error Log
  - Activity Log
  - Queue Monitoring

### 7.3 Operator
- Mode Scan: `/ops/events/{event}/scan`
- Presensi Manual: `/ops/events/{event}/manual`

---

## 8) DRY Architecture (Wajib)

### 8.1 Reusable UI Components
Gunakan reusable UI components untuk:
- Sidebar item (icon + label + active state)
- Header (judul + slot actions)
- Button variant konsisten (**Primary hanya “Simpan”**)
- Alert status (success/warn/error)
- Badge status
- Table wrapper + empty state + pagination wrapper
- Global confirm dialog
- Global toast/notification

### 8.2 Single Source of Truth — `RecordAttendanceAction`
Semua presensi (QR & manual) wajib lewat 1 action:
- Input:
  - event_id, session_id(Hari), action(check_in/out), source(qr/manual)
  - raw_token **atau** event_participant_id
  - device_uuid, operator_user_id, manual_note?
- Output:
  - status(accepted|rejected|warning), code, message (Indonesia)
  - participant_preview (nama, no_hp, meta terpilih)
  - id scan_attempt dan (opsional) id attendance_log
- Rule:
  - selalu tulis `scan_attempts`
  - accepted → create `attendance_logs`
  - duplicate → warning, tidak create attendance log
  - checkout tanpa checkin → warning, tetap create attendance log

---

## 9) Konsep Struktur Layout Flowbite (Tanpa Kode)

### 9.1 Prinsip Umum
- 2 layout utama:
  - Admin: sidebar fixed + header sticky
  - Operator: header minimal + fokus presensi
- Dark/light di header, simpan localStorage, apply sebelum render
- Semua ikon menu konsisten Tabler icons
- Semua copywriting bahasa Indonesia
- Komponen Flowbite berbasis JS (dropdown/drawer/modal):
  - harus aman terhadap re-render Livewire (area stabil atau re-init via event)

### 9.2 Admin Layout
- **Content full width** (tanpa max-width container)
- Sidebar fixed (desktop), menjadi drawer off-canvas (mobile)
- Header menampilkan **judul halaman saja**

### 9.3 Operator Layout
- Header menampilkan:
  - “Presensi / {Nama Event}”
  - **Hari aktif**
  - tombol dinamis: Scan ↔ Manual
  - toggle tema
  - tombol keluar
- Konten fokus: input scan besar, alert besar, hasil scan terakhir

---

## 10) Peta Komponen UI Reusable (Halaman → Komponen)

### 10.1 Dipakai Hampir Semua Halaman
- Layout admin/ops
- Header + dark toggle
- Alert, badge
- Table + pagination + empty state
- Global confirm dialog
- Global toast

### 10.2 Halaman Admin (Ringkas)
- Event list: header + table + badge status + confirm dialog
- Event form: header(“Simpan”) + input/select/toggle + setting operator_display_fields + override button
- Import peserta: input file + alert + table summary
- Enrollment list: table + badge access + modal/confirm untuk disable/blacklist/enable
- Presensi/laporan: filter select event/Hari + table + export button
- Monitoring: halaman akses log-viewer + activity log table + queue monitor

### 10.3 Halaman Operator (Ringkas)
- Scan: select Hari + select mode + input scan + alert besar + list scan terakhir
- Manual: input search + table hasil + select Hari/mode + tombol aksi + manual_note opsional

Aturan:
- meta field kosong **disembunyikan**
- duplicate = **peringatan** (bukan error)

---

## 11) MVP Step-by-Step (Ringkas)

### Phase 0: Fondasi
- Laravel 12 + Livewire 4 + Breeze
- Tailwind + Flowbite + Tabler icons
- Layout admin & ops + dark toggle

### Phase 1: RBAC
- spatie-permission (role admin/operator)
- route protection + redirect login

### Phase 2: DB Model
- migrations & models inti (events, sessions, participants, enrollment, invitations, logs, devices)

### Phase 3: DRY UI Components
- set reusable UI components + global confirm/toast

### Phase 4: Event Management
- CRUD event + generate Hari + operator_display_fields + override button

### Phase 5: Impor Peserta + QR
- fast-excel import + E.164 normalization + create invitation + generate QR

### Phase 6: Access Control
- disable/blacklist/enable → revoke/unrevoke invitation

### Phase 7: RecordAttendanceAction
- 1 action untuk QR & manual + scan_attempts wajib + dedupe + warning rules

### Phase 8: Operator Pages
- scan screen + manual screen + device_uuid localStorage

### Phase 9: Reporting
- rekap per Hari + export minimal 1 format (Excel/PDF)

### Phase 10: Monitoring
- log-viewer + activity log + queue monitor

---

## 12) Skema Database (Final)

### 12.1 `events`
- id (PK)
- code (string, nullable, UNIQUE)
- name (string)
- start_at (datetime)
- end_at (datetime)
- status (enum: draft|open|closed)
- override_until (datetime, nullable)
- settings (json: enable_checkout, operator_display_fields)
- created_by (FK users.id, nullable)
- updated_by (FK users.id, nullable)
- timestamps
Index: status, (start_at, end_at)

### 12.2 `event_sessions` (Hari)
- id (PK)
- event_id (FK)
- name (string)
- start_at (datetime)
- end_at (datetime)
- type (enum: day)
- timestamps
Index: (event_id, start_at, end_at)
Constraint: UNIQUE (event_id, start_at) (disarankan)

### 12.3 `participants`
- id (PK)
- name (string)
- phone_e164 (string, nullable)
- meta (json, nullable)
- timestamps
Index: phone_e164 (non-unique)

### 12.4 `event_participants` (Enrollment)
- id (PK)
- event_id (FK)
- participant_id (FK)
- meta (json, nullable)
- access_status (allowed|disabled|blacklisted)
- access_reason (string(100), nullable)
- access_updated_at (datetime, nullable)
- access_updated_by (FK users.id, nullable)
- timestamps
Index: (event_id, participant_id), (event_id, access_status)
Catatan: tidak UNIQUE (event_id, participant_id) untuk mengizinkan duplikasi enrollment

### 12.5 `invitations`
- id (PK)
- event_participant_id (FK, UNIQUE)
- token_hash (UNIQUE)
- issued_at (datetime)
- expires_at (datetime)
- revoked_at (datetime, nullable)
- revoked_reason (string(100), nullable)
- revoked_by (FK users.id, nullable)
- timestamps
Index: expires_at, revoked_at

### 12.6 `devices`
- id (PK)
- device_uuid (string, UNIQUE)
- name (string, nullable)
- last_seen_at (datetime, nullable)
- timestamps

### 12.7 `attendance_logs`
- id (PK)
- event_id (FK)
- event_participant_id (FK)
- session_id (FK event_sessions)
- action (check_in|check_out)
- scanned_at (datetime)
- device_id (FK devices)
- operator_user_id (FK users)
- timestamps
Index: (event_id, session_id, action), (event_participant_id, session_id, action)
Constraint: UNIQUE (event_participant_id, session_id, action) (disarankan)

### 12.8 `scan_attempts`
- id (PK)
- event_id (FK)
- event_participant_id (FK, nullable)
- session_id (FK, nullable)
- device_uuid (string, nullable)
- operator_user_id (FK users, nullable)
- source (qr|manual)
- result (accepted|rejected|warning)
- code (string)
- message (string) — full text Indonesia
- token_fingerprint (string, nullable)
- manual_note (string, nullable)
- scanned_at (datetime)
- timestamps
Index: (event_id, scanned_at), (event_participant_id, scanned_at), (result, code), device_uuid

### 12.9 RBAC (spatie/laravel-permission)
Menggunakan tabel bawaan:
- roles, permissions, model_has_roles, model_has_permissions, role_has_permissions

### 12.10 Activity Log (spatie/laravel-activitylog)
Menggunakan tabel bawaan:
- activity_log

### 12.11 Queue Monitoring (romanzipp/laravel-queue-monitor)
Menggunakan tabel bawaan package queue-monitor.

---

## 13) Daftar File (Starter Blueprint)

### 13.1 App Layer
app/
- Actions/
  - RecordAttendanceAction.php
  - SetEnrollmentAccessAction.php
  - ImportPesertaAction.php
- Enums/
  - AccessStatus.php
  - AttendanceAction.php
  - ScanResultCode.php
  - ScanSource.php
- Livewire/ (flat)
  - AdminEventIndex.php
  - AdminEventForm.php
  - AdminEnrollmentList.php
  - AdminImportPeserta.php
  - AdminLaporanPresensi.php
  - AdminUsers.php
  - AdminMonitoringErrorLog.php
  - AdminMonitoringActivityLog.php
  - AdminMonitoringQueue.php
  - OpsScanPresensi.php
  - OpsManualPresensi.php
  - UiToast.php
  - UiConfirmDialog.php
- Models/
  - Event.php
  - EventSession.php
  - Participant.php
  - EventParticipant.php
  - Invitation.php
  - AttendanceLog.php
  - ScanAttempt.php
  - Device.php

### 13.2 Database
database/
- migrations/
  - create_events_table.php
  - create_event_sessions_table.php
  - create_participants_table.php
  - create_event_participants_table.php
  - create_invitations_table.php
  - create_devices_table.php
  - create_attendance_logs_table.php
  - create_scan_attempts_table.php
- seeders/
  - RoleSeeder.php
  - UserSeeder.php (opsional)

### 13.3 Routes
routes/
- web.php (admin routes, ops routes, monitoring routes)
- auth.php (Breeze)

### 13.4 Views & Components
resources/views/
- layouts/
  - admin.blade.php
  - ops.blade.php
- components/ui/
  - sidebar-item
  - header
  - dark-toggle
  - button
  - input
  - select
  - textarea
  - alert
  - badge
  - table
  - pagination
  - modal (opsional)
  - confirm-dialog
- livewire/
  - (blade view untuk masing-masing livewire component)

### 13.5 Frontend Assets
resources/
- css/app.css
- js/app.js (Flowbite init + theme + device_uuid init)
tailwind.config.js
vite.config.js

---

## 14) UI Copywriting (Bahasa Indonesia)
Contoh pesan hasil presensi:
- “Berhasil: Presensi berhasil dicatat.”
- “Duplikat: Peserta sudah melakukan presensi pada hari ini.”
- “Ditolak: Kode QR tidak valid.”
- “Ditolak: Kode QR sudah kedaluwarsa.”
- “Ditolak: Peserta dinonaktifkan. (alasan)”
- “Ditolak: Peserta diblacklist. (alasan)”
- “Peringatan: Check-out tercatat, namun peserta belum check-in pada hari ini.”

Semua tombol/label:
- “Simpan”, “Batal”, “Hapus”, “Impor Peserta”, “Buka Presensi”, “Nonaktifkan”, “Blacklist”, “Aktifkan Kembali”, “Keluar”
