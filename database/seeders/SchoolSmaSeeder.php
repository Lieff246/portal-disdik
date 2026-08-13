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

        // Count hasil
        $count = DB::table('school_sma')->count();
        $this->command->info("✅ Selesai! Total data: {$count} records");
    }
}
