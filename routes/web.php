<?php

use Illuminate\Support\Facades\Route;

// Halaman web biasa tidak dipakai lagi.
// Laravel ini sepenuhnya berfungsi sebagai API Backend.
// Semua endpoint ada di routes/api.php
Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Disdik Pemetaan API - Sulawesi Tengah',
        'version' => 'v1',
        'docs' => '/api/v1',
    ]);
});


