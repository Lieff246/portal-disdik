<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Konversi kode_kabupaten format lama (6 digit Kemendikbud)
     * → format BPS 4 digit yang dipakai frontend.
     *
     * Mapping:
     *   180100 → 7207  Banggai Kepulauan
     *   180200 → 7203  Donggala
     *   180300 → 7202  Poso
     *   180400 → 7201  Banggai
     *   180500 → 7205  Buol
     *   180600 → 7204  Tolitoli
     *   180700 → 7206  Morowali
     *   180800 → 7208  Parigi Moutong
     *   180900 → 7209  Tojo Una-Una
     *   181000 → 7210  Sigi
     *   181100 → 7211  Banggai Laut
     *   181200 → 7212  Morowali Utara
     *   186000 → 7271  Kota Palu
     */
    public function up(): void
    {
        $mapping = [
            '180100' => '7207',
            '180200' => '7203',
            '180300' => '7202',
            '180400' => '7201',
            '180500' => '7205',
            '180600' => '7204',
            '180700' => '7206',
            '180800' => '7208',
            '180900' => '7209',
            '181000' => '7210',
            '181100' => '7211',
            '181200' => '7212',
            '186000' => '7271',
        ];

        foreach ($mapping as $lama => $bps) {
            $updated = DB::table('sekolah')
                ->where('kode_kabupaten', $lama)
                ->update(['kode_kabupaten' => $bps]);
            echo "  {$lama} → {$bps} : {$updated} rows\n";
        }
    }

    public function down(): void
    {
        $mapping = [
            '7207' => '180100',
            '7203' => '180200',
            '7202' => '180300',
            '7201' => '180400',
            '7205' => '180500',
            '7204' => '180600',
            '7206' => '180700',
            '7208' => '180800',
            '7209' => '180900',
            '7210' => '181000',
            '7211' => '181100',
            '7212' => '181200',
            '7271' => '186000',
        ];

        foreach ($mapping as $bps => $lama) {
            DB::table('sekolah')
                ->where('kode_kabupaten', $bps)
                ->update(['kode_kabupaten' => $lama]);
        }
    }
};
