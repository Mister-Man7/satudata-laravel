<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('statistik_mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->string('prodi_id');
            $table->string('kode_prodi')->nullable();
            $table->string('nama_prodi')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('fakultas_id')->nullable();
            $table->string('fakultas')->nullable();
            $table->string('nama_fakultas')->nullable();
            $table->integer('jumlah_mahasiswa_aktif');
            $table->integer('jumlah_laki_laki');
            $table->integer('jumlah_perempuan');
            $table->string('angkatan_filter', 20)->default('semua');

            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['prodi_id', 'angkatan_filter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistik_prodis');
    }
};
