<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CekStrukturSekolahSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== CEK STRUKTUR TABEL SEKOLAH ===');
        $this->command->newLine();

        // Cek kolom tabel sekolah
        $columnsSekolah = DB::select('DESCRIBE sekolah');
        $this->command->info('📋 KOLOM TABEL "sekolah":');
        foreach ($columnsSekolah as $col) {
            $this->command->line("  - {$col->Field} ({$col->Type})");
        }
        $this->command->newLine();

        // Sample 3 data
        $this->command->info('🔍 SAMPLE 3 DATA (tabel sekolah):');
        $samples = DB::table('sekolah')->limit(3)->get();
        foreach ($samples as $index => $item) {
            $no = $index + 1;
            $data = json_decode(json_encode($item), true);
            $this->command->warn("Record #{$no}:");
            foreach ($data as $key => $value) {
                $val = $value ?: '(kosong)';
                if (strlen($val) > 50) $val = substr($val, 0, 50) . '...';
                $this->command->line("  {$key}: {$val}");
            }
            $this->command->newLine();
        }

        // Total
        $total = DB::table('sekolah')->count();
        $this->command->line("📊 Total records tabel 'sekolah': {$total}");
        $this->command->newLine();

        // Cek kolom school_sma juga
        $columnsSMA = DB::select('DESCRIBE school_sma');
        $this->command->info('📋 KOLOM TABEL "school_sma":');
        foreach ($columnsSMA as $col) {
            $this->command->line("  - {$col->Field} ({$col->Type})");
        }
        $this->command->newLine();

        $totalSMA = DB::table('school_sma')->count();
        $this->command->line("📊 Total records tabel 'school_sma': {$totalSMA}");
    }
}
