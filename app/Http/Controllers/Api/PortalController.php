<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\JsonResponse;

class PortalController extends Controller
{
    /**
     * Data utama untuk halaman landing/dashboard publik.
     *
     * Endpoint ini menggabungkan semua data yang dibutuhkan
     * halaman utama dalam satu request agar performa lebih baik.
     *
     * GET /api/v1/portal/landing
     */
    public function landing(): JsonResponse
    {
        // Gunakan scope latestSemester() — filter semester terbaru otomatis
        $base = Sekolah::latestSemester();

        // ── Summary: Ringkasan total keseluruhan ──────────────────────────────
        $totalSekolah = (clone $base)->count();
        $totalSD      = (clone $base)->where('bentuk_pendidikan', 'SD')->count();
        $totalSMP     = (clone $base)->where('bentuk_pendidikan', 'SMP')->count();
        $totalSMA     = (clone $base)->whereIn('bentuk_pendidikan', ['SMA', 'SMK', 'SLB'])->count();
        $totalPAUD    = (clone $base)->whereIn('bentuk_pendidikan', ['TK', 'KB', 'SPS', 'TPA'])->count();
        $total3T      = (clone $base)->where('is_3t', true)->count();
        $totalNegeri  = (clone $base)->where('status_sekolah', 'Negeri')->count();
        $totalSwasta  = (clone $base)->where('status_sekolah', 'Swasta')->count();
        $totalSiswa   = (clone $base)->sum('jumlah_siswa');

        $latestSemester = Sekolah::max('semester_id');

        // ── Cards: Jumlah sekolah per kabupaten/kota ──────────────────────────
        $perKabupaten = (clone $base)
            ->selectRaw('
                kabupaten,
                kode_kabupaten,
                COUNT(*) as total_sekolah,
                SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta,
                SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t,
                SUM(jumlah_siswa) as total_siswa,
                SUM(daya_tampung) as total_daya_tampung
            ')
            ->whereNotNull('kabupaten')
            ->groupBy('kabupaten', 'kode_kabupaten')
            ->orderBy('kabupaten')
            ->get();

        // ── Neraca Rekap: Distribusi per jenjang pendidikan ───────────────────
        $neracaRekap = (clone $base)
            ->selectRaw('
                bentuk_pendidikan,
                COUNT(*) as jumlah,
                SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as jumlah_negeri,
                SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as jumlah_swasta,
                SUM(jumlah_siswa) as total_siswa
            ')
            ->whereNotNull('bentuk_pendidikan')
            ->groupBy('bentuk_pendidikan')
            ->orderByDesc('jumlah')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'summary' => [
                    'total_sekolah' => $totalSekolah,
                    'total_sd'      => $totalSD,
                    'total_smp'     => $totalSMP,
                    'total_sma'     => $totalSMA,
                    'total_paud'    => $totalPAUD,
                    'total_3t'      => $total3T,
                    'total_negeri'  => $totalNegeri,
                    'total_swasta'  => $totalSwasta,
                    'total_siswa'   => (int) $totalSiswa,
                    'semester_id'   => $latestSemester,
                ],
                'cards'      => $perKabupaten,
                'neracaRekap' => $neracaRekap,
            ],
        ]);
    }
}
