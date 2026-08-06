<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SekolahController extends Controller
{
    /**
     * Daftar semua sekolah — digunakan untuk menampilkan marker di peta Leaflet.
     * Mendukung filter: jenjang, kabupaten, is_3t
     */
    public function index(Request $request): JsonResponse
    {
        $latestSemester = Sekolah::max('semester_id');

        $query = Sekolah::where('semester_id', $latestSemester)
            ->whereNotNull('lintang')
            ->whereNotNull('bujur')
            ->select([
                'npsn', 'nama', 'bentuk_pendidikan', 'alamat_jalan',
                'kecamatan', 'kabupaten', 'kode_kabupaten',
                'lintang', 'bujur', 'is_3t', 'is_sekolah_alam',
                'jumlah_siswa', 'daya_tampung', 'status_sekolah',
            ]);

        // Filter jenjang (SD, SMP, SMA, SMK, TK, dll)
        if ($request->filled('jenjang')) {
            $query->where('bentuk_pendidikan', $request->jenjang);
        }

        // Filter kabupaten berdasarkan kode_kabupaten
        if ($request->filled('kode_kabupaten')) {
            $query->where('kode_kabupaten', $request->kode_kabupaten);
        }

        // Filter sekolah 3T
        if ($request->boolean('is_3t')) {
            $query->where('is_3t', true);
        }

        $sekolah = $query->limit(5000)->get(); // Batasi agar peta tidak berat

        return response()->json([
            'status' => 'success',
            'total'  => $sekolah->count(),
            'data'   => $sekolah,
        ]);
    }

    /**
     * Detail satu sekolah berdasarkan NPSN.
     * Digunakan untuk popup di peta saat marker diklik.
     */
    public function show(string $npsn): JsonResponse
    {
        $latestSemester = Sekolah::max('semester_id');

        $sekolah = Sekolah::where('npsn', $npsn)
            ->where('semester_id', $latestSemester)
            ->with('detailSma')
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data'   => $sekolah,
        ]);
    }
}
