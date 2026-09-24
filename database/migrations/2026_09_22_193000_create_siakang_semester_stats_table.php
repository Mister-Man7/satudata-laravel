<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penampung angka agregat SIAKANG per prodi (mahasiswa aktif & lulus).
     * Halaman Akademik membaca tabel ini, bukan JSON dan bukan tebakan.
     */
    public function up(): void
    {
        Schema::create('siakang_semester_stats', function (Blueprint $table) {
            $table->id();
            $table->string('semester', 10);
            $table->string('jenis', 10); // aktif | lulus

            $table->string('prodi_id')->nullable();
            $table->string('kode_prodi')->nullable();
            $table->string('nama_prodi')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('fakultas')->nullable();

            $table->integer('jumlah')->default(0);
            $table->integer('laki_laki')->default(0);
            $table->integer('perempuan')->default(0);

            $table->timestamp('diambil_pada')->nullable();
            $table->timestamps();

            $table->unique(['semester', 'jenis', 'prodi_id'], 'siakang_semester_stats_unik');
            $table->index(['semester', 'jenis'], 'siakang_semester_stats_semester_jenis_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakang_semester_stats');
    }
};
