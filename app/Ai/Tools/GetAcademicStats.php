<?php

namespace App\Ai\Tools;

use App\Services\SiakangLulusanService;
use App\Services\SiakangMahasiswaAktifService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAcademicStats implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mendapatkan statistik ringkas data akademik universitas, termasuk total mahasiswa aktif dan total alumni/lulusan secara keseluruhan di UNTIRTA.';
    }

    public function handle(Request $request): Stringable|string
    {
        $lulusanService = app(SiakangLulusanService::class);
        $aktifService = app(SiakangMahasiswaAktifService::class);
        
        $hasilLulusan = $lulusanService->getData([
            'limit' => 1,
            'page' => 1,
        ]);

        $totalLulusan = $hasilLulusan['tersedia'] ? $hasilLulusan['total'] : 0;

        $hasilAktif = $aktifService->getData([]);
        $totalAktif = $hasilAktif['tersedia'] ? (int)($hasilAktif['total_mahasiswa_aktif'] ?? 0) : 0;

        $fakultas = [];
        $dataFakultasApi = $hasilAktif['detail_per_fakultas'] ?? [];

        foreach ($dataFakultasApi as $item) {
            $namaFak = trim($item['nama_fakultas'] ?? '');
            if (empty($namaFak) || str_ireplace(' ', '', $namaFak) === 'Tidakadafakultas' || str_contains(strtolower($namaFak), 'tidak ada')) {
                continue;
            }
            $fakultas[] = [
                'name' => $namaFak,
                'total' => (int)($item['jumlah_mahasiswa_aktif'] ?? 0)
            ];
        }

        $output = [
            'status_koneksi_api_lulusan' => $hasilLulusan['tersedia'] ? 'tersedia (online)' : 'tidak tersedia (offline)',
            'status_koneksi_api_aktif' => $hasilAktif['tersedia'] ? 'tersedia (online)' : 'tidak tersedia (offline)',
            'ringkasan_mahasiswa' => [
                'mahasiswa_aktif' => $totalAktif,
                'mahasiswa_lulus' => $totalLulusan,
            ],
            'distribusi_mahasiswa_aktif_per_fakultas' => $fakultas,
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
