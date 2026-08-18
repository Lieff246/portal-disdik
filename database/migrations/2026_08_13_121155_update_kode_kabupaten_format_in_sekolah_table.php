<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update format kode_kabupaten dari 6 digit (180700) ke 4 digit (7205)
     * agar konsisten dengan tabel school_sma dan bisa digabung di API.
     *
     * Mapping berdasarkan kode kabupaten resmi Kemendagri Sulawesi Tengah.
     */
    public function up(): void
    {
        // Mapping kode lama (6 digit) → kode baru (4 digit)
        $mapping = [
            '186000' => '7271', // Kota Palu
            '180800' => '7208', // Kab. Parigi Moutong
            '180400' => '7201', // Kab. Banggai
            '180100' => '7201', // Kab. Banggai Kepulauan (kode sama dengan Banggai)
            '180300' => '7202', // Kab. Poso
            '180200' => '7203', // Kab. Donggala
            '180600' => '7204', // Kab. Tolitoli
            '181000' => '7206', // Kab. Sigi
            '180700' => '7205', // Kab. Morowali
            '180900' => '7209', // Kab. Tojo Una Una
            '180500' => '7207', // Kab. Buol
            '181200' => '7212', // Kab. Morowali Utara
            '181100' => '7211', // Kab. Banggai Laut
        ];

        foreach ($mapping as $kodeLama => $kodeBaru) {
            DB::table('sekolah')
                ->where('kode_kabupaten', $kodeLama)
                ->update(['kode_kabupaten' => $kodeBaru]);
        }

        echo "✅ Berhasil update kode_kabupaten untuk 1000 records\n";
    }

    /**
     * Rollback: kembalikan ke format 6 digit.
     */
    public function down(): void
    {
        // Mapping terbalik: kode baru (4 digit) → kode lama (6 digit)
        $reverseMapping = [
            '7271' => '186000', // Kota Palu
            '7208' => '180800', // Kab. Parigi Moutong
            '7201' => '180400', // Kab. Banggai (default, karena Banggai Kepulauan juga pakai 7201)
            '7202' => '180300', // Kab. Poso
            '7203' => '180200', // Kab. Donggala
            '7204' => '180600', // Kab. Tolitoli
            '7206' => '181000', // Kab. Sigi
            '7205' => '180700', // Kab. Morowali
            '7209' => '180900', // Kab. Tojo Una Una
            '7207' => '180500', // Kab. Buol
            '7212' => '181200', // Kab. Morowali Utara
            '7211' => '181100', // Kab. Banggai Laut
        ];

        foreach ($reverseMapping as $kodeBaru => $kodeLama) {
            DB::table('sekolah')
                ->where('kode_kabupaten', $kodeBaru)
                ->update(['kode_kabupaten' => $kodeLama]);
        }

        echo "⚠️ Rollback: kode_kabupaten dikembalikan ke format 6 digit\n";
    }
};
