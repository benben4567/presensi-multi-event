# Presensi Multi-Event

Aplikasi web **presensi berbasis QR Code** untuk event multi-hari. Dirancang untuk dioperasikan di **tablet Android** dengan **QR scanner eksternal mode HID keyboard**.

> **Developer:** Benben Bagus Prasetyo A. &nbsp;|&nbsp; &copy; 2026

---

## Fitur Utama

- **Multi-Event & Multi-Hari** — satu sistem untuk banyak event; presensi dihitung per hari
- **Scan QR otomatis** — scanner HID langsung memproses tanpa klik tombol
- **Check-in & Check-out** — dikonfigurasi per event
- **Impor Peserta via Excel** — kolom `nama` + `no_hp`; normalisasi E.164 otomatis
- **Kartu Undangan QR (PDF)** — cetak individu atau ekspor massal seluruh peserta
- **Lembar Stiker (PDF)** — label 16 × 22 mm, ±110 label per A4
- **Presensi Manual** — fallback pencarian peserta by nama/no HP
- **Access Control** — nonaktifkan atau blacklist peserta per event; QR ikut di-revoke/unrevoke
- **Grace Override** — buka ulang presensi setelah event berakhir (15/30/60/120 menit)
- **Laporan & Ekspor Excel** — rekap kehadiran per sesi
- **Monitoring** — Error Log, Activity Log, Queue Monitor
- **Panduan bawaan** — halaman panduan untuk admin dan operator

---

## Screenshot

Dashboard Admin
![Admin Dashboard](https://i.ibb.co.com/JFKqNzd1/Dashboard-Presensi.png)

Scan QR
![Operator Scan](https://i.ibb.co.com/5xz3nL5v/Scan-QR-Presensi-03-02-2026-03-02-PM.png)

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 12, PHP 8.3 |
| Frontend | Livewire 4, Alpine.js, Tailwind CSS, Flowbite |
| Icons | Tabler Icons (`secondnetwork/blade-tabler-icons`) |
| Auth | Laravel Breeze (Blade) |
| RBAC | `spatie/laravel-permission` |
| Database | MySQL 8 |
| Cache / Queue | Redis 7 |
| Infra | Docker (Nginx, PHP-FPM 8.3) |

**Paket Utama:**

| Package | Fungsi |
|---|---|
| `spatie/laravel-permission` | RBAC (role admin & operator) |
| `simplesoftwareio/simple-qrcode` | Generate QR Code |
| `rap2hpoutre/fast-excel` | Import/export Excel |
| `codedge/laravel-fpdf` | Generate PDF (kartu undangan & stiker) |
| `spatie/laravel-activitylog` | Audit log aktivitas |
| `opcodesio/log-viewer` | Viewer error log |
| `romanzipp/laravel-queue-monitor` | Monitor queue jobs |
| `giggsey/libphonenumber-for-php` | Normalisasi nomor HP ke E.164 |

---

## Persyaratan Sistem

- Docker & Docker Compose
- Node.js 20+

---

## Instalasi

### 1. Clone & Konfigurasi

```bash
git clone <repo-url> presensi-qr
cd presensi-qr

cp .env.example .env
```

Edit `.env` untuk menyesuaikan kredensial database dan konfigurasi lainnya.

### 2. Jalankan Container

```bash
./scripts/dev.sh        # development
# atau
./scripts/prod.sh       # production
```

### 3. Setup Aplikasi

```bash
docker exec laravel_php php artisan key:generate
docker exec laravel_php php artisan migrate
docker exec laravel_php php artisan db:seed
```

### 4. Build Assets Frontend

```bash
npm install
npm run build
```

Akses aplikasi di **http://localhost:8080** (dev) atau **http://localhost:80** (prod).

---

## Akun Default (Seeder)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Operator | `operator@example.com` | `password` |

> Ganti password segera setelah login pertama di lingkungan produksi.

---

## Roles & Akses

| Role | Akses |
|---|---|
| `admin` | Seluruh fitur — event, peserta, laporan, pengguna, monitoring |
| `operator` | Halaman presensi saja (`/ops/*`) — scan QR & presensi manual |

---

## Alur Penggunaan

1. **Admin** membuat Event dan mengisi kode event (wajib untuk fitur export stiker)
2. **Admin** mengimpor peserta via Excel — QR token otomatis di-generate per peserta
3. **Admin** mengunduh/mencetak Kartu Undangan QR atau Lembar Stiker
4. **Operator** login, pilih event dan sesi (hari), mulai scan QR
5. **Admin** memantau presensi dan mengekspor laporan kehadiran

---

## Ukuran Cetak

| Media | Ukuran |
|---|---|
| Kartu Undangan | 80 × 105 mm (portrait) |
| Label Stiker | 16 × 22 mm, ±110 label per A4 |

---

## Format QR Token

Token QR bersifat opaque (tidak memuat data identitas peserta):

```
itsk:att:v1:<TOKEN>
```

Database hanya menyimpan hash token — token mentah tidak pernah disimpan.

---

## Perintah Berguna

```bash
# Jalankan test suite
docker exec laravel_php php artisan test --compact

# Code style formatter
docker exec laravel_php ./vendor/bin/pint --dirty

# Clear cache
docker exec laravel_php php artisan optimize:clear

# Buka shell PHP container
docker exec -it laravel_php sh

# Log container
docker-compose logs -f

# Stop container
docker-compose down
```

---

## Lisensi

Aplikasi ini dilisensikan di bawah **Lisensi Non-Komersial** (Non-Commercial Use License).

**Diizinkan:**
- Penggunaan gratis untuk keperluan pribadi, pendidikan, penelitian, dan institusi publik/nirlaba
- Modifikasi dan distribusi ulang dengan mencantumkan kredit kepada pencipta asli

**Dilarang:**
- Menjual, mengkomersilkan, atau menggunakan sebagai bagian dari produk/layanan berbayar
- Melisensikan ulang di bawah lisensi yang mengizinkan penggunaan komersial

Lihat file [LICENSE](LICENSE) untuk teks lisensi lengkap.

---

## Developer

**Benben Bagus Prasetyo A.**

&copy; 2026 Benben Bagus Prasetyo A. All rights reserved.
