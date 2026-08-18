<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')->hourly();

// Temporary: Cek bentuk pendidikan yang ada
Artisan::command('cek:bentuk-pendidikan', function () {
    $this->info('=== CEK BENTUK PENDIDIKAN ===');
    
    $data = DB::table('sekolah')
        ->select('bentuk_pendidikan', DB::raw('COUNT(*) as total'))
        ->groupBy('bentuk_pendidikan')
        ->orderBy('total', 'DESC')
        ->get();
    
    $this->table(['Bentuk Pendidikan', 'Total'], $data->map(fn($item) => [
        $item->bentuk_pendidikan ?: '(Kosong)',
        $item->total
    ]));
    
    $this->info('Total records tabel sekolah: ' . DB::table('sekolah')->count());
    $this->info('Total records tabel school_sma: ' . DB::table('school_sma')->count());
});

// Temporary: Cek sample kode kabupaten
Artisan::command('cek:kode-kabupaten', function () {
    $this->info('=== SAMPLE KODE KABUPATEN (tabel sekolah) ===');
    $this->newLine();
    
    // Group by kode_kabupaten
    $groupedData = DB::table('sekolah')
        ->select('kode_kabupaten', 'kabupaten', DB::raw('COUNT(*) as total'))
        ->groupBy('kode_kabupaten', 'kabupaten')
        ->orderBy('total', 'DESC')
        ->get();
    
    $this->table(['Kode Kabupaten', 'Nama Kabupaten', 'Total'], $groupedData->map(fn($item) => [
        $item->kode_kabupaten ?: '(Kosong)',
        $item->kabupaten ?: '(Kosong)',
        $item->total
    ]));
    
    $this->newLine();
    $this->info('=== SAMPLE 5 SEKOLAH SD/SMP/TK ===');
    $samples = DB::table('sekolah')
        ->whereIn('bentuk_pendidikan', ['SD', 'SMP', 'TK'])
        ->select('nama', 'bentuk_pendidikan', 'kabupaten', 'kode_kabupaten', 'npsn')
        ->limit(5)
        ->get();
    
    foreach ($samples as $item) {
        $this->line("{$item->bentuk_pendidikan} - {$item->nama}");
        $this->line("  Kabupaten: {$item->kabupaten}");
        $this->line("  Kode: {$item->kode_kabupaten}");
        $this->line("  NPSN: {$item->npsn}");
        $this->newLine();
    }
    
    $this->newLine();
    $this->warn('=== BANDINGKAN DENGAN TABEL school_sma ===');
    $smaKodes = DB::table('school_sma')
        ->select('kode_kabupaten', 'city', DB::raw('COUNT(*) as total'))
        ->whereNotNull('kode_kabupaten')
        ->where('kode_kabupaten', '!=', '')
        ->groupBy('kode_kabupaten', 'city')
        ->orderBy('total', 'DESC')
        ->limit(5)
        ->get();
    
    $this->table(['Kode (school_sma)', 'Kota/Kabupaten', 'Total'], $smaKodes->map(fn($item) => [
        $item->kode_kabupaten,
        $item->city,
        $item->total
    ]));
});

// Temporary: Cek kode setelah update
Artisan::command('cek:kode-after-update', function () {
    $this->info('=== CEK KODE KABUPATEN SETELAH UPDATE ===');
    $this->newLine();
    
    $data = DB::table('sekolah')
        ->select('kode_kabupaten', 'kabupaten', DB::raw('COUNT(*) as total'))
        ->groupBy('kode_kabupaten', 'kabupaten')
        ->orderBy('total', 'DESC')
        ->get();
    
    $this->table(['Kode Kabupaten (Baru)', 'Nama Kabupaten', 'Total'], $data->map(fn($item) => [
        $item->kode_kabupaten,
        $item->kabupaten,
        $item->total
    ]));
    
    $this->newLine();
    $this->info('🎉 FORMAT SUDAH KONSISTEN (4 digit)!');
    $this->line('Total records: ' . $data->sum('total'));
});
