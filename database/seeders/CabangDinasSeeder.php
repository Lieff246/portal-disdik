<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CabangDinas;

class CabangDinasSeeder extends Seeder
{
    public function run(): void
    {
        $cabangDinas = [
            ['nama' => 'Wilayah I - Palu & Sigi', 'kabupaten_kota' => ['Kota Palu', 'Kab. Sigi'], 'map_lat' => -0.8917, 'map_lng' => 119.8707, 'map_zoom' => 9, 'kode_kabupaten' => ['7271', '7210']],
            ['nama' => 'Wilayah II - Donggala & Parimo', 'kabupaten_kota' => ['Kab. Donggala', 'Kab. Parigi Moutong'], 'map_lat' => -0.0619, 'map_lng' => 119.9877, 'map_zoom' => 8, 'kode_kabupaten' => ['7203', '7208']],
            ['nama' => 'Wilayah III - Poso & Ampana', 'kabupaten_kota' => ['Kab. Poso', 'Kab. Tojo Una-Una'], 'map_lat' => -1.3989, 'map_lng' => 120.7513, 'map_zoom' => 8, 'kode_kabupaten' => ['7202', '7209']],
            ['nama' => 'Wilayah IV - Morowali', 'kabupaten_kota' => ['Kab. Morowali', 'Kab. Morowali Utara'], 'map_lat' => -2.2618, 'map_lng' => 121.5794, 'map_zoom' => 8, 'kode_kabupaten' => ['7206', '7212']],
            ['nama' => 'Wilayah V - Banggai', 'kabupaten_kota' => ['Kab. Banggai', 'Kab. Banggai Kepulauan', 'Kab. Banggai Laut'], 'map_lat' => -1.1396, 'map_lng' => 122.9830, 'map_zoom' => 8, 'kode_kabupaten' => ['7201', '7207', '7211']],
            ['nama' => 'Wilayah VI - Tolitoli & Buol', 'kabupaten_kota' => ['Kab. Tolitoli', 'Kab. Buol'], 'map_lat' => 1.0592, 'map_lng' => 121.2183, 'map_zoom' => 8, 'kode_kabupaten' => ['7204', '7205']],
        ];

        foreach ($cabangDinas as $wilayah) {
            CabangDinas::firstOrCreate(['nama' => $wilayah['nama']], $wilayah);
        }
    }
}
