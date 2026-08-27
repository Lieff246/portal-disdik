<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SekolahController extends Controller
{
    /**
     * Batasi query sekolah berdasarkan role user yang sedang login.
     *
     * - admin_provinsi : bisa akses semua sekolah di seluruh Sulawesi Tengah
     * - admin_cabdis   : hanya bisa akses sekolah di kabupaten wilayahnya
     * - admin_kab_kota : hanya bisa akses sekolah di satu kabupatennya
     *
     * Fungsi ini dipanggil di semua method agar aturan konsisten.
     */
    private function applyWilayahFilter($query, Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('admin_cabdis')) {
            // Ambil daftar kode_kabupaten dari relasi cabang dinas user
            // Contoh: Wilayah 1 punya kode_kabupaten ["7271", "7210"]
            $kodeKabList = $user->cabangDinas?->kode_kabupaten ?? [];
            $query->whereIn('kode_kabupaten', $kodeKabList);

        } elseif ($user->hasRole('admin_kab_kota')) {
            // Admin kab/kota hanya boleh lihat 1 kabupaten miliknya
            $query->where('kode_kabupaten', $user->kode_kabupaten);
        }

        // admin_provinsi: tidak ada filter tambahan — bisa lihat semua
        return $query;
    }

    /**
     * Cek apakah user punya akses ke sekolah tertentu.
     * Dipakai di show(), update(), destroy() agar tidak bisa
     * akses sekolah di luar wilayahnya.
     */
    private function userCanAccessSekolah(Request $request, Sekolah $sekolah): bool
    {
        $user = $request->user();

        if ($user->hasRole('admin_provinsi')) {
            return true; // admin provinsi bisa akses semua
        }

        if ($user->hasRole('admin_cabdis')) {
            $kodeKabList = $user->cabangDinas?->kode_kabupaten ?? [];

            return in_array($sekolah->kode_kabupaten, $kodeKabList);
        }

        if ($user->hasRole('admin_kab_kota')) {
            return $sekolah->kode_kabupaten === $user->kode_kabupaten;
        }

        return false;
    }

    // =========================================================================

    /**
     * Daftar sekolah untuk panel admin dengan pagination.
     *
     * GET /api/v1/admin/sekolah
     *
     * Query params:
     *   search         - Cari nama sekolah atau NPSN
     *   jenjang        - Filter bentuk pendidikan
     *   kode_kabupaten - Filter kabupaten (admin provinsi saja)
     *   status_sekolah - "Negeri" atau "Swasta"
     *   is_3t          - true/false
     *   per_page       - Jumlah per halaman (default: 25, max: 100)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sekolah::latestSemester()
            ->with('detailSma')
            ->select([
                'sekolah_id', 'semester_id',
                'npsn', 'nama', 'bentuk_pendidikan',
                'alamat_jalan', 'kecamatan', 'kabupaten', 'kode_kabupaten',
                'lintang', 'bujur', 'status_sekolah', 'akreditasi',
                'jumlah_siswa', 'daya_tampung',
                'is_3t', 'is_sekolah_alam',
                'email', 'nomor_telepon', 'website',
                'akses_internet', 'sumber_listrik',
                'last_update',
            ]);

        // Terapkan filter wilayah berdasarkan role
        $this->applyWilayahFilter($query, $request);

        // Filter tambahan dari query params
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', "%{$keyword}%")
                    ->orWhere('npsn', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('jenjang')) {
            $query->where('bentuk_pendidikan', $request->jenjang);
        }

        if ($request->filled('kode_kabupaten')) {
            $query->where('kode_kabupaten', $request->kode_kabupaten);
        }

        if ($request->filled('status_sekolah')) {
            $query->where('status_sekolah', $request->status_sekolah);
        }

        if ($request->boolean('is_3t')) {
            $query->where('is_3t', true);
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $data = $query->orderBy('nama')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data->items(),
            'meta' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ]);
    }

    /**
     * Tambah sekolah baru.
     *
     * POST /api/v1/admin/sekolah
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'npsn' => 'required|string|max:20|unique:sekolah,npsn',
            'bentuk_pendidikan' => 'required|string|max:20',
            'status_sekolah' => 'required|in:Negeri,Swasta',
            'alamat_jalan' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'required|string|max:100',
            'kode_kabupaten' => 'required|string|max:30',
            'provinsi' => 'nullable|string|max:100',
            'lintang' => 'nullable|numeric|between:-90,90',
            'bujur' => 'nullable|numeric|between:-180,180',
            'email' => 'nullable|email|max:100',
            'nomor_telepon' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:150',
            'akreditasi' => 'nullable|string|max:10',
            'jumlah_siswa' => 'nullable|integer|min:0',
            'daya_tampung' => 'nullable|integer|min:0',
            'is_3t' => 'boolean',
            'is_sekolah_alam' => 'boolean',
            'akses_internet' => 'nullable|string|max:50',
            'sumber_listrik' => 'nullable|string|max:50',
            'waktu_penyelenggaraan' => 'nullable|string|max:50',
        ]);

        // Cek apakah user boleh tambah sekolah di kabupaten yang dipilih
        $user = $request->user();
        if ($user->hasRole('admin_cabdis')) {
            $kodeKabList = $user->cabangDinas?->kode_kabupaten ?? [];
            if (! in_array($validated['kode_kabupaten'], $kodeKabList)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk menambah sekolah di kabupaten ini.',
                ], 403);
            }
        } elseif ($user->hasRole('admin_kab_kota')) {
            if ($validated['kode_kabupaten'] !== $user->kode_kabupaten) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk menambah sekolah di kabupaten ini.',
                ], 403);
            }
        }

        $latestSemester = Sekolah::max('semester_id') ?? date('Y').'1';

        $sekolah = Sekolah::create(array_merge($validated, [
            'sekolah_id' => 'SCH-'.strtoupper(Str::random(8)),
            'semester_id' => $latestSemester,
            'provinsi' => $validated['provinsi'] ?? 'Sulawesi Tengah',
            'kode_provinsi' => '72',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Sekolah berhasil ditambahkan.',
            'data' => $sekolah,
        ], 201);
    }

    /**
     * Detail satu sekolah untuk admin.
     *
     * GET /api/v1/admin/sekolah/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $sekolah = Sekolah::latestSemester()
            ->where(function ($q) use ($id) {
                $q->where('npsn', $id)
                    ->orWhere('sekolah_id', $id);
            })
            ->with('detailSma')
            ->first();

        if (! $sekolah) {
            return response()->json([
                'status' => 'error',
                'message' => "Sekolah dengan ID atau NPSN '{$id}' tidak ditemukan.",
            ], 404);
        }

        // Cek apakah user boleh lihat sekolah ini
        if (! $this->userCanAccessSekolah($request, $sekolah)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke data sekolah ini.',
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $sekolah,
        ]);
    }

    /**
     * Update data sekolah.
     *
     * PUT/PATCH /api/v1/admin/sekolah/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $sekolah = Sekolah::latestSemester()
            ->where(function ($q) use ($id) {
                $q->where('npsn', $id)
                    ->orWhere('sekolah_id', $id);
            })
            ->first();

        if (! $sekolah) {
            return response()->json([
                'status' => 'error',
                'message' => "Sekolah dengan ID atau NPSN '{$id}' tidak ditemukan.",
            ], 404);
        }

        // Cek akses wilayah sebelum update
        if (! $this->userCanAccessSekolah($request, $sekolah)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengubah data sekolah ini.',
            ], 403);
        }

        $validated = $request->validate([
            'nama' => 'sometimes|string|max:150',
            // NPSN tidak boleh diubah saat edit (disabled di form)
            // 'npsn'                  => "sometimes|string|max:20|unique:sekolah,npsn,{$sekolah->sekolah_id},sekolah_id",
            'bentuk_pendidikan' => 'sometimes|string|max:20',
            'status_sekolah' => 'sometimes|in:Negeri,Swasta',
            'alamat_jalan' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'sometimes|string|max:100',
            'kode_kabupaten' => 'sometimes|string|max:30',
            'lintang' => 'nullable|numeric|between:-90,90',
            'bujur' => 'nullable|numeric|between:-180,180',
            'email' => 'nullable|email|max:100',
            'nomor_telepon' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:150',
            'akreditasi' => 'nullable|string|max:10',
            'jumlah_siswa' => 'nullable|integer|min:0',
            'daya_tampung' => 'nullable|integer|min:0',
            'is_3t' => 'boolean',
            'is_sekolah_alam' => 'boolean',
            'akses_internet' => 'nullable|string|max:50',
            'sumber_listrik' => 'nullable|string|max:50',
            'waktu_penyelenggaraan' => 'nullable|string|max:50',
        ]);

        $sekolah->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data sekolah berhasil diperbarui.',
            'data' => $sekolah->fresh('detailSma'),
        ]);
    }

    /**
     * Hapus sekolah dari database.
     *
     * DELETE /api/v1/admin/sekolah/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $sekolah = Sekolah::latestSemester()
            ->where(function ($q) use ($id) {
                $q->where('npsn', $id)
                    ->orWhere('sekolah_id', $id);
            })
            ->first();

        if (! $sekolah) {
            return response()->json([
                'status' => 'error',
                'message' => "Sekolah dengan ID atau NPSN '{$id}' tidak ditemukan.",
            ], 404);
        }

        // Cek akses wilayah sebelum hapus
        if (! $this->userCanAccessSekolah($request, $sekolah)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk menghapus data sekolah ini.',
            ], 403);
        }

        $namaSekolah = $sekolah->nama;
        $sekolah->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Sekolah '{$namaSekolah}' berhasil dihapus.",
        ]);
    }
}
