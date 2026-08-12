<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabangDinas;
use App\Models\Sekolah;
use App\Models\SchoolSma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    /**
     * Data utama untuk halaman landing/dashboard publik.
     *
     * Endpoint ini menggabungkan semua data yang dibutuhkan
     * halaman utama dalam satu request agar performa lebih baik.
     *
     * GET /api/v1/portal/landing
     * GET /api/v1/portal/landing-data (alias untuk backward compatibility)
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

    /**
     * Data untuk tampilan peta detail sekolah.
     * 
     * Digunakan oleh frontend SchoolLanding.tsx untuk menampilkan:
     * - Sekolah yang sedang dilihat (current school)
     * - Sekolah-sekolah lain di sekitarnya (nearby schools) untuk ditampilkan sebagai marker kecil
     * - Data cabang dinas wilayah sekolah (untuk context geografis)
     *
     * GET /api/v1/portal/school-map-data/{npsn}
     */
    public function schoolMapData(string $npsn): JsonResponse
    {
        // Cari sekolah by NPSN
        $sekolah = Sekolah::latestSemester()
            ->where('npsn', $npsn)
            ->with('detailSma')
            ->first();

        if (!$sekolah) {
            return response()->json([
                'status' => 'error',
                'message' => "Sekolah dengan NPSN {$npsn} tidak ditemukan."
            ], 404);
        }

        // Cari sekolah nearby (di kabupaten yang sama)
        $lat = $sekolah->lintang;
        $lng = $sekolah->bujur;
        
        $nearbySchools = [];
        if ($sekolah->kode_kabupaten) {
            $nearbySchools = Sekolah::latestSemester()
                ->whereNotNull('lintang')
                ->whereNotNull('bujur')
                ->where('kode_kabupaten', $sekolah->kode_kabupaten)
                ->where('npsn', '!=', $npsn)
                ->limit(100) // Batasi 100 sekolah terdekat
                ->get()
                ->map(function($s) use ($npsn) {
                    return [
                        'id' => $s->npsn,
                        'npsn' => $s->npsn,
                        'name' => $s->nama,
                        'grade' => $s->bentuk_pendidikan,
                        'status' => $s->status_sekolah,
                        'latitude' => $s->lintang,
                        'longitude' => $s->bujur,
                    ];
                });
        }

        // Data cabang dinas (cari berdasarkan kode_kabupaten sekolah)
        $cabdis = null;
        if ($sekolah->kode_kabupaten) {
            $cabdis = \App\Models\CabangDinas::whereJsonContains('kode_kabupaten', $sekolah->kode_kabupaten)
                ->first();
            
            if ($cabdis) {
                $cabdis = [
                    'id' => $cabdis->id,
                    'nama' => $cabdis->nama,
                    'latitude' => $cabdis->map_lat,
                    'longitude' => $cabdis->map_lng,
                    'map_zoom' => $cabdis->map_zoom,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'schools' => $nearbySchools,
                'cabdis' => $cabdis,
            ],
        ]);
    }

    /**
     * Data summary per wilayah cabang dinas untuk halaman CabangDinas.tsx
     *
     * Mengembalikan:
     * - summary statistik sekolah di wilayah tersebut
     * - daftar sekolah (untuk marker peta + sidebar kanan)
     * - info cabang dinas
     *
     * GET /api/v1/portal/region-detail?slug=cabdis-1
     */
    public function regionDetail(Request $request): JsonResponse
    {
        $slug = $request->get('slug', 'cabdis-1');

        // Ekstrak nomor wilayah dari slug (cabdis-1 → 1)
        $wilayahNum = (int) str_replace('cabdis-', '', $slug);

        // Cari data cabang dinas
        $cabdis = CabangDinas::find($wilayahNum);

        if (!$cabdis) {
            return response()->json(['status' => 'error', 'message' => 'Cabang dinas tidak ditemukan.'], 404);
        }

        $kodeKabList = $cabdis->kode_kabupaten ?? [];

        // Base query untuk sekolah di wilayah ini
        $base = Sekolah::latestSemester()->whereIn('kode_kabupaten', $kodeKabList);

        // Summary statistik wilayah
        $summary = [
            'total_sekolah'  => (clone $base)->count(),
            'total_siswa'    => (int) (clone $base)->sum('jumlah_siswa'),
            'total_3t'       => (clone $base)->where('is_3t', true)->count(),
            'total_negeri'   => (clone $base)->where('status_sekolah', 'Negeri')->count(),
            'total_swasta'   => (clone $base)->where('status_sekolah', 'Swasta')->count(),
            // Field GTK belum ada — isi 0 agar frontend tidak crash
            'total_rombel'   => 0,
            'total_guru'     => 0,
            'total_tendik'   => 0,
            'total_pegawai'  => 0,
        ];

        // Daftar sekolah SMA/SMK/SLB di wilayah (sesuai scope tugas cabdis = SMA ke atas)
        $schools = Sekolah::latestSemester()
            ->whereIn('kode_kabupaten', $kodeKabList)
            ->whereIn('bentuk_pendidikan', ['SMA', 'SMK', 'SLB'])
            ->whereNotNull('lintang')
            ->whereNotNull('bujur')
            ->select(['npsn', 'nama', 'bentuk_pendidikan', 'status_sekolah', 'lintang', 'bujur', 'kecamatan', 'kabupaten'])
            ->limit(500)
            ->get()
            ->map(fn($s) => [
                'id'        => $s->npsn,
                'npsn'      => $s->npsn,
                'name'      => $s->nama,
                'grade'     => $s->bentuk_pendidikan,
                'status'    => $s->status_sekolah,
                'latitude'  => $s->lintang,
                'longitude' => $s->bujur,
                'kecamatan' => $s->kecamatan,
                'kabupaten' => $s->kabupaten,
            ]);

        // Progress laporan — belum ada tabel, return struktur kosong
        $schoolReports = [
            'sudah_update'  => 0,
            'belum_update'  => $summary['total_sekolah'],
            'persen'        => 0,
        ];

        return response()->json([
            'status' => 'success',
            'data'   => [
                'cabdis'         => [
                    'id'       => $cabdis->id,
                    'nama'     => $cabdis->nama,
                    'map_lat'  => $cabdis->map_lat,
                    'map_lng'  => $cabdis->map_lng,
                    'map_zoom' => $cabdis->map_zoom,
                ],
                'summary'        => $summary,
                'schools'        => $schools,
                'school_reports' => $schoolReports,
                'projections'    => null, // Data kepegawaian belum tersedia
            ],
        ]);
    }

    /**
     * Detail lengkap sekolah untuk halaman SchoolLandingSekolahku.tsx
     *
     * Mengembalikan:
     * - Data identitas sekolah
     * - Polygon area sekolah (dari school_sma)
     * - Stats dasar (siswa, daya tampung, akreditasi)
     *
     * GET /api/v1/portal/school-detail/{npsn}
     */
    public function schoolDetail(string $npsn): JsonResponse
    {
        $sekolah = Sekolah::latestSemester()
            ->where('npsn', $npsn)
            ->with('detailSma')
            ->first();

        if (!$sekolah) {
            return response()->json(['status' => 'error', 'message' => 'Sekolah tidak ditemukan.'], 404);
        }

        $sma = $sekolah->detailSma;

        // Polygon dari school_sma jika tersedia
        $polygon = null;
        if ($sma && $sma->polygon) {
            $raw = $sma->polygon;
            // Jika masih berupa string JSON, decode dulu
            if (is_string($raw)) {
                $raw = json_decode($raw, true);
            }
            $polygon = $raw;
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'npsn'    => $sekolah->npsn,
                'name'    => $sekolah->nama,
                'status'  => $sekolah->status_sekolah,
                'polygon' => $polygon,
                // Detail gedung belum ada tabel tersendiri
                'gedung_detail' => [],
                'stats'  => [
                    'studentCount'   => $sekolah->jumlah_siswa ?? 0,
                    'dayaTampung'    => $sekolah->daya_tampung ?? 0,
                    'rombelCount'    => $sekolah->jumlah_siswa > 0
                        ? (int) round($sekolah->jumlah_siswa / 32)
                        : 0,
                    'totalTeachers'  => 0, // Belum ada data GTK
                    'accreditation'  => $sekolah->akreditasi ?? '-',
                    'principalPhone' => $sma->no_hp_kepsek ?? $sekolah->nomor_telepon ?? '-',
                    'email'          => $sekolah->email ?? '-',
                    'kepsek'         => $sma->kepsek ?? '-',
                    'nip_kepsek'     => $sma->nip_kepsek ?? '-',
                ],
            ],
        ]);
    }
}
