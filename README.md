# Sistem Penerimaan Siswa Baru (PSB) — SMKN 2 Singosari

Aplikasi web Penerimaan Siswa Baru (PSB) untuk **SMK Negeri 2 Singosari**, Kab. Malang.
Mendukung alur PSB lengkap: pendaftaran online oleh calon siswa, verifikasi dokumen,
seleksi nilai, perankingan per jurusan sesuai kuota, hingga pengumuman.

Dibangun dengan **Laravel 10** + **Tailwind CSS** (portal) & **SB Admin 2** (dashboard admin).

## ✨ Fitur

### Portal Calon Siswa (publik)
- Registrasi akun (email + NISN) dengan verifikasi email
- Formulir pendaftaran 1 halaman (data diri, orang tua, pilihan jurusan, upload dokumen)
- Upload 5 dokumen persyaratan (KTP, KK, ijazah, rapor, foto 3x4)
- Pantau status pendaftaran & hasil seleksi
- Cetak surat keterangan diterima (PDF)

### Dashboard Admin
- Dashboard pipeline (pendaftaran → verifikasi → nilai → hasil)
- Kelola jurusan & kuota
- Data pendaftar (filter, detail, preview dokumen)
- Input pendaftar manual (untuk pendaftar offline)
- Verifikasi dokumen (massal + individual)
- Input nilai seleksi (spreadsheet, simpan semua)
- Proses ranking & penetapan hasil per jurusan
- Terbitkan pengumuman
- Kelola jadwal PSB
- Laporan & ekspor (CSV/Excel)
- Audit log

## 🚀 Cara Menjalankan

### Prasyarat
- PHP >= 8.1
- Composer
- SQLite (default) atau MySQL 8

### Instalasi

```bash
# 1. Clone repository
git clone <url-repo>
cd <nama-folder>

# 2. Install dependensi
composer install
npm install && npm run build

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Buat database & jalankan migration + seeder
touch database/database.sqlite   # jika pakai sqlite
php artisan migrate --seed

# (Opsional) Isi data demo untuk presentasi
php artisan db:seed --class=DemoDataSeeder

# 5. Jalankan aplikasi
php artisan serve
```

Buka `http://localhost:8000`.

### Login Admin
- **Email:** `admin@smkn2.sch.id`
- **Password:** `admin1234` (bisa diubah via `ADMIN_PASSWORD` di `.env`, lalu jalankan ulang seeder)

### Data Demo (opsional)
Jalankan untuk mengisi data demo (idempoten, aman dijalankan berulang):
```bash
php artisan db:seed --class=DemoDataSeeder
```

## 🧪 Testing

```bash
php artisan test
```

##  Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | Laravel 10 (PHP 8.1+) |
| Frontend (portal) | Blade + Tailwind CSS |
| Dashboard admin | SB Admin 2 (Bootstrap 5) |
| Database | SQLite (default) / MySQL 8 |
| Testing | PHPUnit |

## 📁 Struktur Proyek

```
app/
├── Enums/            # Role, StatusPendaftar, JenisDokumen, TahapJadwal
├── Http/Controllers/
│   ├── Admin/        # Controller dashboard admin
│   └── Portal/       # Controller portal calon siswa
├── Models/           # Model Eloquent
└── Services/Psb/     # Logika inti (ranking, nilai, verifikasi, dll)
database/
├── migrations/       # Skema database
├── seeders/          # DatabaseSeeder + DemoDataSeeder
└── factories/        # Factory untuk testing & demo
docs/                 # Dokumen user requirements & system design
resources/views/      # Blade views (portal, admin, auth)
tests/Feature/        # Feature tests (PHPUnit)
```

## 📚 Dokumen

- [`docs/user-requirements.md`](docs/user-requirements.md) — User requirements (use case, kebutuhan fungsional/non-fungsional)
- [`docs/system-design.md`](docs/system-design.md) — System design (arsitektur, ERD, DFD, wireframe)
- [`docs/plans/`](docs/plans/) — Rencana implementasi

## 📄 Lisensi

Proyek ini adalah Tugas Akhir. Dilisensikan untuk keperluan akademik.
