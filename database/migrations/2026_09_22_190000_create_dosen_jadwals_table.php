<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penjadwalan dosen sebelumnya disimpan sebagai satu blok JSON di kolom
     * `dosen_sipps.penjadwalan`. Tabel ini menyimpannya per baris (satu baris per
     * jadwal x waktu kuliah) supaya halaman profil dosen tidak lagi membaca JSON.
     */
    public function up(): void
    {
        Schema::create('dosen_jadwals', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->index();
            $table->string('semester', 10)->index();

            $table->string('jadwal_id')->nullable();
            $table->string('kode_jadwal')->nullable();

            $table->string('mata_kuliah_kode')->nullable();
            $table->string('mata_kuliah_nama')->nullable();
            $table->integer('mata_kuliah_sks')->default(0);
            $table->integer('sks')->default(0);
            $table->string('mode')->nullable();
            $table->string('tipe_jadwal')->nullable();
            $table->string('kelas')->nullable();

            $table->string('hari')->nullable();
            $table->integer('hari_numeric')->default(0);
            $table->string('jam_mulai', 8)->nullable();
            $table->string('jam_selesai', 8)->nullable();
            $table->string('nama_ruang')->nullable();
            $table->string('kode_ruang')->nullable();

            $table->timestamps();

            $table->index(['nip', 'semester'], 'dosen_jadwals_nip_semester_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen_jadwals');
    }
};
