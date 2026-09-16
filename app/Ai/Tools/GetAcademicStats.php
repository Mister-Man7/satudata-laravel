<?php

namespace App\Ai\Tools;

use App\Services\Integrations\SiakangLulusanService;
use App\Services\Integrations\SiakangMahasiswaAktifService;
use App\Services\Integrations\SimpegPegawaiService;
use App\Services\Integrations\SimantapService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAcademicStats implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mendapatkan ringkasan statistik terkini universitas UNTIRTA secara dinamis, mencakup total mahasiswa aktif, total alumni/lulusan, jumlah dosen/pegawai, dan sebaran fakultas.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $aktifService = app(SiakangMahasiswaAktifService::class);
        $lulusanService = app(SiakangLulusanService::class);
        $pegawaiService = app(SimpegPegawaiService::class);
        $simantapService = app(SimantapService::class);

        $resAktif = $aktifService->getData();
        $resLulusan = $lulusanService->getData(['limit' => 1, 'page' => 1]);
        $resDosen = $pegawaiService->getDataDosen();
        $resKampus = $simantapService->makeRequest('GET', 'kampus');

        $dataAktif = $resAktif->success ? ($resAktif->data ?? []) : [];
        $dataLulusan = $resLulusan->success ? ($resLulusan->data ?? []) : [];

        $totalMahasiswaAktif = $dataAktif['total_mahasiswa_aktif'] ?? $dataAktif['total_mahasiswa'] ?? $dataAktif['total'] ?? 0;
        $detailFakultasAktif = $dataAktif['detail_per_fakultas'] ?? [];

        $totalLulusan = $dataLulusan['total'] ?? $dataLulusan['total_mahasiswa_lulus'] ?? 0;
        $detailFakultasLulus = $dataLulusan['detail_per_fakultas'] ?? [];

        $totalDosen = 0;
        if ($resDosen->success ?? false) {
            $totalDosen = count($resDosen->data ?? []);
        }

        $kampusList = $resKampus['data']['data'] ?? $resKampus['data'] ?? [];
        $totalKampus = count($kampusList);

        // Merge faculty stats dynamically
        $fakultasMap = [];
        foreach ($detailFakultasAktif as $f) {
            $nama = $f['nama_fakultas'] ?? 'Fakultas Lain';
            $fakultasMap[$nama] = [
                'name' => $nama,
                'mahasiswa_aktif' => (int)($f['jumlah_mahasiswa_aktif'] ?? 0),
                'mahasiswa_lulus' => 0,
            ];
        }
        foreach ($detailFakultasLulus as $f) {
            $nama = $f['nama_fakultas'] ?? 'Fakultas Lain';
            if (!isset($fakultasMap[$nama])) {
                $fakultasMap[$nama] = [
                    'name' => $nama,
                    'mahasiswa_aktif' => 0,
                    'mahasiswa_lulus' => 0,
                ];
            }
            $fakultasMap[$nama]['mahasiswa_lulus'] = (int)($f['jumlah_mahasiswa_lulus'] ?? 0);
        }

        $output = [
            'status_layanan_api' => [
                'siakang_aktif' => $resAktif->success ? 'online' : 'offline',
                'siakang_lulusan' => $resLulusan->success ? 'online' : 'offline',
                'simpeg_dosen' => ($resDosen->success ?? false) ? 'online' : 'offline',
                'simantap_aset' => !empty($resKampus) ? 'online' : 'offline',
            ],
            'ringkasan_universitas' => [
                'total_mahasiswa_aktif' => $totalMahasiswaAktif,
                'total_alumni_lulusan' => $totalLulusan,
                'total_dosen' => $totalDosen,
                'total_lokasi_kampus' => $totalKampus,
            ],
            'sebaran_per_fakultas' => array_values($fakultasMap),
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

