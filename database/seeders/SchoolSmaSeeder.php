<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSmaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Data ini berasal dari file: database sma.sql
     * 
     * Cara run:
     * php artisan db:seed --class=SchoolSmaSeeder
     */
    public function run(): void
    {
        // Path ke file SQL
        $sqlFile = database_path('seeders/database sma.sql');

        // Cek apakah file ada
        if (!file_exists($sqlFile)) {
            $this->command->warn("⚠️  File 'database sma.sql' tidak ditemukan di database/seeders/");
            $this->command->warn("   Download dari Google Drive/shared folder terlebih dahulu.");
            return;
        }

        $this->command->info("📥 Importing data dari: database sma.sql");

        // Baca file SQL
        $sql = file_get_contents($sqlFile);

        // Ambil hanya bagian INSERT (skip CREATE TABLE, ALTER TABLE, dll)
        preg_match('/INSERT INTO `school`.*?(?=ALTER TABLE|$)/s', $sql, $matches);
        
        if (empty($matches)) {
            $this->command->error("❌ Tidak ada INSERT statement ditemukan di file SQL");
            return;
        }

        $insertSQL = $matches[0];
        
        // Replace table name: school → school_sma
        $insertSQL = str_replace('INSERT INTO `school`', 'INSERT INTO `school_sma`', $insertSQL);

        // Truncate table dulu (bersihkan data lama)
        $this->command->info("🗑️  Truncating table school_sma...");
        DB::table('school_sma')->truncate();

        // Execute INSERT
        $this->command->info("💾 Inserting data...");
        DB::unprepared($insertSQL);

        // Update kode_kabupaten di school_sma
        $this->command->info("🔄 Mengupdate kode_kabupaten di school_sma...");
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

        foreach ($mappingKabupaten as $namaKabupaten => $kode) {
            DB::table('school_sma')
                ->where('city', 'LIKE', "%{$namaKabupaten}%")
                ->update(['kode_kabupaten' => $kode]);
        }

        // Count hasil
        $count = DB::table('school_sma')->count();
        $this->command->info("✅ Selesai! Total data: {$count} records");
    }
}
