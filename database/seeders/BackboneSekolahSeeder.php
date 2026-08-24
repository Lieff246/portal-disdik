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
            $this->command->warn("⚠️  File '{$file}' tidak ditemukan di database/seeders/");
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

        $total = DB::table('sekolah')->count();

        $this->command->info("✅ Import selesai!");
        $this->command->line("   Batch INSERT : {$inserted}");
        $this->command->line("   Error        : {$errors}");
        $this->command->line("   Total sekolah: {$total}");
    }
}
