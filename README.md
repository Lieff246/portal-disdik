# Portal Pemetaan Disdik — Backend API

Backend API untuk aplikasi pemetaan data sekolah Provinsi Sulawesi Tengah. Dibangun dengan Laravel 13 sebagai pure REST API yang dikonsumsi oleh frontend React secara terpisah.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3+ |
| Auth API | Laravel Sanctum |
| Auth User | Laravel Fortify (login, register, 2FA, passkey) |
| Role & Permission | Spatie Laravel Permission |
| Database | MySQL |

## Arsitektur

```
Frontend React (repo terpisah)
        ↕ HTTP / JSON
Backend Laravel (repo ini)
        ↕ Eloquent ORM
    MySQL Database
```

Laravel di repo ini **hanya berfungsi sebagai API Backend**. Tidak ada halaman Blade atau Inertia. Semua response dalam format JSON.

## Struktur Database

| Tabel | Keterangan |
|---|---|
| `sekolah` | Data utama semua sekolah PAUD-SMA Sulawesi Tengah (dari Dapodik) |
| `school_sma` | Detail tambahan SMA/SMK/SLB — kepsek, polygon wilayah |
| `cabang_dinas` | 6 wilayah cabang dinas pendidikan provinsi |
| `users` | Admin dengan role berbasis wilayah |

Relasi utama: `sekolah.npsn ↔ school_sma.npsn`

## Roles

| Role | Akses |
|---|---|
| `admin_provinsi` | Semua sekolah Sulawesi Tengah |
| `admin_cabdis` | SMA/SMK/SLB wilayah cabang dinasnya |
| `admin_kab_kota` | PAUD-SMP kabupaten/kota yang ditentukan |

## API Endpoints

Base URL: `http://127.0.0.1:8000/api/v1`

### Auth

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/login` | Login, return Bearer token |
| POST | `/logout` | Logout, hapus token (perlu Bearer token) |

### Public (tanpa login)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/portal/landing` | Data summary + statistik untuk halaman utama |
| GET | `/sekolah` | List sekolah untuk marker peta |
| GET | `/sekolah?jenjang=SMA` | Filter by jenjang (SD/SMP/SMA/SMK/TK/dll) |
| GET | `/sekolah?kode_kabupaten=7271` | Filter by kode kabupaten |
| GET | `/sekolah?is_3t=true` | Filter sekolah wilayah 3T |
| GET | `/sekolah/{npsn}` | Detail satu sekolah by NPSN |
| GET | `/statistik/kabupaten` | Statistik jumlah sekolah per kabupaten/kota |
| GET | `/statistik/jenjang` | Statistik jumlah sekolah per jenjang |
| GET | `/cabang-dinas` | List 6 wilayah cabang dinas |

### Protected (perlu login — Bearer Token)

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/user` | Info user yang sedang login |
| GET | `/admin/sekolah` | List sekolah (admin) |
| POST | `/admin/sekolah` | Tambah sekolah |
| PUT | `/admin/sekolah/{id}` | Update sekolah |
| DELETE | `/admin/sekolah/{id}` | Hapus sekolah |

## Requirements

- PHP 8.3+
- Composer
- MySQL

## Setup

### 1. Clone repository
```bash
git clone <repo-url>
cd disdik-pemetaan
```

### 2. Install dependencies
```bash
composer install
```

### 3. Konfigurasi environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuaikan kredensial database:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=service-pulpen
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Publish config Spatie Permission
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 5. Buat database & jalankan migrasi
Buat database `service-pulpen` di MySQL terlebih dahulu, lalu:
```bash
php artisan migrate
```

### 6. Seed data awal
> Pastikan file `sekolah.sql` dan `database sma.sql` sudah ada di folder `database/seeders/` sebelum menjalankan seeder. File SQL tidak di-commit ke repo karena ukurannya besar — minta ke anggota tim.

```bash
php artisan db:seed
```

Seeder akan menjalankan:
- `RoleSeeder` — buat 3 roles (admin_provinsi, admin_cabdis, admin_kab_kota)
- `CabangDinasSeeder` — buat 6 wilayah cabang dinas
- `DataSekolahImporterSeeder` — import data sekolah dari file SQL

### 7. Jalankan server
```bash
php artisan serve
```

API siap diakses di `http://127.0.0.1:8000/api/v1`

## Struktur Folder Penting

```
app/
├── Http/Controllers/Api/
│   ├── Admin/SekolahController.php   # CRUD sekolah (protected)
│   ├── CabangDinasController.php     # Data cabang dinas
│   ├── PortalController.php          # Data landing page
│   ├── SekolahController.php         # List & detail sekolah
│   └── StatistikController.php       # Statistik per kabupaten & jenjang
├── Models/
│   ├── User.php                      # User dengan HasRoles & relasi CabangDinas
│   ├── CabangDinas.php               # Cabang dinas pendidikan
│   ├── Sekolah.php                   # Data utama sekolah + scopes filter
│   └── SchoolSma.php                 # Detail SMA/SMK/SLB
routes/
├── api.php                           # Semua endpoint API
└── settings.php                      # Route settings user (profile, security)
database/
├── migrations/                       # Struktur tabel
└── seeders/
    ├── RoleSeeder.php
    ├── CabangDinasSeeder.php
    └── DataSekolahImporterSeeder.php  # Import dari file SQL
```

## CORS

Saat development, semua origin diizinkan (`*`). Sebelum deploy ke production, ubah `config/cors.php`:
```php
'allowed_origins' => ['https://domain-frontend.com'],
```
