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

        @ini_set('memory_limit', '1024M');
        @ini_set('pcre.backtrack_limit', '100000000');

        $this->command->info("📥 Importing data dari: {$file}");

        $sql = File::get($path);

        // Hapus DROP TABLE, CREATE TABLE, ALTER TABLE agar tidak merusak struktur
        $sql = preg_replace('/DROP TABLE IF EXISTS `.*?`;/si', '', $sql);
        $sql = preg_replace('/CREATE TABLE `.*?` \(.*?\).*?;/si', '', $sql);
        $sql = preg_replace('/ALTER TABLE `.*?`.*?;/si', '', $sql);

        // Ekstrak semua INSERT statement
        preg_match_all('/INSERT\s+(?:IGNORE\s+)?INTO\s+`?(\w+)`?.*?VALUES\s*\(.*?\);/si', $sql, $matches);

        // Fallback jika regex preg_match_all terkena limit backtrack: split manual
        $statements = $matches[0] ?? [];
        if (empty($statements)) {
            $rawChunks = preg_split('/;\s*[\r\n]+/', $sql);
            foreach ($rawChunks as $chunk) {
                $trimmed = trim($chunk);
                if (stripos($trimmed, 'INSERT') === 0) {
                    $statements[] = $trimmed . ';';
                }
            }
        }

        if (empty($statements)) {
            $this->command->error("❌ Tidak ada INSERT statement ditemukan di file SQL.");
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $inserted = 0;
        $errors   = 0;

        foreach ($statements as $insertQuery) {
            try {
                // INSERT IGNORE agar data duplikat dilewati
                $insertQuery = preg_replace('/INSERT INTO/i', 'INSERT IGNORE INTO', $insertQuery);

                // Pastikan insert ke tabel sekolah
                $insertQuery = preg_replace('/INSERT IGNORE INTO `?\w+`?/i', 'INSERT IGNORE INTO `sekolah`', $insertQuery);

                DB::unprepared($insertQuery);
                $inserted++;
            } catch (\Exception $e) {
                $errors++;
                $this->command->error("Error batch: " . $e->getMessage());
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

        // Cleanup data anomali / sisa kode yang bukan 4 digit angka
        $namaToBps = [
            'Kota Palu' => '7271',
            'Sigi' => '7210',
            'Donggala' => '7203',
            'Parigi' => '7208',
            'Poso' => '7202',
            'Tojo' => '7209',
            'Morowali Utara' => '7212',
            'Morowali' => '7206',
            'Banggai Laut' => '7211',
            'Banggai Kepulauan' => '7207',
            'Banggai' => '7201',
            'Tolitoli' => '7204',
            'Buol' => '7205',
        ];
        foreach ($namaToBps as $nama => $bps) {
            DB::table('sekolah')
                ->where('kabupaten', 'LIKE', "%{$nama}%")
                ->where(function ($q) {
                    $q->where('kode_kabupaten', 'NOT REGEXP', '^[0-9]{4}$')
                      ->orWhere('kode_kabupaten', 'kode_kecamatan')
                      ->orWhereNull('kode_kabupaten');
                })
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
