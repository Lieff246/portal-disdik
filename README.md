# Portal Pemetaan Disdik

Aplikasi web untuk pemetaan data sekolah (SMA/SMK/SLB) di lingkungan Dinas Pendidikan. Dibangun di atas Laravel 13 dengan frontend React + Inertia.js.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | React 19, TypeScript, Inertia.js |
| Styling | Tailwind CSS v4, shadcn/ui |
| Auth | Laravel Fortify (login, register, 2FA, passkey) |
| Role & Permission | Spatie Laravel Permission |
| Build Tool | Vite |
| Database | MySQL |

## Fitur yang Sudah Ada

### Autentikasi
- Login & Register
- Forgot Password & Reset Password
- Email Verification
- Two-Factor Authentication (2FA)
- Passkey (WebAuthn)

### Settings User
- Edit profil (nama, email)
- Ganti password
- Kelola 2FA & Passkey
- Appearance (dark/light mode)

### Role & Permission
- Integrasi Spatie Laravel Permission
- User terhubung ke Cabang Dinas

### Struktur Data Sekolah
- **`sekolah`** — data utama sekolah (nama, NPSN, koordinat, jenjang, akreditasi, jumlah siswa, daya tampung, status 3T, wilayah terpencil/perbatasan/transmigrasi)
- **`school_sma`** — detail SMA/SMK/SLB (kepala sekolah, NIP, no HP, polygon wilayah)
- **`cabang_dinas`** — cabang dinas pendidikan beserta koordinat peta dan daftar kabupaten/kota

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm
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
npm install
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
```bash
php artisan db:seed
```

### 7. Build assets
```bash
npm run build
```

### 8. Jalankan server
```bash
composer run dev
```

Atau hanya PHP server:
```bash
php artisan serve
```

Akses di `http://127.0.0.1:8000`

## Struktur Folder Penting

```
app/
├── Actions/Fortify/        # Logic register & reset password
├── Http/
│   ├── Controllers/Settings/  # Profile & Security controller
│   └── Middleware/            # Inertia & Appearance middleware
├── Models/
│   ├── User.php            # User dengan HasRoles & relasi CabangDinas
│   ├── CabangDinas.php     # Cabang dinas pendidikan
│   ├── Sekolah.php         # Data utama sekolah
│   └── SchoolSma.php       # Detail SMA/SMK/SLB
resources/js/
├── pages/                  # Halaman React (auth, dashboard, settings)
├── components/             # Komponen UI (shadcn/ui + custom)
├── layouts/                # Layout app & auth
└── hooks/                  # Custom React hooks
```

## Catatan

- Dashboard masih dalam tahap pengembangan (placeholder)
- Fitur pemetaan (map view) belum diimplementasi
