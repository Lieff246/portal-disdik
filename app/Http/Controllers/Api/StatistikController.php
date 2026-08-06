<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\JsonResponse;

class StatistikController extends Controller
{
    /**
     * Statistik jumlah sekolah per kabupaten/kota.
     * Digunakan untuk Card list di sisi kanan dashboard.
     */
    public function byKabupaten(): JsonResponse
    {
        $latestSemester = Sekolah::max('semester_id');

        $data = Sekolah::where('semester_id', $latestSemester)
            ->selectRaw('
                kabupaten,
                kode_kabupaten,
                COUNT(*) as total_sekolah,
                SUM(CASE WHEN bentuk_pendidikan IN ("SMA","SMK","SLB") THEN 1 ELSE 0 END) as total_sma,
                SUM(CASE WHEN bentuk_pendidikan = "SMP" THEN 1 ELSE 0 END) as total_smp,
                SUM(CASE WHEN bentuk_pendidikan = "SD" THEN 1 ELSE 0 END) as total_sd,
                SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t,
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
     * Digunakan untuk Chart (Donut/Bar) di dashboard.
     */
    public function byJenjang(): JsonResponse
    {
        $latestSemester = Sekolah::max('semester_id');

        $data = Sekolah::where('semester_id', $latestSemester)
            ->selectRaw('bentuk_pendidikan as jenjang, COUNT(*) as total')
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
