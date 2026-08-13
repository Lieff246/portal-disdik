<?php

use App\Http\Middleware\CachePublicApiResponse;
use App\Http\Middleware\HandleAppearance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',     // ← Daftarkan API routes
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Inertia dihapus — Laravel sekarang murni jadi API Backend
        $middleware->web(append: [
            HandleAppearance::class,
        ]);

        // CORS untuk frontend React teman kamu (izinkan semua origin saat development)
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Cache-Control header untuk semua GET API publik (browser & CDN caching)
        $middleware->api(append: [
            CachePublicApiResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Semua error di endpoint /api/* selalu dikembalikan dalam format JSON
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

