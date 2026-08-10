<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SekolahResource;
use App\Models\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SekolahController extends Controller
{
    /**
     * Daftar sekolah untuk marker peta Leaflet.
     *
     * Hanya mengambil field yang dibutuhkan peta agar response ringan.
     * Dibatasi 5000 record agar browser tidak kewalahan render marker.
     *
     * GET /api/v1/sekolah
     *
     * Query params:
     *   jenjang        - Filter bentuk pendidikan (SD, SMP, SMA, SMK, SLB, TK, dll)
     *   kode_kabupaten - Filter kode wilayah kabupaten (misal: 7271)
     *   is_3t          - Filter sekolah 3T (true/false)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sekolah::latestSemester()
            ->whereNotNull('lintang')
            ->whereNotNull('bujur')
            ->where('lintang', '!=', 0)
            ->where('bujur', '!=', 0)
            ->select([
                'npsn', 'nama', 'bentuk_pendidikan',
                'alamat_jalan', 'kecamatan', 'kabupaten', 'kode_kabupaten',
                'lintang', 'bujur',
                'is_3t', 'is_sekolah_alam',
                'jumlah_siswa', 'daya_tampung', 'status_sekolah',
                'akreditasi', 'akses_internet',
            ]);

        if ($request->filled('jenjang')) {
            $query->where('bentuk_pendidikan', $request->jenjang);
        }

        if ($request->filled('kode_kabupaten')) {
            $query->where('kode_kabupaten', $request->kode_kabupaten);
        }

        // Pakai boolean() agar "true", "1", "on" semua ditangani
        if ($request->boolean('is_3t')) {
            $query->where('is_3t', true);
        }

        $sekolah = $query->limit(5000)->get();

        return response()->json([
            'status' => 'success',
            'total'  => $sekolah->count(),
            'data'   => SekolahResource::collection($sekolah),
        ]);
    }

    /**
     * Detail satu sekolah berdasarkan NPSN.
     *
     * Load relasi detailSma untuk mendapatkan:
     * - Kepala sekolah (kepsek, nip, hp, status)
     * - Koordinat akurat (latitude/longitude dari school_sma)
     * - Polygon gedung (untuk tampilan peta detail)
     *
     * GET /api/v1/sekolah/{npsn}
     */
    public function show(string $npsn): JsonResponse
    {
        $sekolah = Sekolah::latestSemester()
            ->where('npsn', $npsn)
            ->with('detailSma')
            ->first();

        if (! $sekolah) {
            return response()->json([
                'status'  => 'error',
                'message' => "Sekolah dengan NPSN {$npsn} tidak ditemukan.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => new SekolahResource($sekolah),
        ]);
    }
}
