<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CekJenjangSekolahSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== CEK DATA JENJANG SEKOLAH ===');
        $this->command->newLine();

        // Total tabel sekolah
        $totalSekolah = DB::table('sekolah')->count();
        $this->command->line("📊 Total tabel 'sekolah': {$totalSekolah} records");
        $this->command->newLine();

        // Group by jenjang
        $jenjangSekolah = DB::table('sekolah')
            ->select('jenjang', DB::raw('count(*) as total'))
            ->groupBy('jenjang')
            ->orderBy('total', 'desc')
            ->get();

        if ($jenjangSekolah->count() > 0) {
            $this->command->info('📚 BREAKDOWN JENJANG (tabel sekolah):');
            foreach ($jenjangSekolah as $item) {
                $jenjang = $item->jenjang ?: '(Tanpa Jenjang)';
                $this->command->line("  - {$jenjang}: {$item->total} records");
            }
        } else {
            $this->command->warn('⚠️ Tidak ada data jenjang di tabel sekolah');
        }

        $this->command->newLine();

        // Total tabel school_sma
        $totalSMA = DB::table('school_sma')->count();
        $this->command->line("📊 Total tabel 'school_sma': {$totalSMA} records (SMA/SMK)");
        $this->command->newLine();

        // Sample 5 sekolah per jenjang
        $this->command->info('🔍 SAMPLE 5 SEKOLAH PER JENJANG:');
        $this->command->newLine();

        foreach ($jenjangSekolah as $item) {
            $jenjang = $item->jenjang ?: '';
            if (!$jenjang) continue;

            $this->command->warn("Jenjang: {$jenjang}");
            $samples = DB::table('sekolah')
                ->select('nama', 'kode_kabupaten', 'latitude', 'longitude', 'npsn')
                ->where('jenjang', $jenjang)
                ->limit(5)
                ->get();

            foreach ($samples as $index => $school) {
                $no = $index + 1;
                $npsn = $school->npsn ?: '(Tanpa NPSN)';
                $coord = $school->latitude ? "Ada koordinat" : "Tanpa koordinat";
                $kode = $school->kode_kabupaten ?: '(Tanpa kode)';
                $this->command->line("  {$no}. {$school->nama}");
                $this->command->line("     NPSN: {$npsn} | Kode Kab: {$kode} | {$coord}");
            }
            $this->command->newLine();
        }

        // Kesimpulan
        $this->command->info('=== KESIMPULAN ===');
        if ($jenjangSekolah->count() > 1) {
            $this->command->line('✅ Data PAUD-SMP sudah masuk di tabel sekolah!');
        } elseif ($jenjangSekolah->count() === 1 && $jenjangSekolah->first()->jenjang === 'SMA') {
            $this->command->warn('⚠️ Hanya ada data SMA, belum ada PAUD/TK/SD/SMP');
        } else {
            $this->command->error('❌ Format data tidak sesuai, cek manual');
        }
        $this->command->newLine();

        $this->command->line('Total gabungan (sekolah + school_sma): ' . ($totalSekolah + $totalSMA) . ' records');
    }
}
