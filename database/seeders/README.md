# Database Seeders

## File SQL yang Diperlukan

File SQL **tidak di-commit ke Git** — harus diminta ke tim atau didownload dari Google Drive.

| File | Tabel | Deskripsi |
|------|-------|-----------|
| `sekolah.sql` | `sekolah` | Data sekolah PAUD–SMA dari Dapodik (format BPS) |
| `database sma.sql` | `school_sma` | Data 466 sekolah SMA/SMK/SLB kewenangan Provinsi |
| `backbone_sekolah.sql` | `sekolah` | Data backbone sekolah lengkap semua jenjang |

> Taruh semua file SQL di folder `database/seeders/` sebelum menjalankan seeder.

---

## Setup Awal (Fresh Install)

```bash
# 1. Install dependencies
composer install
cp .env.example .env
php artisan key:generate

# 2. Buat tabel
php artisan migrate

# 3. Seed data (jalankan semua sekaligus)
php artisan db:seed
```

`php artisan db:seed` akan menjalankan urutan berikut:
1. `RoleSeeder` — data role user
2. `CabangDinasSeeder` — data 6 wilayah cabang dinas
3. `UserSeeder` — user default
4. `DataSekolahImporterSeeder` — import `sekolah.sql` + `database sma.sql`
5. `SchoolSmaSeeder` — import `database sma.sql` ke `school_sma`

---

## Seed Per Seeder

```bash
# Import sekolah.sql + database sma.sql
php artisan db:seed --class=DataSekolahImporterSeeder

# Import database sma.sql ke school_sma
php artisan db:seed --class=SchoolSmaSeeder

# Import backbone_sekolah.sql ke sekolah
php artisan db:seed --class=BackboneSekolahSeeder
```

---

## Deploy ke Server

```bash
# 1. Pull kode terbaru
git pull origin main

# 2. Upload file SQL via SCP
scp "database sma.sql" user@server:/path/to/project/database/seeders/
scp sekolah.sql user@server:/path/to/project/database/seeders/
scp backbone_sekolah.sql user@server:/path/to/project/database/seeders/

# 3. SSH ke server lalu run
php artisan migrate --force
php artisan db:seed --force
```

---

## Troubleshooting

**File SQL tidak ditemukan:**
Taruh file di `database/seeders/` lalu jalankan ulang seeder.

**Duplicate entry error:**
```bash
php artisan tinker --execute 'DB::table("sekolah")->truncate();'
php artisan db:seed --class=BackboneSekolahSeeder
```

**Memory limit (untuk file besar):**
```bash
php -d memory_limit=512M artisan db:seed --class=BackboneSekolahSeeder
```
