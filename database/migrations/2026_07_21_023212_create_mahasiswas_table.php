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
        Schema::create('mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 20)->unique();
            $table->string('nama');
            $table->string('prodi_id')->nullable();
            $table->string('jalur_masuk_id');
            $table->string('program_id');
            $table->string('jenjang_id');
            $table->string('dosen_wali_id');
            $table->string('angkatan')->nullable();
            $table->string('tanggal_masuk');
            $table->string('kewarganegaraan');
            $table->string('agama');
            $table->string('jenis_kelamin_string');
            $table->string('tempat_tanggal_lahir');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswas');
    }
};
