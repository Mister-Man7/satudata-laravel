<?php

namespace App\Ai\Tools;

use App\Models\Aset;
use App\Services\Integrations\SimantapService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAssetStats implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mendapatkan statistik aset BMN, gedung, ruangan, dan lokasi kampus UNTIRTA dari SIMANTAP.';
    }

    public function handle(Request $request): Stringable|string
    {
        $simantap = app(SimantapService::class);
        $resKampus = $simantap->makeRequest('GET', 'kampus');

        $kampusList = $resKampus['data']['data'] ?? $resKampus['data'] ?? [];

        $totalAset = 0;
        try {
            $totalAset = Aset::count();
        } catch (\Throwable $e) {
            //
        }

        return json_encode([
            'sources' => 'API SIMANTAP / Database BMN Aset UNTIRTA',
            'total_aset_bmn' => $totalAset,
            'total_lokasi_kampus' => count($kampusList),
            'daftar_kampus' => $kampusList,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
