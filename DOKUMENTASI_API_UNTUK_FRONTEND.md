# 📘 Dokumentasi API untuk Frontend Developer

> **Untuk:** Frontend Developer (React/Inertia)  
> **Bahasa:** Bahasa Indonesia (mudah dipahami mahasiswa)  
> **Backend:** Laravel 11 + Sanctum Authentication  
> **Base URL:** `http://localhost:8000/api/v1`

---

## 🚀 Cara Setup & Testing Backend

### 1. Jalankan Backend (Laravel)

```bash
# Masuk ke folder backend
cd disdik-pemetaan

# Install dependencies (kalau belum)
composer install

# Copy .env (kalau belum)
cp .env.example .env

# Generate key
php artisan key:generate

# Migrasi database + seeder
php artisan migrate:fresh --seed

# Jalankan server
php artisan serve
# Backend jalan di: http://localhost:8000
```

### 2. Test User untuk Login

Setelah `php artisan migrate:fresh --seed`, akan ada 5 user test:

| Email | Password | Role | Akses |
|-------|----------|------|-------|
| `admin@disdik-sulteng.go.id` | `password` | admin_provinsi | Semua sekolah di Sulawesi Tengah |
| `cabdis1@disdik-sulteng.go.id` | `password` | admin_cabdis | Sekolah di Kota Palu & Kab. Sigi |
| `cabdis2@disdik-sulteng.go.id` | `password` | admin_cabdis | Sekolah di Kab. Donggala & Parigi Moutong |
| `kabkota.palu@disdik-sulteng.go.id` | `password` | admin_kab_kota | Sekolah di Kota Palu saja |
| `kabkota.donggala@disdik-sulteng.go.id` | `password` | admin_kab_kota | Sekolah di Kab. Donggala saja |

---

## 🔐 Authentication (Login & Token)

### A. Login

**Endpoint:** `POST /api/v1/login`

**Request Body:**
```json
{
  "email": "admin@disdik-sulteng.go.id",
  "password": "password"
}
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin Provinsi",
      "email": "admin@disdik-sulteng.go.id",
      "role": "admin_provinsi",
      "cabang_dinas_id": null,
      "kode_kabupaten": null
    },
    "token": "1|eyJ0eXAiOiJKV1QiLCJhbGc..." // <-- Simpan ini!
  }
}
```

**Response Gagal (401):**
```json
{
  "status": "error",
  "message": "Email atau password salah"
}
```

### B. Cara Pakai Token (Setiap Request)

Setelah login, **simpan token** di localStorage/state management (Redux/Zustand).

**Contoh di Axios:**
```javascript
const token = localStorage.getItem('auth_token');

axios.get('http://localhost:8000/api/v1/user', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

**Contoh di Fetch:**
```javascript
const token = localStorage.getItem('auth_token');

fetch('http://localhost:8000/api/v1/user', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

### C. Get User Info (Cek Login)

**Endpoint:** `GET /api/v1/user`  
**Auth:** Bearer Token ✅ (Wajib)

**Response:**
```json
{
  "id": 1,
  "name": "Admin Provinsi",
  "email": "admin@disdik-sulteng.go.id",
  "role": "admin_provinsi",
  "cabang_dinas_id": null,
  "kode_kabupaten": null
}
```

### D. Logout

**Endpoint:** `POST /api/v1/logout`  
**Auth:** Bearer Token ✅ (Wajib)

**Response:**
```json
{
  "status": "success",
  "message": "Logout berhasil"
}
```

**Jangan lupa hapus token di frontend:**
```javascript
localStorage.removeItem('auth_token');
```

---

## 🗺️ API Publik (Tanpa Login)

### 1. Landing Page Data

**Endpoint:** `GET /api/v1/portal/landing`  
**Auth:** ❌ Tidak perlu token (publik)

**Response:**
```json
{
  "status": "success",
  "data": {
    "summary": {
      "total_sekolah": 3500,
      "total_sd": 1800,
      "total_smp": 900,
      "total_sma": 600,
      "total_paud": 200,
      "total_3t": 150,
      "total_negeri": 2800,
      "total_swasta": 700,
      "total_siswa": 500000,
      "semester_id": "20241"
    },
    "cards": [
      {
        "kabupaten": "Kota Palu",
        "kode_kabupaten": "7271",
        "total_sekolah": 450,
        "total_negeri": 380,
        "total_swasta": 70,
        "total_3t": 5,
        "total_siswa": 80000,
        "total_daya_tampung": 85000
      },
      // ... kabupaten lainnya
    ],
    "neracaRekap": [
      {
        "bentuk_pendidikan": "SD",
        "jumlah": 1800,
        "jumlah_negeri": 1600,
        "jumlah_swasta": 200,
        "total_siswa": 300000
      },
      // ... jenjang lainnya
    ]
  }
}
```

**Kegunaan:**
- Tampilan dashboard publik
- Card list kabupaten
- Summary statistik

---

### 2. Daftar Sekolah (Untuk Marker Peta)

**Endpoint:** `GET /api/v1/sekolah`  
**Auth:** ❌ Tidak perlu token (publik)

**Query Params (Opsional):**
- `jenjang` — Filter bentuk pendidikan: `SD`, `SMP`, `SMA`, `SMK`, `SLB`, `TK`, dll
- `kode_kabupaten` — Filter kabupaten: `7271` (Palu), `7202` (Donggala), dll
- `is_3t` — Filter sekolah 3T: `true` atau `false`

**Contoh Request:**
```
GET /api/v1/sekolah?jenjang=SD&kode_kabupaten=7271
```

**Response:**
```json
{
  "status": "success",
  "total": 450,
  "data": [
    {
      "sekolah_id": "abc123",
      "semester_id": "20241",
      "nama": "SDN 1 Palu",
      "npsn": "40400001",
      "bentuk_pendidikan": "SD",
      "status_sekolah": "Negeri",
      "alamat_jalan": "Jl. Merdeka No. 123",
      "dusun": null,
      "desa_kelurahan": "Kelurahan Talise",
      "kode_kabupaten": "7271",
      "nama_kabupaten": "Kota Palu", // <-- NAMA KABUPATEN
      "kode_kecamatan": "727101",
      "kode_provinsi": "72",
      "lintang": -0.8951,
      "bujur": 119.8707,
      "is_3t": false,
      "is_sekolah_alam": false,
      "jumlah_siswa": 350,
      "daya_tampung": 400,
      "create_date": "2024-01-01T00:00:00Z",
      "last_update": "2024-08-01T00:00:00Z",
      "detail_sma": null
    },
    // ... max 5000 sekolah
  ]
}
```

**Kegunaan:**
- Tampilan marker di peta Leaflet
- Dibatasi 5000 record agar browser tidak lag

---

### 3. Detail Sekolah (1 Sekolah)

**Endpoint:** `GET /api/v1/sekolah/{npsn}`  
**Auth:** ❌ Tidak perlu token (publik)

**Contoh:**
```
GET /api/v1/sekolah/40400001
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "sekolah_id": "abc123",
    "nama": "SDN 1 Palu",
    "npsn": "40400001",
    "bentuk_pendidikan": "SD",
    "status_sekolah": "Negeri",
    "alamat_jalan": "Jl. Merdeka No. 123",
    "kode_kabupaten": "7271",
    "nama_kabupaten": "Kota Palu",
    "lintang": -0.8951,
    "bujur": 119.8707,
    "is_3t": false,
    "jumlah_siswa": 350,
    "daya_tampung": 400,
    "detail_sma": null
    // ... field lengkap
  }
}
```

**Response Error (404):**
```json
{
  "status": "error",
  "message": "Sekolah dengan NPSN 40400001 tidak ditemukan."
}
```

---

### 4. Statistik per Kabupaten

**Endpoint:** `GET /api/v1/statistik/kabupaten`  
**Auth:** ❌ Tidak perlu token (publik)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "kabupaten": "Kota Palu",
      "kode_kabupaten": "7271",
      "total_sekolah": 450,
      "total_sma": 80,
      "total_smp": 100,
      "total_sd": 250,
      "total_paud": 20,
      "total_3t": 5,
      "total_negeri": 380,
      "total_swasta": 70,
      "total_siswa": 80000,
      "total_daya_tampung": 85000
    },
    // ... kabupaten lainnya
  ]
}
```

**Kegunaan:**
- Chart perbandingan antar kabupaten
- Card list kabupaten di dashboard

---

### 5. Statistik per Jenjang Pendidikan

**Endpoint:** `GET /api/v1/statistik/jenjang`  
**Auth:** ❌ Tidak perlu token (publik)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "bentuk_pendidikan": "SD",
      "total": 1800,
      "total_negeri": 1600,
      "total_swasta": 200,
      "total_siswa": 300000,
      "total_daya_tampung": 320000,
      "total_3t": 80
    },
    {
      "bentuk_pendidikan": "SMP",
      "total": 900,
      "total_negeri": 800,
      "total_swasta": 100,
      "total_siswa": 150000,
      "total_daya_tampung": 160000,
      "total_3t": 40
    },
    // ... jenjang lainnya
  ]
}
```

**Kegunaan:**
- Chart Donut/Pie distribusi jenjang
- Bar Chart negeri vs swasta

---

## 🔒 API Admin (Butuh Login + Token)

### 1. Daftar Sekolah (Admin View)

**Endpoint:** `GET /api/v1/admin/sekolah`  
**Auth:** Bearer Token ✅ (Wajib)

**Fitur Spesial:**
- **Admin Provinsi:** Bisa lihat semua sekolah
- **Admin Cabdis:** Hanya sekolah di wilayahnya (contoh: Palu + Sigi)
- **Admin Kab/Kota:** Hanya sekolah di kab/kota-nya

**Query Params (Opsional):**
- `jenjang` — Filter jenjang
- `kode_kabupaten` — Filter kabupaten
- `is_3t` — Filter 3T

**Response:** (sama seperti `/api/v1/sekolah`, tapi sudah terfilter by role)

---

### 2. Detail Sekolah (Admin View)

**Endpoint:** `GET /api/v1/admin/sekolah/{id}`  
**Auth:** Bearer Token ✅ (Wajib)

**Response:**
```json
{
  "status": "success",
  "data": {
    "sekolah_id": "abc123",
    "nama": "SDN 1 Palu",
    // ... field lengkap
  }
}
```

**Response Error (403) — Tidak punya akses:**
```json
{
  "status": "error",
  "message": "Anda tidak memiliki akses ke sekolah ini."
}
```

---

### 3. Update Sekolah

**Endpoint:** `PUT /api/v1/admin/sekolah/{id}`  
**Auth:** Bearer Token ✅ (Wajib)

**Request Body (contoh):**
```json
{
  "nama": "SDN 1 Palu (Diperbaharui)",
  "alamat_jalan": "Jl. Merdeka Raya No. 123",
  "jumlah_siswa": 360
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Data sekolah berhasil diperbarui",
  "data": {
    // ... data sekolah terbaru
  }
}
```

---

### 4. Hapus Sekolah

**Endpoint:** `DELETE /api/v1/admin/sekolah/{id}`  
**Auth:** Bearer Token ✅ (Wajib)

**Response:**
```json
{
  "status": "success",
  "message": "Sekolah berhasil dihapus"
}
```

---

### 5. List Cabang Dinas

**Endpoint:** `GET /api/v1/cabang-dinas`  
**Auth:** ❌ Tidak perlu token (publik)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "nama": "Wilayah I - Palu & Sigi",
      "kabupaten_kota": ["Kota Palu", "Kab. Sigi"],
      "kode_kabupaten": ["7271", "7210"],
      "map_lat": -0.8917,
      "map_lng": 119.8707,
      "map_zoom": 9
    },
    // ... wilayah lainnya
  ]
}
```

**Kegunaan:**
- Dropdown pilihan cabang dinas di form admin

---

## 🛠️ Cara Test API (Postman/Thunder Client)

### Setup Collection

1. **Base URL:** `http://localhost:8000/api/v1`
2. **Headers (untuk semua request):**
   ```
   Accept: application/json
   Content-Type: application/json
   ```
3. **Authorization (untuk endpoint admin):**
   ```
   Type: Bearer Token
   Token: <token dari response login>
   ```

### Langkah Testing:

#### 1. Test Login
```
POST http://localhost:8000/api/v1/login
Body (JSON):
{
  "email": "admin@disdik-sulteng.go.id",
  "password": "password"
}

✅ Harusnya dapat token
```

#### 2. Test Get User
```
GET http://localhost:8000/api/v1/user
Authorization: Bearer <token dari step 1>

✅ Harusnya dapat data user
```

#### 3. Test Sekolah Publik
```
GET http://localhost:8000/api/v1/sekolah

✅ Harusnya dapat list sekolah
```

#### 4. Test Admin Sekolah (dengan filter role)
```
GET http://localhost:8000/api/v1/admin/sekolah
Authorization: Bearer <token admin cabdis atau kab/kota>

✅ Harusnya hanya dapat sekolah sesuai wilayah admin
```

---

## ⚠️ Error Handling

### Error Response Format

Semua error mengikuti format yang sama:

```json
{
  "status": "error",
  "message": "Deskripsi error"
}
```

### HTTP Status Codes

| Code | Arti | Kapan Muncul |
|------|------|--------------|
| 200 | OK | Request berhasil |
| 401 | Unauthorized | Token salah/expired atau belum login |
| 403 | Forbidden | Login tapi tidak punya akses (contoh: admin kab Palu akses data Donggala) |
| 404 | Not Found | Data tidak ditemukan |
| 422 | Validation Error | Input tidak valid (misal: email kosong) |
| 500 | Server Error | Error di backend |

---

## 🔧 CORS & Frontend Setup

### Axios Config (Recommended)

```javascript
// src/lib/axios.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

// Auto inject token ke setiap request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 (token expired) auto logout
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### Contoh Pakai di Component

```javascript
import api from '@/lib/axios';

// Login
const handleLogin = async (email, password) => {
  try {
    const { data } = await api.post('/login', { email, password });
    localStorage.setItem('auth_token', data.data.token);
    // Redirect ke dashboard
  } catch (error) {
    alert(error.response?.data?.message || 'Login gagal');
  }
};

// Get Sekolah
const fetchSekolah = async () => {
  try {
    const { data } = await api.get('/sekolah', {
      params: { jenjang: 'SD', kode_kabupaten: '7271' }
    });
    setSekolahList(data.data);
  } catch (error) {
    console.error(error);
  }
};
```

---

## 📝 Checklist Frontend Developer

Sebelum push code, pastikan:

- [ ] Bisa login dengan user test
- [ ] Token tersimpan di localStorage
- [ ] Token auto inject ke setiap request
- [ ] Bisa fetch `/portal/landing` (publik)
- [ ] Bisa fetch `/sekolah` dengan filter (publik)
- [ ] Bisa fetch `/admin/sekolah` dengan token (admin)
- [ ] Handle error 401 (auto logout)
- [ ] Handle error 403 (show "Tidak punya akses")
- [ ] Logout menghapus token di localStorage

---

## 🚨 FAQ & Troubleshooting

### Q: Error "Unauthenticated" padahal sudah kirim token?

**A:** Pastikan:
1. Token pakai format `Bearer <token>` (ada spasi setelah Bearer)
2. Header `Accept: application/json` ada
3. Token belum expired (default 1 tahun, tapi cek `sanctum.php`)

### Q: CORS error di browser?

**A:** Backend sudah diset `allowed_origins => ['*']` di `config/cors.php`. Tapi pastikan:
1. Laravel jalan di `http://localhost:8000`
2. Frontend jalan di `http://localhost:5173` (Vite) atau port lain
3. Jangan pakai `file://` (harus http/https)

### Q: Admin cabdis malah bisa lihat semua sekolah?

**A:** Cek `cabang_dinas_id` di tabel `users` — harus terisi. Kalau `null`, berarti dianggap admin provinsi.

### Q: Response `kabupaten` null?

**A:** Data `sekolah` belum di-seed. Jalankan:
```bash
php artisan migrate:fresh --seed
```

---

## 📞 Kontak Backend Developer

Kalau ada error atau bingung, hubungi backend developer (kamu):
- **GitHub:** (link repo)
- **Email:** (email kamu)

---

**Selamat Coding! 🚀**
