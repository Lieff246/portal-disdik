<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolah', function (Blueprint $table) {
            // Primary Key
            $table->string('sekolah_id', 50);
            $table->string('semester_id', 10);

            // Identitas
            $table->string('nama', 150);
            $table->string('nama_nomenklatur', 150)->nullable();
            $table->string('nss', 20)->nullable();
            $table->string('npsn', 20)->nullable()->index();
            $table->integer('bentuk_pendidikan_id')->nullable();
            $table->string('bentuk_pendidikan', 20)->nullable();

            // Alamat
            $table->string('alamat_jalan', 255)->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('nama_dusun', 100)->nullable();
            $table->string('kode_wilayah', 30)->nullable();
            $table->string('kode_desa_kelurahan', 30)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kode_kecamatan', 30)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kode_kabupaten', 30)->nullable()->index();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kode_provinsi', 30)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kode_pos', 10)->nullable();

            // Koordinat
            $table->decimal('lintang', 12, 9)->nullable();
            $table->decimal('bujur', 12, 9)->nullable();

            // Kontak
            $table->string('nomor_telepon', 30)->nullable();
            $table->string('nomor_fax', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 150)->nullable();

            // Kebutuhan Khusus
            $table->integer('kebutuhan_khusus_id')->nullable();
            $table->string('kebutuhan_khusus', 50)->nullable();

            // Status
            $table->string('status_sekolah_id', 5)->nullable();
            $table->string('status_sekolah', 20)->nullable();
            $table->string('sk_pendirian_sekolah', 100)->nullable();
            $table->date('tanggal_sk_pendirian')->nullable();
            $table->integer('status_kepemilikan_id')->nullable();
            $table->string('status_kepemilikan', 50)->nullable();
            $table->string('yayasan_id', 50)->nullable();
            $table->string('yayasan', 150)->nullable();
            $table->string('sk_izin_operasional', 100)->nullable();
            $table->date('tanggal_sk_izin_operasional')->nullable();

            // Rekening
            $table->string('no_rekening', 50)->nullable();
            $table->string('nama_bank', 100)->nullable();
            $table->string('cabang_kcp_unit', 100)->nullable();
            $table->string('rekening_atas_nama', 100)->nullable();

            // Lahan
            $table->string('mbs', 5)->nullable();
            $table->integer('luas_tanah_milik')->nullable();
            $table->integer('luas_tanah_bukan_milik')->nullable();

            // Registrasi
            $table->string('kode_registrasi', 50)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('nm_wp', 100)->nullable();

            // Keaktifan & Flag
            $table->string('keaktifan', 10)->nullable();
            $table->string('flag', 10)->nullable();

            // Listrik
            $table->integer('daya_listrik')->nullable();
            $table->string('kontinuitas_listrik', 5)->nullable();
            $table->string('jarak_listrik', 10)->nullable();

            // Kategori Wilayah
            $table->string('wilayah_terpencil', 10)->nullable();
            $table->string('wilayah_perbatasan', 10)->nullable();
            $table->string('wilayah_transmigrasi', 10)->nullable();
            $table->string('wilayah_adat_terpencil', 10)->nullable();
            $table->string('wilayah_bencana_alam', 10)->nullable();
            $table->string('wilayah_bencana_sosial', 10)->nullable();

            // BOS & Waktu
            $table->string('partisipasi_bos', 5)->nullable();
            $table->integer('waktu_penyelenggaraan_id')->nullable();
            $table->string('waktu_penyelenggaraan', 50)->nullable();

            // Sumber Listrik
            $table->integer('sumber_listrik_id')->nullable();
            $table->string('sumber_listrik', 50)->nullable();

            // Sertifikasi
            $table->integer('sertifikasi_iso_id')->nullable();
            $table->string('sertifikasi_iso', 50)->nullable();

            // Internet
            $table->integer('akses_internet_id')->nullable();
            $table->string('akses_internet', 50)->nullable();
            $table->integer('akses_internet_2_id')->nullable();
            $table->string('akses_internet_2', 50)->nullable();

            // Akreditasi
            $table->string('akreditasi', 10)->nullable();

            // Timestamps dari Dapodik
            $table->timestamp('create_date')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->string('soft_delete_sekolah', 5)->nullable();

            // Kolom tambahan project
            $table->integer('jumlah_siswa')->nullable()->default(0);
            $table->integer('daya_tampung')->nullable()->default(0);
            $table->boolean('is_3t')->default(false);
            $table->boolean('is_sekolah_alam')->default(false);

            $table->primary(['sekolah_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah');
    }
};
