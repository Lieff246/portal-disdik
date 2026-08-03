<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sekolah', function (Blueprint $table) {
            $table->string('sekolah_id', 50);
            $table->string('semester_id', 10);
            $table->string('nama', 150);
            $table->string('npsn', 20)->nullable()->index();
            $table->integer('bentuk_pendidikan_id')->nullable();
            $table->string('bentuk_pendidikan', 20)->nullable();
            $table->string('alamat_jalan', 255)->nullable();
            $table->string('kode_wilayah', 30)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kode_kabupaten', 30)->nullable()->index();
            $table->string('kabupaten', 100)->nullable();
            $table->string('kode_provinsi', 30)->nullable();
            $table->decimal('lintang', 12, 9)->nullable();
            $table->decimal('bujur', 12, 9)->nullable();
            $table->string('status_sekolah', 20)->nullable();
            $table->string('akreditasi', 10)->nullable();
            $table->string('keaktifan', 10)->nullable();
            
            // Kolom baru tambahan dari Alif
            $table->integer('jumlah_siswa')->nullable()->default(0);
            $table->integer('daya_tampung')->nullable()->default(0);
            $table->boolean('is_3t')->default(false); // 3T (Terdepan, Terluar, Tertinggal)
            $table->boolean('is_sekolah_alam')->default(false); // Sekolah Alam

            // Kategori wilayah 
            $table->string('wilayah_terpencil', 10)->nullable();
            $table->string('wilayah_perbatasan', 10)->nullable();
            $table->string('wilayah_transmigrasi', 10)->nullable();
            
            $table->primary(['sekolah_id', 'semester_id']); // Composite PK
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah');
    }
};
