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
            'sekolah_id'       => $this->sekolah_id ?? null,
            'semester_id'      => $this->semester_id ?? null,
            'nama'             => $this->nama,
            'npsn'             => $this->npsn ?? null,
            'bentuk_pendidikan' => $this->bentuk_pendidikan,
            'status_sekolah'   => $this->status_sekolah ?? 'Negeri',
            'alamat_jalan'     => $this->alamat_jalan ?? null,
            'kecamatan'        => $this->kecamatan ?? null,
            'kabupaten'        => $this->kabupaten ?? null,
            'kode_kabupaten'   => $this->kode_kabupaten ?? null,
            'kode_kecamatan'   => $this->kode_kecamatan ?? null,

            'lintang'          => $this->lintang ?? null,
            'bujur'            => $this->bujur ?? null,
            'is_3t'            => $this->is_3t ?? false,
            'is_sekolah_alam'  => $this->is_sekolah_alam ?? false,

            // Data kapasitas
            'jumlah_siswa'     => $this->jumlah_siswa ?? 0,
            'daya_tampung'     => $this->daya_tampung ?? 0,
        ];
    }
}
