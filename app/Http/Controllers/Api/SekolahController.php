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
     *   search         - Cari nama sekolah (case-insensitive, partial match)
     *   wewenang       - Filter wewenang kabupaten/kota (true = hanya PAUD-SMP)
     */
    public function index(Request $request): JsonResponse
    {
        // Query tabel sekolah (SD, SMP, PAUD)
        $querySekolah = Sekolah::latestSemester()
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
            $querySekolah->where('bentuk_pendidikan', $request->jenjang);
        }

        if ($request->filled('kode_kabupaten')) {
            $querySekolah->where('kode_kabupaten', 'LIKE', "%{$request->kode_kabupaten}%");
        }

        if ($request->boolean('is_3t')) {
            $querySekolah->where('is_3t', true);
        }

        if ($request->filled('search')) {
            $querySekolah->where('nama', 'LIKE', "%{$request->search}%");
        }

        // Filter wewenang kabupaten/kota (hanya PAUD-SMP)
        if ($request->boolean('wewenang')) {
            $querySekolah->whereIn('bentuk_pendidikan', ['PAUD', 'TK', 'KB', 'TPA', 'SPS', 'SD', 'SMP']);
        }

        $sekolahData = $querySekolah->limit(5000)->get();

        // Query tabel school_sma (SMA, SMK, SLB) - gabungkan dengan sekolah
        $querySchoolSma = \DB::table('school_sma')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->select([
                'npsn',
                'name as nama',
                'grade as bentuk_pendidikan',
                'address as alamat_jalan',
                'kecamatan',
                'city as kabupaten',
                'kode_kabupaten',
                'latitude as lintang',
                'longitude as bujur',
                'status as status_sekolah',
            ]);

        if ($request->filled('kode_kabupaten')) {
            $querySchoolSma->where('kode_kabupaten', $request->kode_kabupaten);
        }

        if ($request->filled('jenjang')) {
            $querySchoolSma->where('grade', 'LIKE', "%{$request->jenjang}%");
        }

        if ($request->filled('search')) {
            $querySchoolSma->where('name', 'LIKE', "%{$request->search}%");
        }

        // Jika filter wewenang aktif, skip query school_sma (karena SMA/SMK/SLB bukan wewenang kabupaten)
        if ($request->boolean('wewenang')) {
            $schoolSmaData = collect([]);
        } else {
            $schoolSmaData = $querySchoolSma->limit(5000)->get()->map(function ($item) {
                // Transform ke format yang sama dengan SekolahResource
                return (object) [
                    'npsn' => $item->npsn ?? null,
                    'nama' => $item->nama,
                    'bentuk_pendidikan' => $item->bentuk_pendidikan,
                    'alamat_jalan' => $item->alamat_jalan,
                    'kecamatan' => $item->kecamatan,
                    'kabupaten' => $item->kabupaten,
                    'kode_kabupaten' => $item->kode_kabupaten,
                    'lintang' => (float) $item->lintang,
                    'bujur' => (float) $item->bujur,
                    'is_3t' => false,
                    'is_sekolah_alam' => false,
                    'jumlah_siswa' => 0,
                    'daya_tampung' => 0,
                    'status_sekolah' => $item->status_sekolah ?? 'Negeri',
                ];
            });
        }

        // Gabungkan 2 collection
        $merged = $sekolahData->concat($schoolSmaData);

        return response()->json([
            'status' => 'success',
            'total'  => $merged->count(),
            'data'   => SekolahResource::collection($merged),
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
