<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cabang_dinas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->json('kode_kabupaten'); // Array of kode_kabupaten
            $table->json('kabupaten_kota'); // Array of nama kabupaten
            $table->decimal('map_lat', 10, 6)->nullable();
            $table->decimal('map_lng', 10, 6)->nullable();
            $table->integer('map_zoom')->default(9);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabang_dinas');
    }
};
