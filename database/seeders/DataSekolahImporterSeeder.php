<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DataSekolahImporterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai import data sekolah mentah dari SQL...');

        $files = [
            'sekolah.sql' => 'sekolah',
            'database sma.sql' => 'school_sma',
        ];

        foreach ($files as $file => $table) {
            $path = database_path('seeders/' . $file);
            if (File::exists($path)) {
                $sql = File::get($path);
                
                // Hapus query DROP TABLE dan CREATE TABLE agar tidak merusak struktur baru
                $sql = preg_replace('/DROP TABLE IF EXISTS `.*?`;/s', '', $sql);
                $sql = preg_replace('/CREATE TABLE `.*?` \(.*?\).*?;/s', '', $sql);
                $sql = preg_replace('/ALTER TABLE `.*?`.*?;/s', '', $sql);
                
                // Jalankan hanya query INSERT
                preg_match_all('/INSERT INTO `.*?`.*?VALUES.*?;/s', $sql, $matches);
                if (isset($matches[0]) && count($matches[0]) > 0) {
                    foreach ($matches[0] as $insertQuery) {
                        try {
                            // Nonaktifkan foreign key checks untuk aman
                            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                            // Ganti INSERT INTO dengan INSERT IGNORE INTO agar data duplikat dilewati
                            $insertQuery = preg_replace('/INSERT INTO/i', 'INSERT IGNORE INTO', $insertQuery);
                            // Rename tabel 'school' ke 'school_sma' sesuai nama tabel di database
                            if ($table === 'school_sma') {
                                $insertQuery = preg_replace('/INSERT IGNORE INTO `school`/i', 'INSERT IGNORE INTO `school_sma`', $insertQuery);
                            }
                            DB::unprepared($insertQuery);
                            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                        } catch (\Exception $e) {
                            $this->command->error("Error importing a row in $table: " . $e->getMessage());
                        }
                    }
                    $this->command->info("Data untuk tabel $table berhasil diimport!");
                } else {
                    $this->command->warn("Tidak ditemukan INSERT query untuk $file.");
                }
            } else {
                $this->command->warn("File $file tidak ditemukan di folder database/seeders.");
            }
        }
    }
}
