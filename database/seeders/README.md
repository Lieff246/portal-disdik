# Database Seeders

## File SQL yang Diperlukan

Beberapa seeder membutuhkan file SQL eksternal yang **TIDAK di-commit** ke Git (terlalu besar).

### 📥 File yang Harus Didownload:

| File | Ukuran | Deskripsi | Lokasi Download |
|------|--------|-----------|-----------------|
| `database sma.sql` | ~167 KB | Data 466 sekolah SMA/SMK/SLB | 📁 [Google Drive](https://drive.google.com/...) atau minta ke tim |
| `sekolah.sql` | ~867 KB | Data lengkap sekolah dari Dapodik | 📁 [Google Drive](https://drive.google.com/...) atau minta ke tim |

> **Catatan:** Update link Google Drive di atas dengan link sharing yang sebenarnya.

---

## 🚀 Cara Setup Database

### 1. Clone Repository
```bash
git clone <repo-url>
cd disdik-pemetaan
```

### 2. Install Dependencies
```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 3. Setup Database
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portal_disdik
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Download File SQL
Download file SQL dari Google Drive/shared folder, simpan di folder ini (`database/seeders/`):
- ✅ `database sma.sql`
- ✅ `sekolah.sql` (opsional, sudah ada seeder alternatif)

### 5. Run Migration & Seeder
```bash
# Buat tabel
php artisan migrate

# Seed data
php artisan db:seed

# Atau seed satu per satu:
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=CabangDinasSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=DataSekolahImporterSeeder
php artisan db:seed --class=SchoolSmaSeeder
```

---

## ⚠️ Troubleshooting

### Error: File 'database sma.sql' tidak ditemukan
**Solusi:** Download file dari Google Drive/minta ke tim, simpan di `database/seeders/`

### Error: SQLSTATE[23000]: Duplicate entry
**Solusi:** Truncate table dulu sebelum seed ulang:
```bash
php artisan tinker
> DB::table('school_sma')->truncate();
> exit
php artisan db:seed --class=SchoolSmaSeeder
```

---

## 📦 Deployment ke Production

### Via SSH (VPS/Server):
```bash
# 1. Pull kode terbaru
git pull origin main

# 2. Upload file SQL via SCP/FTP
scp database\ sma.sql user@server:/path/to/project/database/seeders/

# 3. SSH ke server
ssh user@server

# 4. Run migration & seeder
cd /path/to/project
php artisan migrate --force
php artisan db:seed --force
```

### Via Shared Hosting (cPanel):
1. Upload file SQL via File Manager ke folder `database/seeders/`
2. Buka Terminal di cPanel
3. Run: `php artisan db:seed --class=SchoolSmaSeeder`

---

## 🔄 Update Data (Re-seed)

Kalau ada data baru (misal file SQL diupdate):
```bash
# Truncate table dulu
php artisan tinker --execute="DB::table('school_sma')->truncate();"

# Seed ulang
php artisan db:seed --class=SchoolSmaSeeder
```
