<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackboneSekolahSeeder extends Seeder
{
    /**
     * Import data backbone sekolah (PAUD–SMA semua jenjang) ke tabel sekolah.
     *
     * File yang dibutuhkan: database/seeders/backbone_sekolah.sql
     * File ini tidak di-commit ke Git — minta ke tim / download dari Google Drive.
     *
     * Cara run:
     *   php artisan db:seed --class=BackboneSekolahSeeder
     */
    public function run(): void
    {
        $file = 'backbone_sekolah.sql';
        $path = database_path('seeders/' . $file);

        if (!File::exists($path)) {
            $file = 'sekolah.sql';
            $path = database_path('seeders/' . $file);
        }

        if (!File::exists($path)) {
            $this->command->warn("⚠️  File 'backbone_sekolah.sql' atau 'sekolah.sql' tidak ditemukan di database/seeders/");
            $this->command->warn("   Download dari Google Drive/shared folder terlebih dahulu.");
            return;
        }

        $this->command->info("📥 Importing data dari: {$file}");

        $sql = File::get($path);

        // Hapus DROP TABLE, CREATE TABLE, ALTER TABLE agar tidak merusak struktur
        $sql = preg_replace('/DROP TABLE IF EXISTS `.*?`;/s', '', $sql);
        $sql = preg_replace('/CREATE TABLE `.*?` \(.*?\).*?;/s', '', $sql);
        $sql = preg_replace('/ALTER TABLE `.*?`.*?;/s', '', $sql);

        // Ambil INSERT dan INSERT IGNORE query
        preg_match_all('/INSERT\s+(?:IGNORE\s+)?INTO\s+`.*?`.*?VALUES.*?;/s', $sql, $matches);

        if (empty($matches[0])) {
            $this->command->error("❌ Tidak ada INSERT statement ditemukan di file SQL.");
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $inserted = 0;
        $errors   = 0;

        foreach ($matches[0] as $insertQuery) {
            try {
                // INSERT IGNORE agar data duplikat dilewati
                $insertQuery = preg_replace('/INSERT INTO/i', 'INSERT IGNORE INTO', $insertQuery);

                // Pastikan insert ke tabel sekolah
                $insertQuery = preg_replace('/INSERT IGNORE INTO `\w+`/i', 'INSERT IGNORE INTO `sekolah`', $insertQuery);

                DB::unprepared($insertQuery);
                $inserted++;
            } catch (\Exception $e) {
                $errors++;
                $this->command->error("Error: " . $e->getMessage());
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("🔄 Mengonversi kode_kabupaten format lama (18xxxx) ke BPS 4 digit (72xx)...");
        $mapping = [
            '180100' => '7207', // Kab. Banggai Kepulauan
            '180200' => '7203', // Kab. Donggala
            '180300' => '7202', // Kab. Poso
            '180400' => '7201', // Kab. Banggai
            '180500' => '7205', // Kab. Buol
            '180600' => '7204', // Kab. Tolitoli
            '180700' => '7206', // Kab. Morowali
            '180800' => '7208', // Kab. Parigi Moutong
            '180900' => '7209', // Kab. Tojo Una-Una
            '181000' => '7210', // Kab. Sigi
            '181100' => '7211', // Kab. Banggai Laut
            '181200' => '7212', // Kab. Morowali Utara
            '186000' => '7271', // Kota Palu
        ];

        foreach ($mapping as $lama => $bps) {
            DB::table('sekolah')
                ->where('kode_kabupaten', 'LIKE', $lama . '%')
                ->update(['kode_kabupaten' => $bps]);
        }

        // Flush cache agar data statistik ter-refresh
        \Illuminate\Support\Facades\Cache::flush();

        $total = DB::table('sekolah')->count();

        $this->command->info("✅ Import selesai!");
        $this->command->line("   Batch INSERT : {$inserted}");
        $this->command->line("   Error        : {$errors}");
        $this->command->line("   Total sekolah: {$total}");
    }
}
