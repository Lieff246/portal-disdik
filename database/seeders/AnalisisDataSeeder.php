<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalisisDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== ANALISIS DATA SEKOLAH ===');
        $this->command->newLine();

        // Total data
        $totalSekolah = DB::table('sekolah')->count();
        $totalSchoolSma = DB::table('school_sma')->count();
        $this->command->info("📊 TOTAL DATA:");
        $this->command->line("- Tabel sekolah: {$totalSekolah} records");
        $this->command->line("- Tabel school_sma: {$totalSchoolSma} records");
        $this->command->newLine();

        // Koordinat
        $sekolahWithCoord = DB::table('sekolah')
            ->whereNotNull('lintang')->whereNotNull('bujur')
            ->where('lintang', '!=', 0)->where('bujur', '!=', 0)
            ->count();
        
        $schoolSmaWithCoord = DB::table('school_sma')
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->where('latitude', '!=', '')->where('longitude', '!=', '')
            ->count();

        $this->command->info("📍 DATA KOORDINAT (untuk marker peta):");
        $this->command->line("- sekolah dengan koordinat: {$sekolahWithCoord} records");
        $this->command->line("- school_sma dengan koordinat: {$schoolSmaWithCoord} records");
        $this->command->warn("- school_sma TANPA koordinat: " . ($totalSchoolSma - $schoolSmaWithCoord) . " records ❌");
        $this->command->newLine();

        // NPSN
        $sekolahWithNpsn = DB::table('sekolah')->whereNotNull('npsn')->count();
        $schoolSmaWithNpsn = DB::table('school_sma')
            ->whereNotNull('npsn')->where('npsn', '!=', '')
            ->count();
        $schoolSmaNoNpsn = DB::table('school_sma')
            ->where(function($q) {
                $q->whereNull('npsn')->orWhere('npsn', '');
            })->count();

        $this->command->info("🔑 DATA NPSN (untuk link detail sekolah):");
        $this->command->line("- sekolah dengan NPSN: {$sekolahWithNpsn} records");
        $this->command->line("- school_sma dengan NPSN: {$schoolSmaWithNpsn} records");
        $this->command->error("- school_sma TANPA NPSN: {$schoolSmaNoNpsn} records ❌");
        $this->command->newLine();

        // Kode Kabupaten
        $sekolahWithKode = DB::table('sekolah')->whereNotNull('kode_kabupaten')->count();
        $schoolSmaWithKode = DB::table('school_sma')->whereNotNull('kode_kabupaten')->count();

        $this->command->info("🏫 DATA KODE KABUPATEN (untuk filter):");
        $this->command->line("- sekolah dengan kode: {$sekolahWithKode} records");
        $this->command->line("- school_sma dengan kode: {$schoolSmaWithKode} records ✅");
        $this->command->newLine();

        // Sample data kosong
        $this->command->warn("📋 SAMPLE 5 SEKOLAH TANPA NPSN:");
        $sampleNoNpsn = DB::table('school_sma')
            ->select('id', 'name', 'npsn', 'latitude', 'longitude', 'kode_kabupaten', 'city')
            ->where(function($q) {
                $q->whereNull('npsn')->orWhere('npsn', '');
            })
            ->limit(5)->get();

        foreach ($sampleNoNpsn as $item) {
            $coord = $item->latitude ? "✅ Ada koordinat" : "❌ Tanpa koordinat";
            $this->command->line("  - {$item->name} ({$item->city}) - {$coord}");
        }
        $this->command->newLine();

        // Sample data tanpa koordinat
        $this->command->warn("📋 SAMPLE 5 SEKOLAH TANPA KOORDINAT:");
        $sampleNoCoord = DB::table('school_sma')
            ->select('id', 'name', 'npsn', 'latitude', 'longitude', 'city')
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhere('latitude', '');
            })
            ->limit(5)->get();

        foreach ($sampleNoCoord as $item) {
            $npsn = $item->npsn ?: '(Tanpa NPSN)';
            $this->command->line("  - {$item->name} - NPSN: {$npsn} ({$item->city})");
        }
    }
}
