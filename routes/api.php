<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Disdik Pemetaan Sulawesi Tengah
|--------------------------------------------------------------------------
|
| Semua route di sini bisa diakses oleh frontend React.
| Route publik (tanpa login) digunakan untuk tampilan peta publik.
| Route yang dilindungi membutuhkan token autentikasi.
|
*/

// =====================================================
// AUTH ROUTES
// =====================================================
Route::prefix('v1')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// =====================================================
// PUBLIC ROUTES — Bisa diakses siapa saja tanpa login
// =====================================================
Route::prefix('v1')->group(function () {

    // Dashboard / Landing — data utama untuk halaman publik
    Route::get('/portal/landing', [\App\Http\Controllers\Api\PortalController::class, 'landing']);

    // Data peta wilayah cabang dinas (sekolah + summary per wilayah)
    Route::get('/portal/region-detail', [\App\Http\Controllers\Api\PortalController::class, 'regionDetail']);

    // Detail sekolah untuk halaman sekolahku (polygon + stats)
    Route::get('/portal/school-detail/{npsn}', [\App\Http\Controllers\Api\PortalController::class, 'schoolDetail']);

    // Data Sekolah — untuk render marker peta
    Route::get('/sekolah', [\App\Http\Controllers\Api\SekolahController::class, 'index']);
    Route::get('/sekolah/{npsn}', [\App\Http\Controllers\Api\SekolahController::class, 'show']);

    // Data Statistik per Kabupaten
    Route::get('/statistik/kabupaten', [\App\Http\Controllers\Api\StatistikController::class, 'byKabupaten']);
    Route::get('/statistik/jenjang', [\App\Http\Controllers\Api\StatistikController::class, 'byJenjang']);

    // Statistik SMA/SMK/SLB dari tabel school_sma (kewenangan Provinsi)
    Route::get('/statistik/sma-provinsi', [\App\Http\Controllers\Api\StatistikController::class, 'byGradeProvinsi']);

    // Cabang Dinas
    Route::get('/cabang-dinas', [\App\Http\Controllers\Api\CabangDinasController::class, 'index']);

});

// ═════════════════════════════════════════════════════════════════════════════
// LEGACY API COMPATIBILITY
// Endpoint ini untuk backward compatibility dengan frontend yang sudah ada.
// Frontend expect endpoint dengan nama berbeda, jadi kita buat alias di sini.
// ═════════════════════════════════════════════════════════════════════════════
Route::prefix('v1')->group(function () {
    // Alias: /portal/landing-data → /portal/landing
    Route::get('/portal/landing-data', [\App\Http\Controllers\Api\PortalController::class, 'landing']);
    
    // Endpoint baru: school-map-data (untuk detail peta + nearby schools)
    Route::get('/portal/school-map-data/{npsn}', [\App\Http\Controllers\Api\PortalController::class, 'schoolMapData']);
});

// =====================================================
// PROTECTED ROUTES — Khusus Admin (Perlu Login)
// =====================================================
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // Info user yang sedang login
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'id'               => $user->id,
            'name'             => $user->name,
            'email'            => $user->email,
            'role'             => $user->role,
            'cabang_dinas_id'  => $user->cabang_dinas_id,
            'kode_kabupaten'   => $user->kode_kabupaten,
        ]);
    });

    // Admin: Kelola data sekolah
    Route::apiResource('admin/sekolah', \App\Http\Controllers\Api\Admin\SekolahController::class);

});
