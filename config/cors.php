<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini mengizinkan frontend React (berjalan di port/domain berbeda)
    | untuk memanggil API Laravel kita. Saat development, kita izinkan semua origin.
    | Saat production, ganti '*' dengan domain frontend yang sebenarnya.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // DEVELOPMENT: Izinkan semua origin (termasuk localhost:5173 milik Vite React)
    // PRODUCTION: Ganti dengan ['https://domain-frontend-kalian.com']
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
