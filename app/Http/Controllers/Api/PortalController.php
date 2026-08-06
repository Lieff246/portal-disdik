<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\JsonResponse;

class PortalController extends Controller
{
    /**
     * Data utama untuk halaman landing/dashboard publik.
     * Dipanggil oleh: PortalService.getLandingData() di frontend React.
     */
    public function landing(): JsonResponse
    {
        // Ambil semester terbaru yang tersedia di data
        $latestSemester = Sekolah::max('semester_id');

        $query = Sekolah::where('semester_id', $latestSemester);

        // --- Summary: Ringkasan total keseluruhan ---
        $totalSekolah  = (clone $query)->count();
        $totalSD       = (clone $query)->where('bentuk_pendidikan', 'SD')->count();
        $totalSMP      = (clone $query)->where('bentuk_pendidikan', 'SMP')->count();
        $totalSMA      = (clone $query)->whereIn('bentuk_pendidikan', ['SMA', 'SMK', 'SLB'])->count();
        $totalPAUD     = (clone $query)->whereIn('bentuk_pendidikan', ['TK', 'KB', 'SPS', 'TPA'])->count();
        $total3T       = (clone $query)->where('is_3t', true)->count();

        // --- Cards: Data per Kabupaten/Kota ---
        $perKabupaten = (clone $query)
            ->selectRaw('kabupaten, kode_kabupaten, COUNT(*) as total_sekolah')
            ->whereNotNull('kabupaten')
            ->groupBy('kabupaten', 'kode_kabupaten')
            ->orderBy('kabupaten')
            ->get();

        // --- Neraca Recap: Distribusi per jenjang ---
        $neracaRekap = (clone $query)
            ->selectRaw('bentuk_pendidikan, COUNT(*) as jumlah')
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
                    'semester_id'   => $latestSemester,
                ],
                'cards'      => $perKabupaten,
                'neracaRekap' => $neracaRekap,
            ],
        ]);
    }
}
