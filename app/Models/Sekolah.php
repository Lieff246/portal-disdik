<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sekolah extends Model
{
    protected $table = 'sekolah';
    protected $primaryKey = ['sekolah_id', 'semester_id'];
    public $incrementing = false;
    protected $guarded = [];

    /**
     * Cast kolom agar tipe data dikembalikan dengan benar ke frontend.
     * Tanpa ini, boolean is_3t bisa kembali sebagai "0"/"1" string.
     */
    protected function casts(): array
    {
        return [
            'is_3t'            => 'boolean',
            'is_sekolah_alam'  => 'boolean',
            'lintang'          => 'float',
            'bujur'            => 'float',
            'jumlah_siswa'     => 'integer',
            'daya_tampung'     => 'integer',
            'daya_listrik'     => 'integer',
            'luas_tanah_milik'       => 'integer',
            'luas_tanah_bukan_milik' => 'integer',
            'tanggal_sk_pendirian'        => 'date',
            'tanggal_sk_izin_operasional' => 'date',
            'create_date' => 'datetime',
            'last_update' => 'datetime',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByJenjang($query, string $jenjang)
    {
        return $query->where('bentuk_pendidikan', $jenjang);
    }

    public function scopeByKabupaten($query, string $kodeKab)
    {
        return $query->where('kode_kabupaten', $kodeKab);
    }

    public function scopeWilayah3T($query)
    {
        return $query->where(function ($q) {
            $q->where('is_3t', true)
              ->orWhere('wilayah_terpencil', '1')
              ->orWhere('wilayah_perbatasan', '1')
              ->orWhere('wilayah_transmigrasi', '1');
        });
    }

    /**
     * Scope untuk mengambil hanya data semester terbaru.
     * Contoh: Sekolah::latestSemester()->get()
     *
     * semester_id di-cache selama 10 menit agar tidak query MAX()
     * ke DB setiap kali scope ini dipanggil (landing() memanggil ~9x).
     * Cache otomatis batal jika ada data import baru via artisan/seeder.
     */
    public function scopeLatestSemester($query)
    {
        $latest = \Cache::remember('sekolah_latest_semester_id', 600, function () {
            return static::max('semester_id');
        });
        return $query->where('semester_id', $latest);
    }

    /**
     * Batal-kan cache semester_id — panggil setelah import/seeder data baru.
     * Contoh: Sekolah::clearSemesterCache();
     */
    public static function clearSemesterCache(): void
    {
        \Cache::forget('sekolah_latest_semester_id');
    }

    // ── Relasi ────────────────────────────────────────────────────────────────

    /**
     * Relasi ke tabel school_sma — data detail SMA/SMK/SLB
     * (kepala sekolah, polygon gedung, koordinat akurat, dll).
     * Join via npsn (tanpa FK constraint di database).
     */
    public function detailSma()
    {
        return $this->hasOne(SchoolSma::class, 'npsn', 'npsn');
    }
}
