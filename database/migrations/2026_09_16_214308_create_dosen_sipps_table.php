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
        Schema::create('dosen_sipps', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->index();
            $table->string('semester', 20)->index();

            // 8 Indikator Kinerja SIPP
            $table->integer('sinta12')->default(0);
            $table->integer('sinta36')->default(0);
            $table->integer('jurnal_internasional_q')->default(0);
            $table->integer('jurnal_internasional_pbb')->default(0);
            $table->integer('jurnal_nasional_issn')->default(0);
            $table->integer('pengembangan')->default(0);
            $table->integer('pengabdian_masyarakat')->default(0);
            $table->integer('buku_referensi')->default(0);

            // Detail Tri Dharma Lists
            $table->json('publikasi')->nullable();
            $table->json('penelitian')->nullable();
            $table->json('pengabdian')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->unique(['nip', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen_sipps');
    }
};
