<?php

namespace App\Ai\Tools;

use App\Services\Integrations\SiakangMahasiswaAktifService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetActiveStudentStats implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mendapatkan statistik lengkap dan rincian mahasiswa aktif UNTIRTA per semester, per fakultas, dan per program studi (prodi).';
    }

    public function handle(Request $request): Stringable|string
    {
        $aktifService = app(SiakangMahasiswaAktifService::class);
        $arguments = $request->all();

        $params = [];
        if (filled($arguments['semester'] ?? null)) {
            $params['semester'] = (string)$arguments['semester'];
        }

        $res = $aktifService->getData($params);

        if (!$res->success || empty($res->data)) {
            return json_encode([
                'error' => 'Data mahasiswa aktif tidak tersedia dari API SIAKANG saat ini.',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $hasil = $res->data;

        return json_encode([
            'total_mahasiswa_aktif' => $hasil['total_mahasiswa_aktif'] ?? $hasil['total'] ?? 0,
            'detail_per_fakultas' => $hasil['detail_per_fakultas'] ?? [],
            'detail_per_prodi' => $hasil['detail_per_prodi'] ?? [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'semester' => $schema->string()->description('Kode semester (contoh: 20241 untuk Ganjil 2024, 20242 untuk Genap 2024). Optional.')->nullable(),
        ];
    }
}
