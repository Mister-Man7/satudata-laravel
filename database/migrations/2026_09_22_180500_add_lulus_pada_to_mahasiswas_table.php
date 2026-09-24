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
            // Tanggal lulus hanya tersimpan di dalam JSON `payload`, sehingga hitungan
            // lulusan per semester harus membongkar JSON 80 ribu baris (±±50 detik
            // untuk dua semester). Kolom ini memindahkan nilainya ke kolom berindeks.
            if (!Schema::hasColumn('mahasiswas', 'lulus_pada')) {
                $table->date('lulus_pada')->nullable();
            }

            if (!$this->hasIndex('mahasiswas', 'mahasiswas_lulus_pada_index')) {
                $table->index('lulus_pada', 'mahasiswas_lulus_pada_index');
            }
        });

        // Backfill dari payload. Hanya dijalankan pada MySQL (SQLite tidak punya
        // JSON_UNQUOTE) dan hanya untuk nilai yang benar-benar berbentuk tanggal.
        if (DB::connection()->getDriverName() === 'mysql') {
            $tanggal = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.tanggal_lulus')), JSON_UNQUOTE(JSON_EXTRACT(payload, '$.tanggal_ijazah')))";

            DB::statement(
                "update mahasiswas set lulus_pada = {$tanggal} "
                . "where lulus_pada is null and payload is not null and {$tanggal} regexp '^[0-9]{4}-[0-9]{2}-[0-9]{2}'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropIndex('mahasiswas_lulus_pada_index');
            $table->dropColumn('lulus_pada');
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
