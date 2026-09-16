<?php

namespace App\Ai\Tools;

use App\Services\Integrations\SimpegPegawaiService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPegawaiStats implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mendapatkan statistik ringkasan pegawai dan dosen UNTIRTA, sebaran unit kerja/fakultas, dan status kepegawaian.';
    }

    public function handle(Request $request): Stringable|string
    {
        $simpegService = app(SimpegPegawaiService::class);
        $resPegawai = $simpegService->getData();
        $resDosen = $simpegService->getDataDosen();

        $allPegawai = $resPegawai->success ? ($resPegawai->data ?? []) : [];
        $allDosen = $resDosen->success ? ($resDosen->data ?? []) : [];

        $unitKerjaCounts = [];
        $statusKerjaCounts = [];

        foreach ($allPegawai as $p) {
            $unit = trim((string)($p['unitKerja'] ?? 'Lainnya'));
            $status = trim((string)($p['statusKerja'] ?? 'Lainnya'));

            if (!empty($unit)) {
                $unitKerjaCounts[$unit] = ($unitKerjaCounts[$unit] ?? 0) + 1;
            }
            if (!empty($status)) {
                $statusKerjaCounts[$status] = ($statusKerjaCounts[$status] ?? 0) + 1;
            }
        }

        arsort($unitKerjaCounts);
        arsort($statusKerjaCounts);

        $unitList = [];
        foreach (array_slice($unitKerjaCounts, 0, 10, true) as $unit => $total) {
            $unitList[] = ['unit_kerja' => $unit, 'total_pegawai' => $total];
        }

        $statusList = [];
        foreach ($statusKerjaCounts as $status => $total) {
            $statusList[] = ['status_kerja' => $status, 'total_pegawai' => $total];
        }

        return json_encode([
            'sumber' => 'API SIMPEG / DB Pegawai UNTIRTA',
            'total_pegawai' => count($allPegawai),
            'total_dosen' => count($allDosen),
            'total_tendik_staff' => max(0, count($allPegawai) - count($allDosen)),
            'sebaran_status_kerja' => $statusList,
            'top_unit_kerja' => $unitList,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
