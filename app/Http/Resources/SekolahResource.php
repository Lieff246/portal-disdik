<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk model Sekolah.
 *
 * Mengembalikan data yang sudah diformat dengan relasi yang di-eager load.
 */
class SekolahResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Identitas ────────────────────────────────────────────────
            'sekolah_id'            => $this->sekolah_id ?? null,
            'semester_id'           => $this->semester_id ?? null,
            'nama'                  => $this->nama,
            'nama_nomenklatur'      => $this->nama_nomenklatur ?? null,
            'npsn'                  => $this->npsn ?? null,
            'nss'                   => $this->nss ?? null,
            'bentuk_pendidikan'     => $this->bentuk_pendidikan,
            'bentuk_pendidikan_id'  => $this->bentuk_pendidikan_id ?? null,
            'status_sekolah'        => $this->status_sekolah ?? null,
            'status_kepemilikan'    => $this->status_kepemilikan ?? null,
            'yayasan'               => $this->yayasan ?? null,
            'akreditasi'            => $this->akreditasi ?? null,
            'keaktifan'             => $this->keaktifan ?? null,

            // ── Alamat ───────────────────────────────────────────────────
            'alamat_jalan'          => $this->alamat_jalan ?? null,
            'rt'                    => $this->rt ?? null,
            'rw'                    => $this->rw ?? null,
            'nama_dusun'            => $this->nama_dusun ?? null,
            'desa_kelurahan'        => $this->desa_kelurahan ?? null,
            'kecamatan'             => $this->kecamatan ?? null,
            'kode_kecamatan'        => $this->kode_kecamatan ?? null,
            'kabupaten'             => $this->kabupaten ?? null,
            'kode_kabupaten'        => $this->kode_kabupaten ?? null,
            'provinsi'              => $this->provinsi ?? null,
            'kode_pos'              => $this->kode_pos ?? null,

            // ── Koordinat ────────────────────────────────────────────────
            'lintang'               => $this->lintang ?? null,
            'bujur'                 => $this->bujur ?? null,

            // ── Kontak ───────────────────────────────────────────────────
            'nomor_telepon'         => $this->nomor_telepon ?? null,
            'email'                 => $this->email ?? null,
            'website'               => $this->website ?? null,

            // ── Fasilitas ────────────────────────────────────────────────
            'sumber_listrik'        => $this->sumber_listrik ?? null,
            'daya_listrik'          => $this->daya_listrik ?? null,
            'akses_internet'        => $this->akses_internet ?? null,
            'akses_internet_2'      => $this->akses_internet_2 ?? null,
            'waktu_penyelenggaraan' => $this->waktu_penyelenggaraan ?? null,
            'sertifikasi_iso'       => $this->sertifikasi_iso ?? null,

            // ── Lahan ────────────────────────────────────────────────────
            'luas_tanah_milik'      => $this->luas_tanah_milik ?? null,
            'luas_tanah_bukan_milik'=> $this->luas_tanah_bukan_milik ?? null,
            'mbs'                   => $this->mbs ?? null,

            // ── Wilayah Khusus ───────────────────────────────────────────
            'is_3t'                 => $this->is_3t ?? false,
            'is_sekolah_alam'       => $this->is_sekolah_alam ?? false,
            'wilayah_terpencil'     => $this->wilayah_terpencil ?? null,
            'wilayah_perbatasan'    => $this->wilayah_perbatasan ?? null,
            'partisipasi_bos'       => $this->partisipasi_bos ?? null,

            // ── Kapasitas ────────────────────────────────────────────────
            'jumlah_siswa'          => $this->jumlah_siswa ?? 0,
            'daya_tampung'          => $this->daya_tampung ?? 0,

            // ── SK & Legalitas ───────────────────────────────────────────
            'sk_pendirian_sekolah'       => $this->sk_pendirian_sekolah ?? null,
            'tanggal_sk_pendirian'       => $this->tanggal_sk_pendirian ?? null,
            'sk_izin_operasional'        => $this->sk_izin_operasional ?? null,
            'tanggal_sk_izin_operasional'=> $this->tanggal_sk_izin_operasional ?? null,

            // ── Relasi school_sma (kepsek, polygon, dll) ─────────────────
            // Hanya tersedia di endpoint show() — tidak di index()
            'detailSma'             => $this->when(
                $this->resource instanceof \App\Models\Sekolah && $this->relationLoaded('detailSma'),
                function () {
                    $sma = $this->detailSma;
                    if (! $sma) return null;
                    return [
                        'id'             => $sma->id,
                        'name'           => $sma->name,
                        'grade'          => $sma->grade,
                        'status'         => $sma->status,
                        'kepsek'         => $sma->kepsek,
                        'nip_kepsek'     => $sma->nip_kepsek,
                        'no_hp_kepsek'   => $sma->no_hp_kepsek,
                        'status_kepsek'  => $sma->status_kepsek,
                        'address'        => $sma->address,
                        'latitude'       => $sma->latitude,
                        'longitude'      => $sma->longitude,
                        'polygon'        => $sma->polygon,
                    ];
                }
            ),
        ];
    }
}
