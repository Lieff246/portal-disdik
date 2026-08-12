# 🔍 Analisis Gap Backend vs Frontend

## 📋 Summary

Frontend React (portal-data) yang kamu pull dari teman menggunakan **struktur API yang berbeda** dengan backend Laravel yang kamu buat. Ada beberapa masalah:

1. **Frontend memanggil API ke SERVICE_REKAP (port 7777)** — tapi backend kamu hanya satu Laravel di port 8000
2. **Struktur response backend SUDAH BENAR** sesuai types frontend — tapi perlu sedikit penyesuaian
3. **Frontend pakai PortalService** untuk data landing, tapi endpoint berbeda
4. **Nama field detailSma tidak sesuai** dengan yang frontend expect

---

## 🎯 Masalah Utama

### 1. **Frontend Expect Multiple Services (Microservices Architecture)**

Frontend `.env.example`:
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_API_SERVICE_PTK=http://localhost:8001/api          # GTK/Teacher service
VITE_API_SERVICE_WEBSEKOLAH=http://localhost:8002/api   # School website
VITE_API_SERVICE_SCHOOL=http://localhost:8003/api       # School data
VITE_API_SERVICE_PPDB=http://localhost:8004/api         # PPDB service
VITE_API_SERVICE_BERANI_CERDAS=http://localhost:8005/api
VITE_API_SERVICE_OPENDATA=http://localhost:8006/api
VITE_API_SERVICE_REKAP=http://localhost:7777/api        # ⭐ INI UNTUK PEMETAAN
```

**Frontend pakai `SERVICE_REKAP` untuk data pemetaan:**
- `PortalService.getLandingData()` → `${API_REKAP_URL}/v1/portal/landing-data`
- `PortalService.getSchoolMapData(id)` → `${API_REKAP_URL}/v1/portal/school-map-data/${id}`

**Backend kamu hanya punya 1 Laravel di port 8000:**
- `/api/v1/portal/landing`
- `/api/v1/sekolah`
- etc.

### 2. **Endpoint Path Berbeda**

| Frontend Expect | Backend Actual | Status |
|----------------|----------------|--------|
| `/v1/portal/landing-data` | `/v1/portal/landing` | ❌ Beda nama |
| `/v1/portal/school-map-data/{id}` | Belum ada | ❌ Missing |
| `/v1/sekolah` | `/v1/sekolah` | ✅ OK |
| `/v1/sekolah/{npsn}` | `/v1/sekolah/{npsn}` | ✅ OK |
| `/v1/statistik/kabupaten` | `/v1/statistik/kabupaten` | ✅ OK |
| `/v1/statistik/jenjang` | `/v1/statistik/jenjang` | ✅ OK |
| `/v1/cabang-dinas` | `/v1/cabang-dinas` | ✅ OK |

### 3. **Response Structure — SUDAH 90% BENAR!**

#### ✅ `/portal/landing` — SUDAH BENAR
Backend response:
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
      }
    ],
    "neracaRekap": [
      {
        "bentuk_pendidikan": "SD",
        "jumlah": 1800,
        "jumlah_negeri": 1600,
        "jumlah_swasta": 200,
        "total_siswa": 300000
      }
    ]
  }
}
```

Frontend expect: **SAMA PERSIS!** ✅

#### ✅ `/sekolah` — SUDAH BENAR (pakai SekolahResource)
Backend response:
```json
{
  "status": "success",
  "total": 450,
  "data": [
    {
      "npsn": "40400001",
      "nama": "SDN 1 Palu",
      "bentuk_pendidikan": "SD",
      "alamat_jalan": "Jl. Merdeka No. 123",
      "kecamatan": "Palu Barat",
      "kabupaten": "Kota Palu",
      "kode_kabupaten": "7271",
      "nama_kabupaten": "Kota Palu",
      "lintang": -0.8951,
      "bujur": 119.8707,
      "is_3t": false,
      "is_sekolah_alam": false,
      "jumlah_siswa": 350,
      "daya_tampung": 400,
      "status_sekolah": "Negeri"
    }
  ]
}
```

Frontend expect: **SAMA!** ✅

#### ⚠️ `/sekolah/{npsn}` — Field `detailSma` BERBEDA

**Frontend expect:**
```typescript
detailSma: {
  id: string;
  name: string;              // ← Nama sekolah
  grade: string;             // ← Jenjang (SMA/SMK/SLB)
  status: string;            // ← Negeri/Swasta
  kecamatan: string;
  city: string;
  kepsek: string | null;
  nip_kepsek: string | null;
  no_hp_kepsek: string | null;
  status_kepsek: string | null;
  address: string;
  npsn: string;
  latitude: string | null;
  longitude: string | null;
  polygon: any | null;
}
```

**Backend actual (dari tabel `school_sma`):**
Kita belum tahu field apa saja yang ada. Perlu cek struktur tabel `school_sma`.

---

## 🛠️ Solusi

### Opsi A: **Ubah Frontend Config (RECOMMENDED untuk MVP)**

Ini solusi tercepat — ubah frontend agar pakai backend Laravel kamu:

1. Buat file `.env` di `portal-data/`:
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_API_SERVICE_PTK=http://localhost:8000/api
VITE_API_SERVICE_WEBSEKOLAH=http://localhost:8000/api
VITE_API_SERVICE_SCHOOL=http://localhost:8000/api
VITE_API_SERVICE_PPDB=http://localhost:8000/api
VITE_API_SERVICE_BERANI_CERDAS=http://localhost:8000/api
VITE_API_SERVICE_OPENDATA=http://localhost:8000/api
VITE_API_MESSAGE_URL=http://localhost:8000/api
VITE_API_SERVICE_REKAP=http://localhost:8000/api  # ⭐ SEMUA KE PORT 8000
VITE_API_PEMETAAN=http://localhost:8000/api       # ⭐ TAMBAH INI
```

2. **Ubah PortalService** agar pakai `api_pemetaan` bukan `SERVICE_REKAP`
3. **Ubah endpoint path** yang beda

### Opsi B: **Ubah Backend Routes (Lebih Lama)**

Tambah alias route agar backend support endpoint frontend:

```php
// Alias untuk kompatibilitas frontend
Route::get('/v1/portal/landing-data', [PortalController::class, 'landing']);
Route::get('/v1/portal/school-map-data/{id}', [PortalController::class, 'schoolMapData']);
```

---

## 📊 Checklist Perbaikan

### Backend (Laravel)

- [x] `/api/v1/portal/landing` — Response sudah benar ✅
- [x] `/api/v1/sekolah` — Response sudah benar ✅
- [x] `/api/v1/sekolah/{npsn}` — Response sudah benar ✅
- [x] `/api/v1/statistik/kabupaten` — Response sudah benar ✅
- [x] `/api/v1/statistik/jenjang` — Response sudah benar ✅
- [x] `/api/v1/cabang-dinas` — Response sudah benar ✅
- [ ] **PERLU FIX:** Tambah alias route `/v1/portal/landing-data`
- [ ] **PERLU FIX:** Buat endpoint `/v1/portal/school-map-data/{id}` (untuk detail peta sekolah)
- [ ] **PERLU CEK:** Field `detailSma` di response `/sekolah/{npsn}` — pastikan field sesuai frontend

### Frontend (React)

- [ ] **PERLU FIX:** Buat file `.env` dengan config ke `localhost:8000`
- [ ] **PERLU FIX:** Ubah `PortalService` agar pakai endpoint Laravel:
  - `getLandingData()` → `/v1/portal/landing` (bukan `/v1/portal/landing-data`)
  - `getSchoolMapData(id)` → endpoint baru atau pakai `/v1/sekolah/{id}`
- [ ] **PERLU FIX:** Update import config agar include `VITE_API_PEMETAAN`

---

## 🎯 Langkah Selanjutnya

Mau saya fix yang mana dulu?

**Opsi 1: Fix Backend** (Tambah alias route + endpoint school-map-data)
**Opsi 2: Fix Frontend** (Ubah .env + PortalService)
**Opsi 3: Fix Keduanya** (Hybrid — backend alias + frontend .env)

Pilih opsi mana?
