<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tambahkan header Cache-Control pada response API publik (GET, 200 OK).
 *
 * Keuntungan:
 * - Browser/CDN tidak perlu request ulang ke server untuk data yang sama
 *   selama 5 menit (max-age=300).
 * - stale-while-revalidate=60 → browser pakai cache lama dulu sambil
 *   diam-diam memperbaruinya di background, sehingga tidak ada jeda kosong.
 * - Hanya berlaku untuk GET 200 — POST/PUT/DELETE tidak ter-cache.
 */
class CachePublicApiResponse
{
    // Durasi cache dalam detik
    private const MAX_AGE             = 300; // 5 menit
    private const STALE_WHILE_REVALIDATE = 60;  // 1 menit grace period

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya cache GET request yang berhasil (status 200)
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $response->headers->set(
                'Cache-Control',
                sprintf(
                    'public, max-age=%d, stale-while-revalidate=%d',
                    self::MAX_AGE,
                    self::STALE_WHILE_REVALIDATE
                )
            );
        }

        return $response;
    }
}
