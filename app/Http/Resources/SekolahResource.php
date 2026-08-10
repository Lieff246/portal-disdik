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
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sekolah_id'       => $this->sekolah_id,
            'semester_id'      => $this->semester_id,
            'nama'             => $this->nama,
            'npsn'             => $this->npsn,
            'bentuk_pendidikan' => $this->bentuk_pendidikan,
            'status_sekolah'   => $this->status_sekolah,
            'alamat_jalan'     => $this->alamat_jalan,
            'dusun'            => $this->dusun,
            'desa_kelurahan'   => $this->desa_kelurahan,
            'kode_kabupaten'   => $this->kode_kabupaten,
            'nama_kabupaten'   => $this->kabupaten, // Nama kabupaten sudah ada di kolom ini
            'kode_kecamatan'   => $this->kode_kecamatan,
            'kode_provinsi'    => $this->kode_provinsi,

            'lintang'          => $this->lintang,
            'bujur'            => $this->bujur,
            'is_3t'            => $this->is_3t,
            'is_sekolah_alam'  => $this->is_sekolah_alam,

            // Data kapasitas
            'jumlah_siswa'     => $this->jumlah_siswa,
            'daya_tampung'     => $this->daya_tampung,

            // Timestamps
            'create_date'      => $this->create_date?->toIso8601String(),
            'last_update'      => $this->last_update?->toIso8601String(),

            // Relasi lain jika di-load (misalnya detailSma)
            'detail_sma'       => $this->whenLoaded('detailSma'),
        ];
    }
}
