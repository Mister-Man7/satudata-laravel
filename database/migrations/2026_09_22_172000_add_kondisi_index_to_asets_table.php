<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            // Hitungan kondisi di halaman /aset selalu memindai seluruh tabel
            // (99 ribu baris, 230 MB, rata-rata 2,8 KB per baris) sehingga satu
            // query memakan ±0,6 detik. Dengan indeks ini hitungannya hanya
            // membaca indeks, bukan barisnya.
            if (!$this->hasIndex('asets', 'asets_kondisi_index')) {
                $table->index('kondisi', 'asets_kondisi_index');
            }

            // Breakdown kondisi per kampus memakai WHERE id_kampus = ? AND kondisi = ?.
            if (!$this->hasIndex('asets', 'asets_kampus_kondisi_index')) {
                $table->index(['id_kampus', 'kondisi'], 'asets_kampus_kondisi_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            $table->dropIndex('asets_kondisi_index');
            $table->dropIndex('asets_kampus_kondisi_index');
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
