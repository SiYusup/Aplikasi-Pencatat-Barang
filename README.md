# 📦 Aplikasi Pencatat Barang

Aplikasi pencatat inventory barang berbasis web dengan arsitektur **MVC + RESTful API**. Setiap data di UI dimuat lewat endpoint JSON (`/api/v1/*`), sehingga backend dan frontend terpisah rapi: controller web hanya me-render cangkang HTML, seluruh transfer data lewat API.

Referensi arsitektur: `php-login-management` (Programmer Zaman Now) — pola Router, Service, Repository, dan session-cookie dipertahankan, lalu dikembangkan untuk kebutuhan inventory.

## ✨ Fitur

**Auth**
- Registrasi (pendaftar pertama otomatis jadi `admin`), login, logout (konfirmasi SweetAlert), refresh sesi
- Lupa & reset password

**Admin Panel**
1. **Dashboard** — ringkasan stok, nilai persediaan, grafik arus masuk vs keluar (ApexCharts), donat komposisi per kategori, top 5 stok, peringatan stok menipis
2. **Data Barang** — CRUD, pencarian, stok opname/penyesuaian, riwayat mutasi per barang
3. **Kategori** — CRUD + jumlah barang per kategori
4. **Supplier** — CRUD + jumlah transaksi per supplier
5. **Barang Masuk** — input (satuan/borongan), pembatalan (stok dikembalikan)
6. **Barang Keluar** — input dengan validasi stok, pembatalan
7. **Laporan** — stok, masuk, keluar, mutasi/kartu stok per periode
8. **Export** — PDF (dompdf), Excel asli (PhpSpreadsheet), CSV (`fputcsv` + BOM)
9. **Profile** — lihat/update profil, ganti password, upload avatar
10. **Pendukung** — manajemen user & role, audit log, notifikasi stok menipis

## 🛠️ Tech Stack

| Lapisan   | Teknologi |
|-----------|-----------|
| Bahasa    | PHP >= 8.1 (MVC tanpa framework) |
| Database  | PostgreSQL (`pdo_pgsql`) |
| Config    | `vlucas/phpdotenv` (`.env`) |
| UI        | Bootstrap 5 + Bootstrap Icons (sidebar) |
| Alert     | SweetAlert2 (fallback alert Bootstrap) |
| Tabel     | DataTables |
| Grafik    | ApexCharts |
| Datepicker| flatpickr |
| PDF       | `dompdf/dompdf` |
| Excel     | `phpoffice/phpspreadsheet` |
| Testing   | `phpunit/phpunit` |

## 📋 Prasyarat

- PHP >= 8.1 dengan ekstensi `pdo_pgsql`, `mbstring`, `xml`, `zip`, `gd`
- PostgreSQL berjalan
- Composer

## 🚀 Instalasi

```bash
# 1. Clone repo
git clone <url-repo-anda>
cd aplikasi-pencatat-barang-crud

# 2. Install dependensi PHP
composer install

# 3. Siapkan environment
cp .env.example .env
# lalu isi DB_PASS (dan sesuaikan DB_HOST/DB_PORT bila perlu)

# 4. Buat database + jalankan migrasi (database.sql)
php scripts/migrate.php
# untuk database testing: php scripts/migrate.php --test

# 5. Jalankan server
php -S localhost:8000 -t public
```

Buka **http://localhost:8000/users/register** untuk membuat akun pertama.

> 👤 **Akun pertama yang terdaftar otomatis menjadi `admin`.** Pendaftar berikutnya menjadi `staff`.

## ⚙️ Konfigurasi (`.env`)

```ini
APP_NAME="Aplikasi Pencatat Barang"
APP_ENV=prod
APP_URL=http://localhost:8000

DB_HOST=localhost
DB_PORT=5432
DB_NAME=pencatat_barang
DB_NAME_TEST=pencatat_barang_test
DB_USER=postgres
DB_PASS=ucup
```

## 🗂️ Struktur Proyek

```
app/
├── App/            # Router, View, ApiResponse
├── Config/         # Database (dotenv + PDO PostgreSQL)
├── Controller/     # Web (HTML) + Api (JSON: Auth, Barang, Kategori, Supplier, Laporan)
├── Domain/         # Entity: User, Barang, Session
├── Exception/      # ValidationException, ApiResponseSent
├── Middleware/     # MustLogin, MustNotLogin, ApiAuth
├── Model/          # Request DTO
├── Repository/     # Akses database (PDO)
├── Service/        # Logika bisnis + transaksi
└── View/           # Template (layout sidebar + halaman)
public/index.php    # Front controller + definisi routes
docs/openapi.yaml   # Dokumentasi API (OpenAPI 3.0)
database.sql        # DDL PostgreSQL + seed kategori
scripts/migrate.php # Pembuat database + migrasi
tests/              # PHPUnit test suite
```

## 🔌 Dokumentasi API

Spesifikasi lengkap tersedia di [`docs/openapi.yaml`](docs/openapi.yaml) (OpenAPI 3.0) — dapat dibuka dengan [Swagger Editor](https://editor.swagger.io) atau Swagger UI.

Autentikasi memakai token Bearer (atau cookie sesi):

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}'

# Pakai token
curl http://localhost:8000/api/v1/barang \
  -H "Authorization: Bearer <access_token>"
```

Endpoint utama: `POST /api/v1/auth/*`, `GET /api/v1/dashboard/*`, CRUD `/api/v1/barang`, `/api/v1/kategori`, `/api/v1/supplier`, `/api/v1/barang-masuk`, `/api/v1/barang-keluar`, `/api/v1/laporan/*`, `/api/v1/export/*?format=pdf|xlsx|csv`.

## 🧪 Testing

```bash
./vendor/bin/phpunit --testdox
```

Suite mencakup: registrasi/login/logout, CRUD + stok masuk/keluar/opname, CRUD kategori & supplier (termasuk efek `SET NULL`), serta validitas file export PDF/XLSX/CSV. Test memakai database terpisah (`DB_NAME_TEST`) sehingga data asli aman.

## 📤 Catatan Export

- **PDF**: A4 landscape via dompdf — kop, nomor halaman, baris zebra.
- **XLSX**: file Excel asli via PhpSpreadsheet — header gaya, border, auto-size, freeze pane.
- **CSV**: `fputcsv` + BOM UTF-8 agar terbuka rapi di Excel Indonesia.

## 📝 Lisensi

MIT — bebas dipakai untuk belajar maupun dikembangkan lebih lanjut.
