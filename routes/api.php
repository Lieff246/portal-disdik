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

    // Data Sekolah — untuk render marker peta
    Route::get('/sekolah', [\App\Http\Controllers\Api\SekolahController::class, 'index']);
    Route::get('/sekolah/{npsn}', [\App\Http\Controllers\Api\SekolahController::class, 'show']);

    // Data Statistik per Kabupaten
    Route::get('/statistik/kabupaten', [\App\Http\Controllers\Api\StatistikController::class, 'byKabupaten']);
    Route::get('/statistik/jenjang', [\App\Http\Controllers\Api\StatistikController::class, 'byJenjang']);

    // Cabang Dinas
    Route::get('/cabang-dinas', [\App\Http\Controllers\Api\CabangDinasController::class, 'index']);

});

// =====================================================
// PROTECTED ROUTES — Khusus Admin (Perlu Login)
// =====================================================
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // Info user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user()->load('cabangDinas');
    });

    // Admin: Kelola data sekolah
    Route::apiResource('admin/sekolah', \App\Http\Controllers\Api\Admin\SekolahController::class);

});
