<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asets', function (Blueprint $table) {
            $table->id();

            $table->uuid('id_bmn')->unique();
            $table->uuid('id_satker')->nullable()->index();
            $table->string('id_kampus')->nullable();
            $table->string('id_gedung')->nullable();
            $table->string('id_lantai_gedung')->nullable();
            $table->string('id_ruangan')->nullable();

            $table->uuid('id_jenis_barang')->nullable()->index();
            $table->string('nama_jenis_barang')->nullable();
            $table->uuid('id_kode_barang')->nullable()->index();
            $table->string('nama_kode_barang')->nullable();

            $table->string('nup')->nullable();
            $table->text('merk')->nullable();
            $table->string('tipe')->nullable();
            $table->date('tgl_perolehan')->nullable();
            $table->integer('kondisi')->nullable();
            $table->string('kondisi_text')->nullable();
            $table->string('intra_ekstra')->nullable();
            $table->integer('status_sewa')->default(0);

            $table->decimal('nilai_perolehan', 18, 2)->default(0);
            $table->decimal('nilai_buku', 18, 2)->default(0);

            $table->string('lokasi_lengkap')->nullable();
            $table->string('umur_barang')->nullable();


            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asets');
    }
};
