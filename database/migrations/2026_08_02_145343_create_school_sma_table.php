<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_sma', function (Blueprint $table) {
            $table->string('id', 255)->primary(); // SCH0001-xxx
            $table->string('asnsmart_department_sub_id', 255)->nullable();
            $table->string('name', 61)->nullable();
            $table->string('grade', 4)->nullable();   // SMA/SMK/SLB/SMTK
            $table->string('status', 20)->nullable(); // Negeri/Swasta
            $table->string('kecamatan', 20)->nullable();
            $table->string('city', 22)->nullable();   // Nama Kab/Kota
            $table->string('kepsek', 35)->nullable();
            $table->string('nip_kepsek', 21)->nullable();
            $table->string('no_hp_kepsek', 13)->nullable();
            $table->string('status_kepsek', 9)->nullable(); // Definitif/PLT
            $table->text('address')->nullable();
            $table->string('npsn', 8)->nullable()->index();
            $table->integer('city_id')->nullable();
            $table->string('latitude', 255)->nullable();
            $table->string('longitude', 255)->nullable();
            $table->json('polygon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_sma');
    }
};
