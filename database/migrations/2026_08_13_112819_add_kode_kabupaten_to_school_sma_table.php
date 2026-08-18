<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom kode_kabupaten dan kode_kecamatan
        Schema::table('school_sma', function (Blueprint $table) {
            $table->string('kode_kabupaten', 10)->nullable()->after('city');
            $table->string('kode_kecamatan', 10)->nullable()->after('kecamatan');
        });

        // 2. Mapping nama kabupaten → kode BPS (7 digit)
        $mappingKabupaten = [
            'Kab. Poso' => '7202',
            'Kab. Donggala' => '7203',
            'Kab. Tolitoli' => '7204',
            'Kab. Buol' => '7205',
            'Kab. Morowali' => '7206',
            'Kab. Banggai Kepulauan' => '7207',
            'Kab. Parigi Moutong' => '7208',
            'Kab. Tojo Una-Una' => '7209',
            'Kab. Sigi' => '7210',
            'Kab. Banggai Laut' => '7211',
            'Kab. Morowali Utara' => '7212',
            'Kota Palu' => '7271',
            'Kab. Banggai' => '7201',
        ];

        // 3. Update kode_kabupaten berdasarkan nama
        foreach ($mappingKabupaten as $namaKabupaten => $kode) {
            DB::table('school_sma')
                ->where('city', 'LIKE', "%{$namaKabupaten}%")
                ->update(['kode_kabupaten' => $kode]);
        }

        echo "✅ Kode kabupaten berhasil ditambahkan ke school_sma\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_sma', function (Blueprint $table) {
            $table->dropColumn(['kode_kabupaten', 'kode_kecamatan']);
        });
    }
};
