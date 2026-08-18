<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BackboneSekolahSeeder extends Seeder
{
    /**
     * Import data backbone sekolah (11,642 records) dari Excel ke tabel sekolah.
     * 
     * Strategy:
     * 1. Baca Excel file (bukan SQL)
     * 2. Mapping kolom Excel ke struktur tabel sekolah
     * 3. Cleaning & validasi data (koordinat, NPSN, kode kabupaten)
     * 4. Chunk insert 500 rows per batch
     */
    public function run(): void
    {
        $excelPath = database_path('seeders/backbone_sekolah.xlsx');

        if (!file_exists($excelPath)) {
            $this->command->error("❌ File tidak ditemukan: {$excelPath}");
            return;
        }

        $this->command->info("📂 Loading Excel file...");
        
        try {
            $spreadsheet = IOFactory::load($excelPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Header di baris pertama
            $headers = array_shift($rows);
            $totalRows = count($rows);
            
            $this->command->info("📊 Total baris: {$totalRows}");
            $this->command->info("⏳ Memulai import...");

            DB::beginTransaction();

            // Truncate tabel sekolah
            $this->command->info("🗑️  Truncate tabel sekolah...");
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('sekolah')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Process data dalam chunk
            $chunkSize = 500;
            $inserted = 0;
            $skipped = 0;
            $chunks = array_chunk($rows, $chunkSize);

            foreach ($chunks as $chunkIndex => $chunk) {
                $dataToInsert = [];

                foreach ($chunk as $rowIndex => $row) {
                    // Map kolom Excel ke array associative
                    $rowData = array_combine($headers, $row);
                    
                    // Skip jika tidak ada sekolah_id atau semester_id
                    if (empty($rowData['sekolah_id']) || empty($rowData['semester_id'])) {
                        $skipped++;
                        continue;
                    }

                    // Clean & transform data
                    $cleaned = $this->cleanData($rowData);
                    
                    if ($cleaned) {
                        $dataToInsert[] = $cleaned;
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                }

                // Batch insert
                if (!empty($dataToInsert)) {
                    DB::table('sekolah')->insert($dataToInsert);
                }

                // Progress
                $progress = ($chunkIndex + 1) * $chunkSize;
                $percent = min(100, round(($progress / $totalRows) * 100, 1));
                $this->command->info("   Progress: {$percent}% ({$progress}/{$totalRows})");
            }

            DB::commit();

            // Summary
            $totalSekolah = DB::table('sekolah')->count();
            $sekolahDenganNPSN = DB::table('sekolah')->whereNotNull('npsn')->where('npsn', '!=', '')->count();
            $sekolahDenganKoordinat = DB::table('sekolah')
                ->whereNotNull('lintang')
                ->whereNotNull('bujur')
                ->where('lintang', '!=', 0)
                ->where('bujur', '!=', 0)
                ->count();

            $this->command->info("✅ Import selesai!");
            $this->command->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->command->info("📊 SUMMARY:");
            $this->command->info("   Total sekolah      : {$totalSekolah}");
            $this->command->info("   Inserted           : {$inserted}");
            $this->command->info("   Skipped            : {$skipped}");
            $this->command->info("   Dengan NPSN        : {$sekolahDenganNPSN}");
            $this->command->info("   Dengan koordinat   : {$sekolahDenganKoordinat}");
            $this->command->info("   Tanpa NPSN         : " . ($totalSekolah - $sekolahDenganNPSN));
            $this->command->info("   Tanpa koordinat    : " . ($totalSekolah - $sekolahDenganKoordinat));
            $this->command->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Error: " . $e->getMessage());
            $this->command->error("File: " . $e->getFile() . " Line: " . $e->getLine());
        }
    }

    /**
     * Clean dan transform data dari Excel ke format database
     */
    private function cleanData(array $row): ?array
    {
        // Convert koordinat dari format aneh (-1461200000000) ke decimal proper
        $lintang = $this->parseKoordinat($row['lintang'] ?? null);
        $bujur = $this->parseKoordinat($row['bujur'] ?? null);

        // Parse tanggal
        $tanggalSkPendirian = $this->parseDate($row['tanggal_sk_pendirian'] ?? null);
        $tanggalSkIzin = $this->parseDate($row['tanggal_sk_izin_operasional'] ?? null);
        $createDate = $this->parseDate($row['create_date'] ?? null);
        $lastUpdate = $this->parseDate($row['last_update'] ?? null);

        return [
            // Primary keys
            'sekolah_id' => $row['sekolah_id'],
            'semester_id' => $row['semester_id'],

            // Identitas
            'nama' => $this->cleanString($row['nama'] ?? null),
            'nama_nomenklatur' => $this->cleanString($row['nama_nomenklatur'] ?? null),
            'nss' => $this->cleanString($row['nss'] ?? null),
            'npsn' => $this->cleanString($row['npsn'] ?? null),
            'bentuk_pendidikan_id' => $this->cleanInt($row['bentuk_pendidikan_id'] ?? null),
            'bentuk_pendidikan' => $this->cleanString($row['bentuk_pendidikan'] ?? null),

            // Alamat
            'alamat_jalan' => $this->cleanString($row['alamat_jalan'] ?? null),
            'rt' => $this->cleanString($row['rt'] ?? null),
            'rw' => $this->cleanString($row['rw'] ?? null),
            'nama_dusun' => $this->cleanString($row['nama_dusun'] ?? null),
            'kode_wilayah' => $this->cleanString($row['kode_wilayah'] ?? null),
            'kode_desa_kelurahan' => $this->cleanString($row['kode_desa_kelurahan'] ?? null),
            'desa_kelurahan' => $this->cleanString($row['desa_kelurahan'] ?? null),
            'kode_kecamatan' => $this->cleanString($row['kode_kecamatan'] ?? null),
            'kecamatan' => $this->cleanString($row['kecamatan'] ?? null),
            'kode_kabupaten' => $this->cleanString($row['kode_kabupaten'] ?? null),
            'kabupaten' => $this->cleanString($row['kabupaten'] ?? null),
            'kode_provinsi' => $this->cleanString($row['kode_provinsi'] ?? null),
            'provinsi' => $this->cleanString($row['provinsi'] ?? null),
            'kode_pos' => $this->cleanString($row['kode_pos'] ?? null),

            // Koordinat
            'lintang' => $lintang,
            'bujur' => $bujur,

            // Kontak
            'nomor_telepon' => $this->cleanString($row['nomor_telepon'] ?? null),
            'nomor_fax' => $this->cleanString($row['nomor_fax'] ?? null),
            'email' => $this->cleanString($row['email'] ?? null),
            'website' => $this->cleanString($row['website'] ?? null),

            // Kebutuhan Khusus
            'kebutuhan_khusus_id' => $this->cleanInt($row['kebutuhan_khusus_id'] ?? null),
            'kebutuhan_khusus' => $this->cleanString($row['kebutuhan_khusus'] ?? null),

            // Status
            'status_sekolah_id' => $this->cleanString($row['status_sekolah_id'] ?? null),
            'status_sekolah' => $this->cleanString($row['status_sekolah'] ?? null),
            'sk_pendirian_sekolah' => $this->cleanString($row['sk_pendirian_sekolah'] ?? null),
            'tanggal_sk_pendirian' => $tanggalSkPendirian,
            'status_kepemilikan_id' => $this->cleanInt($row['status_kepemilikan_id'] ?? null),
            'status_kepemilikan' => $this->cleanString($row['status_kepemilikan'] ?? null),
            'yayasan_id' => $this->cleanString($row['yayasan_id'] ?? null),
            'yayasan' => $this->cleanString($row['yayasan'] ?? null),
            'sk_izin_operasional' => $this->cleanString($row['sk_izin_operasional'] ?? null),
            'tanggal_sk_izin_operasional' => $tanggalSkIzin,

            // Rekening
            'no_rekening' => $this->cleanString($row['no_rekening'] ?? null),
            'nama_bank' => $this->cleanString($row['nama_bank'] ?? null),
            'cabang_kcp_unit' => $this->cleanString($row['cabang_kcp_unit'] ?? null),
            'rekening_atas_nama' => $this->cleanString($row['rekening_atas_nama'] ?? null),

            // Lahan
            'mbs' => $this->cleanString($row['mbs'] ?? null),
            'luas_tanah_milik' => $this->cleanInt($row['luas_tanah_milik'] ?? null),
            'luas_tanah_bukan_milik' => $this->cleanInt($row['luas_tanah_bukan_milik'] ?? null),

            // Registrasi
            'kode_registrasi' => $this->cleanString($row['kode_registrasi'] ?? null),
            'npwp' => $this->cleanString($row['npwp'] ?? null),
            'nm_wp' => $this->cleanString($row['nm_wp'] ?? null),

            // Flag
            'keaktifan' => $this->cleanString($row['keaktifan'] ?? null),
            'flag' => $this->cleanString($row['flag'] ?? null),

            // Listrik
            'daya_listrik' => $this->cleanInt($row['daya_listrik'] ?? null),
            'kontinuitas_listrik' => $this->cleanString($row['kontinuitas_listrik'] ?? null),
            'jarak_listrik' => $this->cleanString($row['jarak_listrik'] ?? null),

            // Wilayah
            'wilayah_terpencil' => $this->cleanString($row['wilayah_terpencil'] ?? null),
            'wilayah_perbatasan' => $this->cleanString($row['wilayah_perbatasan'] ?? null),
            'wilayah_transmigrasi' => $this->cleanString($row['wilayah_transmigrasi'] ?? null),
            'wilayah_adat_terpencil' => $this->cleanString($row['wilayah_adat_terpencil'] ?? null),
            'wilayah_bencana_alam' => $this->cleanString($row['wilayah_bencana_alam'] ?? null),
            'wilayah_bencana_sosial' => $this->cleanString($row['wilayah_bencana_sosial'] ?? null),

            // BOS & Waktu
            'partisipasi_bos' => $this->cleanString($row['partisipasi_bos'] ?? null),
            'waktu_penyelenggaraan_id' => $this->cleanInt($row['waktu_penyelenggaraan_id'] ?? null),
            'waktu_penyelenggaraan' => $this->cleanString($row['waktu_penyelenggaraan'] ?? null),

            // Internet & Sertifikasi
            'sumber_listrik_id' => $this->cleanInt($row['sumber_listrik_id'] ?? null),
            'sumber_listrik' => $this->cleanString($row['sumber_listrik'] ?? null),
            'sertifikasi_iso_id' => $this->cleanInt($row['sertifikasi_iso_id'] ?? null),
            'sertifikasi_iso' => $this->cleanString($row['sertifikasi_iso'] ?? null),
            'akses_internet_id' => $this->cleanInt($row['akses_internet_id'] ?? null),
            'akses_internet' => $this->cleanString($row['akses_internet'] ?? null),
            'akses_internet_2_id' => $this->cleanInt($row['akses_internet_2_id'] ?? null),
            'akses_internet_2' => $this->cleanString($row['akses_internet_2'] ?? null),

            // Akreditasi
            'akreditasi' => $this->cleanString($row['akreditasi'] ?? null),

            // Timestamps
            'create_date' => $createDate,
            'last_update' => $lastUpdate,
            'soft_delete_sekolah' => $this->cleanString($row['soft_delete_sekolah'] ?? null),

            // Kolom tambahan project (default values)
            'jumlah_siswa' => 0,
            'daya_tampung' => 0,
            'is_3t' => false,
            'is_sekolah_alam' => false,
        ];
    }

    private function parseKoordinat($value): ?float
    {
        if (empty($value) || $value === '0' || $value === 0) {
            return null;
        }

        // Jika format aneh seperti -1461200000000 (timestamp-like), convert ke decimal
        if (is_numeric($value) && (abs($value) > 1000)) {
            // Kemungkinan format: koordinat * 10^10 atau timestamp
            // Coba bagi 10^10
            $converted = $value / 10000000000;
            if (abs($converted) <= 180) {
                return round($converted, 9);
            }
        }

        // Jika sudah format decimal normal
        $float = (float) $value;
        if (abs($float) <= 180) {
            return round($float, 9);
        }

        return null;
    }

    private function parseDate($value): ?string
    {
        if (empty($value) || $value === '0' || $value === '1900-01-01 00:00:00') {
            return null;
        }

        try {
            $timestamp = strtotime($value);
            if ($timestamp && $timestamp > 0) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    private function cleanString($value): ?string
    {
        if (empty($value) || $value === '-' || $value === '****' || $value === '******') {
            return null;
        }

        return trim($value);
    }

    private function cleanInt($value): ?int
    {
        if (empty($value) || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
