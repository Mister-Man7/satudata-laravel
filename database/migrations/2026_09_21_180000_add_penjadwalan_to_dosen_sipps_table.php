<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pindahkan penjadwalan dosen dari tabel terpisah ke kolom JSON di `dosen_sipps`.
     *
     * Alasannya: satu baris dosen+semester sudah memuat seluruh angka profil dosen, sehingga
     * tidak perlu tabel kedua. Catatan domain: `dosen_sipps` memuat data SIPP (indikator &
     * Tri Dharma) sekaligus penjadwalan dari SIAKANG sebagai sumber SKS & jumlah MK.
     */
    public function up(): void
    {
        Schema::table('dosen_sipps', function (Blueprint $table) {
            $table->json('penjadwalan')->nullable();
        });

        // Bila tabel penjadwalan terpisah sempat ada, pindahkan isinya lalu hapus.
        if (Schema::hasTable('dosen_penjadwalans')) {
            foreach (DB::table('dosen_penjadwalans')->get() as $baris) {
                DB::table('dosen_sipps')->updateOrInsert(
                    ['nip' => $baris->nip, 'semester' => $baris->semester],
                    [
                        'penjadwalan' => $baris->items,
                        'created_at' => $baris->created_at ?? now(),
                        'updated_at' => $baris->updated_at ?? now(),
                    ]
                );
            }

            Schema::drop('dosen_penjadwalans');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dosen_sipps', function (Blueprint $table) {
            $table->dropColumn('penjadwalan');
        });
    }
};
