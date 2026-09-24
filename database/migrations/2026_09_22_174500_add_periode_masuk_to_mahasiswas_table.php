<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            // Periode masuk sebelumnya hanya tersimpan di dalam kolom JSON `payload`,
            // sehingga hitungan mahasiswa baru per semester (halaman Akademik) harus
            // membongkar JSON 80 ribu baris: 2 query memakan 4,6 detik.
            if (!Schema::hasColumn('mahasiswas', 'periode_masuk')) {
                $table->string('periode_masuk', 10)->nullable();
            }

            if (!$this->hasIndex('mahasiswas', 'mahasiswas_periode_masuk_index')) {
                $table->index('periode_masuk', 'mahasiswas_periode_masuk_index');
            }
        });

        // Isi kolom dari payload untuk baris yang sudah ada. Ekspresi JSON berbeda
        // antar driver (SQLite tidak punya JSON_UNQUOTE) dan basis data uji memang
        // mulai dari tabel kosong, jadi backfill hanya dijalankan pada MySQL; baris
        // yang belum terisi tetap terhitung lewat cadangan di query aplikasi.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::table('mahasiswas')
                ->whereNull('periode_masuk')
                ->whereNotNull('payload')
                ->update(['periode_masuk' => DB::raw('JSON_UNQUOTE(JSON_EXTRACT(payload, \'$.periode_masuk\'))')]);
        }
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropIndex('mahasiswas_periode_masuk_index');
            $table->dropColumn('periode_masuk');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes($table);
            foreach ($indexes as $index) {
                if (($index['name'] ?? '') === $indexName) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        return false;
    }
};
