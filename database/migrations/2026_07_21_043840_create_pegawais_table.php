<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();

            $table->string('nip')->unique();
            $table->string('kode_data')->nullable()->index();
            $table->string('id_sdm')->nullable();

            $table->string('nama');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();

            $table->string('email')->nullable();
            $table->string('no_tlp')->nullable();

            $table->string('unit_kerja')->nullable();
            $table->string('unit_kerja_id')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('jabatan_id')->nullable();
            $table->string('pangkat')->nullable();
            $table->string('pangkat_id')->nullable();

            $table->string('status_kerja')->nullable();
            $table->string('level_pegawai')->nullable();

            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
