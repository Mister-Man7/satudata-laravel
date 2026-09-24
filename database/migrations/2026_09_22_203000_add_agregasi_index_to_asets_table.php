<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indeks pendukung agregasi halaman Aset yang selama ini memindai 99 ribu
     * baris (tabel 230 MB) sehingga halaman dingin butuh 1-2 detik:
     * - MIN(lokasi_lengkap) GROUP BY id_kampus (nama kampus) dan pencarian lokasi
     * - SUM(nilai_perolehan) pada kartu ringkasan
     */
    public function up(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            if (!$this->hasIndex('asets', 'asets_kampus_lokasi_index')) {
                $table->index(['id_kampus', 'lokasi_lengkap'], 'asets_kampus_lokasi_index');
            }

            if (!$this->hasIndex('asets', 'asets_lokasi_index')) {
                $table->index('lokasi_lengkap', 'asets_lokasi_index');
            }

            if (!$this->hasIndex('asets', 'asets_nilai_perolehan_index')) {
                $table->index('nilai_perolehan', 'asets_nilai_perolehan_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            $table->dropIndex('asets_kampus_lokasi_index');
            $table->dropIndex('asets_lokasi_index');
            $table->dropIndex('asets_nilai_perolehan_index');
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
