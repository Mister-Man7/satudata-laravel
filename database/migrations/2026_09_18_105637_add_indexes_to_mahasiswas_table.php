<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            // Speeds up fallback GROUP BY prodi_id, jenis_kelamin_string (~3s → <100ms)
            if (!$this->hasIndex('mahasiswas', 'mahasiswas_prodi_jk_index')) {
                $table->index(['prodi_id', 'jenis_kelamin_string'], 'mahasiswas_prodi_jk_index');
            }

            // Speeds up WHERE angkatan = ? queries (mahasiswa baru count)
            if (!$this->hasIndex('mahasiswas', 'mahasiswas_angkatan_index')) {
                $table->index('angkatan', 'mahasiswas_angkatan_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropIndex('mahasiswas_prodi_jk_index');
            $table->dropIndex('mahasiswas_angkatan_index');
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
