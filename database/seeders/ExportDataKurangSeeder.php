<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExportDataKurangSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== EXPORT LIST SEKOLAH DENGAN DATA KURANG ===');
        $this->command->newLine();

        // 1. Sekolah tanpa NPSN
        $this->command->error('🔴 SEKOLAH TANPA NPSN (12 records):');
        $this->command->line('Button "Klik Detail" akan error 404 untuk sekolah ini');
        $this->command->newLine();

        $noNpsn = DB::table('school_sma')
            ->select('id', 'name', 'city', 'address', 'latitude', 'longitude', 'npsn', 'kode_kabupaten')
            ->where(function($q) {
                $q->whereNull('npsn')->orWhere('npsn', '');
            })
            ->orderBy('city')
            ->get();

        foreach ($noNpsn as $index => $item) {
            $no = $index + 1;
            $coord = $item->latitude ? "Ada koordinat ✅" : "Tanpa koordinat ❌";
            $this->command->line("{$no}. {$item->name}");
            $this->command->line("   - Kabupaten/Kota: {$item->city}");
            $this->command->line("   - Alamat: " . ($item->address ?: '-'));
            $this->command->line("   - NPSN: (KOSONG) ❌");
            $this->command->line("   - Koordinat: {$coord}");
            $this->command->line("   - Kode Kabupaten: " . ($item->kode_kabupaten ?: '(KOSONG)'));
            $this->command->newLine();
        }

        // 2. Sekolah tanpa Koordinat (tapi punya NPSN)
        $this->command->warn('🟡 SEKOLAH DENGAN NPSN TAPI TANPA KOORDINAT:');
        $this->command->line('Marker tidak muncul di peta untuk sekolah ini');
        $this->command->newLine();

        $noCoord = DB::table('school_sma')
            ->select('id', 'name', 'npsn', 'city', 'address', 'latitude', 'longitude', 'kode_kabupaten')
            ->whereNotNull('npsn')
            ->where('npsn', '!=', '')
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhere('latitude', '')
                  ->orWhereNull('longitude')
                  ->orWhere('longitude', '');
            })
            ->orderBy('city')
            ->get();

        if ($noCoord->count() > 0) {
            foreach ($noCoord as $index => $item) {
                $no = $index + 1;
                $this->command->line("{$no}. {$item->name}");
                $this->command->line("   - NPSN: {$item->npsn} ✅");
                $this->command->line("   - Kabupaten/Kota: {$item->city}");
                $this->command->line("   - Alamat: " . ($item->address ?: '-'));
                $this->command->line("   - Koordinat: (KOSONG) ❌");
                $this->command->line("   - Kode Kabupaten: " . ($item->kode_kabupaten ?: '(KOSONG)'));
                $this->command->newLine();
            }
        } else {
            $this->command->info('✅ Semua sekolah dengan NPSN sudah punya koordinat!');
            $this->command->newLine();
        }

        // 3. Sekolah tanpa Kode Kabupaten
        $this->command->warn('🟡 SEKOLAH TANPA KODE KABUPATEN:');
        $this->command->line('Tidak muncul di filter kabupaten tertentu');
        $this->command->newLine();

        $noKode = DB::table('school_sma')
            ->select('id', 'name', 'npsn', 'city', 'latitude', 'longitude', 'kode_kabupaten')
            ->where(function($q) {
                $q->whereNull('kode_kabupaten')->orWhere('kode_kabupaten', '');
            })
            ->orderBy('city')
            ->get();

        if ($noKode->count() > 0) {
            foreach ($noKode as $index => $item) {
                $no = $index + 1;
                $npsn = $item->npsn ?: '(KOSONG)';
                $coord = $item->latitude ? "Ada" : "Tanpa";
                $this->command->line("{$no}. {$item->name} ({$item->city})");
                $this->command->line("   - NPSN: {$npsn} | Koordinat: {$coord}");
                $this->command->newLine();
            }
        } else {
            $this->command->info('✅ Semua sekolah sudah punya kode kabupaten!');
            $this->command->newLine();
        }

        // Summary
        $this->command->info('=== SUMMARY ===');
        $this->command->line("Total sekolah tanpa NPSN: {$noNpsn->count()} records");
        $this->command->line("Total sekolah tanpa koordinat (punya NPSN): {$noCoord->count()} records");
        $this->command->line("Total sekolah tanpa kode kabupaten: {$noKode->count()} records");
        $this->command->newLine();

        $this->command->warn('⚠️ DATA YANG PERLU DIMINTA KE TIM/DINAS:');
        $this->command->line('1. NPSN untuk ' . $noNpsn->count() . ' sekolah (kebanyakan sekolah teologi)');
        $this->command->line('2. Koordinat latitude/longitude untuk ' . ($noNpsn->count() + $noCoord->count()) . ' sekolah');
        $this->command->line('3. Kode kabupaten untuk ' . $noKode->count() . ' sekolah');
    }
}
