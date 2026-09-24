<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom ini menyimpan satu blok JSON penjadwalan dosen. Aplikasi tidak lagi
     * membacanya: penjadwalan kini tersimpan per baris di tabel `dosen_jadwals`
     * (satu baris per jadwal x waktu kuliah).
     *
     * Data di kolom ini bisa ditarik ulang dari SIAKANG lewat penarik Chromium
     * (scripts/sync-penjadwalan.php) bila memang dibutuhkan.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('dosen_sipps', 'penjadwalan')) {
            return;
        }

        Schema::table('dosen_sipps', function (Blueprint $table) {
            $table->dropColumn('penjadwalan');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('dosen_sipps', 'penjadwalan')) {
            return;
        }

        Schema::table('dosen_sipps', function (Blueprint $table) {
            $table->json('penjadwalan')->nullable();
        });
    }
};
