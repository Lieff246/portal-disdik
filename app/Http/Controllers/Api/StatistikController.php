<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\JsonResponse;

class StatistikController extends Controller
{
    /**
     * Statistik jumlah sekolah per kabupaten/kota.
     *
     * Response digunakan untuk:
     * - Card list kabupaten di dashboard publik
     * - Chart perbandingan antar kabupaten
     *
     * GET /api/v1/statistik/kabupaten
     */
    public function byKabupaten(): JsonResponse
    {
        $data = Sekolah::latestSemester()
            ->selectRaw('
                kabupaten,
                kode_kabupaten,
                COUNT(*) as total_sekolah,
                SUM(CASE WHEN bentuk_pendidikan IN ("SMA","SMK","SLB") THEN 1 ELSE 0 END) as total_sma,
                SUM(CASE WHEN bentuk_pendidikan = "SMP" THEN 1 ELSE 0 END) as total_smp,
                SUM(CASE WHEN bentuk_pendidikan = "SD" THEN 1 ELSE 0 END) as total_sd,
                SUM(CASE WHEN bentuk_pendidikan IN ("TK","KB","SPS","TPA") THEN 1 ELSE 0 END) as total_paud,
                SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t,
                SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta,
                SUM(jumlah_siswa) as total_siswa,
                SUM(daya_tampung) as total_daya_tampung
            ')
            ->whereNotNull('kabupaten')
            ->groupBy('kabupaten', 'kode_kabupaten')
            ->orderBy('kabupaten')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Statistik jumlah sekolah per jenjang pendidikan.
     *
     * Response digunakan untuk:
     * - Chart Donut/Pie distribusi jenjang
     * - Bar Chart perbandingan negeri vs swasta per jenjang
     *
     * GET /api/v1/statistik/jenjang
     */
    public function byJenjang(): JsonResponse
    {
        $data = Sekolah::latestSemester()
            ->selectRaw('
                bentuk_pendidikan,
                COUNT(*) as total,
                SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta,
                SUM(jumlah_siswa) as total_siswa,
                SUM(daya_tampung) as total_daya_tampung,
                SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t
            ')
            ->whereNotNull('bentuk_pendidikan')
            ->groupBy('bentuk_pendidikan')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}
