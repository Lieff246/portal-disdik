<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CabangDinas;
use App\Models\Sekolah;
use App\Models\SchoolSma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PortalController extends Controller
{
    /**
     * Data utama untuk halaman landing/dashboard publik.
     *
     * Endpoint ini menggabungkan semua data yang dibutuhkan
     * halaman utama dalam satu request agar performa lebih baik.
     *
     * Di-cache 10 menit — data sekolah jarang berubah di tengah hari.
     * Untuk invalidasi manual: Cache::forget('portal_landing_data')
     *
     * GET /api/v1/portal/landing
     * GET /api/v1/portal/landing-data (alias untuk backward compatibility)
     */
    public function landing(): JsonResponse
    {
        $data = Cache::remember('portal_landing_data', 600, function () {
            // Gunakan scope latestSemester() — filter semester terbaru otomatis
            $base = Sekolah::latestSemester();

            // ── Summary: semua COUNT/SUM dikerjakan dalam 1 query ─────────────
            $summaryRaw = (clone $base)
                ->selectRaw('
                    COUNT(*) as total_sekolah,
                    SUM(CASE WHEN bentuk_pendidikan = "SD" THEN 1 ELSE 0 END) as total_sd,
                    SUM(CASE WHEN bentuk_pendidikan = "SMP" THEN 1 ELSE 0 END) as total_smp,
                    SUM(CASE WHEN bentuk_pendidikan IN ("SMA","SMK","SLB") THEN 1 ELSE 0 END) as total_sma,
                    SUM(CASE WHEN bentuk_pendidikan IN ("TK","KB","SPS","TPA") THEN 1 ELSE 0 END) as total_paud,
                    SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t,
                    SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                    SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta,
                    SUM(jumlah_siswa) as total_siswa,
                    MAX(semester_id) as semester_id
                ')
                ->first();

            // ── Cards: Jumlah sekolah per kabupaten/kota ──────────────────────
            $perKabupaten = (clone $base)
                ->selectRaw('
                    kabupaten,
                    kode_kabupaten,
                    COUNT(*) as total_sekolah,
                    SUM(CASE WHEN bentuk_pendidikan IN ("TK","KB","SPS","TPA","RA") THEN 1 ELSE 0 END) as total_paud,
                    SUM(CASE WHEN bentuk_pendidikan IN ("SD","MI") THEN 1 ELSE 0 END) as total_sd,
                    SUM(CASE WHEN bentuk_pendidikan IN ("SMP","MTs") THEN 1 ELSE 0 END) as total_smp,
                    SUM(CASE WHEN bentuk_pendidikan IN ("TK","KB","SPS","TPA","RA","SD","MI","SMP","MTs") THEN 1 ELSE 0 END) as total_paud_smp,
                    SUM(CASE WHEN bentuk_pendidikan IN ("SMA","MA","SMK","SLB","SMTK") THEN 1 ELSE 0 END) as total_sma_provinsi,
                    SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                    SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta,
                    SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t,
                    SUM(jumlah_siswa) as total_siswa,
                    SUM(daya_tampung) as total_daya_tampung
                ')
                ->whereNotNull('kabupaten')
                ->groupBy('kabupaten', 'kode_kabupaten')
                ->orderBy('kabupaten')
                ->get()
                ->toArray(); // ← pastikan array murni agar cache aman di-decode

            // ── Neraca Rekap: Distribusi per jenjang pendidikan ───────────────
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
                ->get()
                ->toArray(); // ← pastikan array murni agar cache aman di-decode

            return [
                'summary' => [
                    'total_sekolah' => (int) ($summaryRaw->total_sekolah ?? 0),
                    'total_sd'      => (int) ($summaryRaw->total_sd      ?? 0),
                    'total_smp'     => (int) ($summaryRaw->total_smp     ?? 0),
                    'total_sma'     => (int) ($summaryRaw->total_sma     ?? 0),
                    'total_paud'    => (int) ($summaryRaw->total_paud    ?? 0),
                    'total_3t'      => (int) ($summaryRaw->total_3t      ?? 0),
                    'total_negeri'  => (int) ($summaryRaw->total_negeri  ?? 0),
                    'total_swasta'  => (int) ($summaryRaw->total_swasta  ?? 0),
                    'total_siswa'   => (int) ($summaryRaw->total_siswa   ?? 0),
                    'semester_id'   => $summaryRaw->semester_id,
                ],
                'cards'       => $perKabupaten,
                'neracaRekap' => $neracaRekap,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
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
                'status'  => 'error',
                'message' => "Sekolah dengan NPSN {$npsn} tidak ditemukan.",
            ], 404);
        }

        $nearbySchools = [];
        if ($sekolah->kode_kabupaten) {
            $nearbySchools = Sekolah::latestSemester()
                ->whereNotNull('lintang')
                ->whereNotNull('bujur')
                ->where('kode_kabupaten', $sekolah->kode_kabupaten)
                ->where('npsn', '!=', $npsn)
                ->limit(100)
                ->get()
                ->map(fn($s) => [
                    'id'        => $s->npsn,
                    'npsn'      => $s->npsn,
                    'name'      => $s->nama,
                    'grade'     => $s->bentuk_pendidikan,
                    'status'    => $s->status_sekolah,
                    'latitude'  => $s->lintang,
                    'longitude' => $s->bujur,
                ]);
        }

        // Data cabang dinas
        $cabdis = null;
        if ($sekolah->kode_kabupaten) {
            $raw = CabangDinas::whereJsonContains('kode_kabupaten', $sekolah->kode_kabupaten)->first();
            if ($raw) {
                $cabdis = [
                    'id'       => $raw->id,
                    'nama'     => $raw->nama,
                    'latitude' => $raw->map_lat,
                    'longitude'=> $raw->map_lng,
                    'map_zoom' => $raw->map_zoom,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'schools' => $nearbySchools,
                'cabdis'  => $cabdis,
            ],
        ]);
    }

    /**
     * Data summary per wilayah cabang dinas untuk halaman CabangDinas.tsx
     *
     * Di-cache 10 menit per cabdis slug.
     * Invalidasi: Cache::forget("portal_region_detail_{$slug}")
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

        $data = Cache::remember("portal_region_detail_{$slug}", 600, function () use ($cabdis, $slug) {
            $kodeKabList = $cabdis->kode_kabupaten ?? [];

            // Base query untuk sekolah di wilayah ini
            $base = Sekolah::latestSemester()->whereIn('kode_kabupaten', $kodeKabList);

            // Summary dalam 1 query
            $summaryRaw = (clone $base)
                ->selectRaw('
                    COUNT(*) as total_sekolah,
                    SUM(jumlah_siswa) as total_siswa,
                    SUM(CASE WHEN is_3t = 1 THEN 1 ELSE 0 END) as total_3t,
                    SUM(CASE WHEN status_sekolah = "Negeri" THEN 1 ELSE 0 END) as total_negeri,
                    SUM(CASE WHEN status_sekolah = "Swasta" THEN 1 ELSE 0 END) as total_swasta
                ')
                ->first();

            $summary = [
                'total_sekolah' => (int) ($summaryRaw->total_sekolah ?? 0),
                'total_siswa'   => (int) ($summaryRaw->total_siswa   ?? 0),
                'total_3t'      => (int) ($summaryRaw->total_3t      ?? 0),
                'total_negeri'  => (int) ($summaryRaw->total_negeri  ?? 0),
                'total_swasta'  => (int) ($summaryRaw->total_swasta  ?? 0),
                'total_rombel'  => 0,
                'total_guru'    => 0,
                'total_tendik'  => 0,
                'total_pegawai' => 0,
            ];

            // Daftar sekolah SMA/SMK/SLB di wilayah
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
                ])
                ->values()   // reset keys
                ->toArray(); // plain array agar cache aman

            return [
                'cabdis'  => [
                    'id'       => $cabdis->id,
                    'nama'     => $cabdis->nama,
                    'map_lat'  => $cabdis->map_lat,
                    'map_lng'  => $cabdis->map_lng,
                    'map_zoom' => $cabdis->map_zoom,
                ],
                'summary'        => $summary,
                'schools'        => $schools,
                'school_reports' => [
                    'sudah_update' => 0,
                    'belum_update' => $summary['total_sekolah'],
                    'persen'       => 0,
                ],
                'projections'    => null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Detail lengkap sekolah untuk halaman SchoolLandingSekolahku.tsx
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
            if (is_string($raw)) {
                $raw = json_decode($raw, true);
            }
            $polygon = $raw;
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'npsn'          => $sekolah->npsn,
                'name'          => $sekolah->nama,
                'status'        => $sekolah->status_sekolah,
                'polygon'       => $polygon,
                'gedung_detail' => [],
                'stats'         => [
                    'studentCount'   => $sekolah->jumlah_siswa ?? 0,
                    'dayaTampung'    => $sekolah->daya_tampung ?? 0,
                    'rombelCount'    => $sekolah->jumlah_siswa > 0
                        ? (int) round($sekolah->jumlah_siswa / 32)
                        : 0,
                    'totalTeachers'  => 0,
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
