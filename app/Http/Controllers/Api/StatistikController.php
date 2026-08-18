<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use App\Models\SchoolSma;
use Illuminate\Http\JsonResponse;

class StatistikController extends Controller
{
    /**
     * Statistik jumlah sekolah per kabupaten/kota.
     *
     * Di-cache 10 menit — data ini stabil dan jarang berubah.
     * Invalidasi: Cache::forget('statistik_kabupaten')
     *
     * GET /api/v1/statistik/kabupaten
     */
    public function byKabupaten(): JsonResponse
    {
        $data = \Cache::remember('statistik_kabupaten', 600, function () {
            return Sekolah::latestSemester()
                ->selectRaw('
                    kabupaten,
                    kode_kabupaten,
                    COUNT(*) as total_sekolah,

                    -- Wewenang PROVINSI: SMA/SMK/SLB (dan setara)
                    SUM(CASE WHEN bentuk_pendidikan IN ("SMA","MA","SMK","SLB","SMTK") THEN 1 ELSE 0 END) as total_sma_provinsi,

                    -- Wewenang KABUPATEN: PAUD s/d SMP (dan setara)
                    SUM(CASE WHEN bentuk_pendidikan IN ("TK","KB","SPS","TPA","RA") THEN 1 ELSE 0 END) as total_paud,
                    SUM(CASE WHEN bentuk_pendidikan IN ("SD","MI") THEN 1 ELSE 0 END) as total_sd,
                    SUM(CASE WHEN bentuk_pendidikan IN ("SMP","MTs") THEN 1 ELSE 0 END) as total_smp,
                    SUM(CASE WHEN bentuk_pendidikan IN ("TK","KB","SPS","TPA","RA","SD","MI","SMP","MTs") THEN 1 ELSE 0 END) as total_paud_smp,

                    -- Umum
                    SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t,
                    SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                    SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta,
                    SUM(jumlah_siswa) as total_siswa,
                    SUM(daya_tampung) as total_daya_tampung
                ')
                ->whereNotNull('kabupaten')
                ->groupBy('kabupaten', 'kode_kabupaten')
                ->orderBy('kabupaten')
                ->get()
                ->toArray(); // plain array agar JSON decode dari cache selalu array
        });

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

    /**
     * Statistik jumlah sekolah SMA/SMK/SLB per grade dari tabel school_sma.
     *
     * Digunakan untuk Card Provinsi di dashboard publik yang menampilkan
     * ringkasan sekolah menengah kewenangan Provinsi Sulawesi Tengah.
     *
     * FALLBACK: Jika tabel school_sma kosong, ambil dari tabel sekolah.
     *
     * GET /api/v1/statistik/sma-provinsi
     */
    public function byGradeProvinsi(): JsonResponse
    {
        // Cache 1 jam — data ini jarang berubah, tidak perlu query tiap request
        $data = \Cache::remember('statistik_sma_provinsi', 3600, function () {
            // Cek dulu apakah ada data di school_sma
            $countSma = SchoolSma::count();
            
            if ($countSma > 0) {
                // Gunakan tabel school_sma (data lengkap dengan polygon, dll)
                return SchoolSma::selectRaw('
                        grade as bentuk_pendidikan,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                        SUM(CASE WHEN status = "Swasta" THEN 1 ELSE 0 END) as total_swasta
                    ')
                    ->whereNotNull('grade')
                    ->groupBy('grade')
                    ->orderByRaw('FIELD(grade, "SMA", "SMK", "SLB", "SMTK")')
                    ->get()
                    ->toArray();
            }
            
            // Fallback: Gunakan tabel sekolah (data Dapodik)
            return Sekolah::latestSemester()
                ->selectRaw('
                    bentuk_pendidikan,
                    COUNT(*) as total,
                    SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                    SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta
                ')
                ->whereIn('bentuk_pendidikan', ['SMA', 'SMK', 'SLB'])
                ->groupBy('bentuk_pendidikan')
                ->orderByRaw('FIELD(bentuk_pendidikan, "SMA", "SMK", "SLB")')
                ->get()
                ->toArray();
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}
