<?php

namespace App\Ai\Tools;

use App\Services\SiakangLulusanService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAcademicStats implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mendapatkan statistik data akademik universitas secara ringkas, termasuk total alumni/mahasiswa lulus dan distribusi jumlah mahasiswa per fakultas di UNTIRTA.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $lulusanService = app(SiakangLulusanService::class);
        $hasilApi = $lulusanService->getData([
            'limit' => 1,
            'page' => 1,
        ]);

        $totalLulusan = 0;
        $apiTersedia = 'tidak tersedia (offline)';

        if ($hasilApi['tersedia']) {
            $totalLulusan = $hasilApi['total'];
            $apiTersedia = 'tersedia (online)';
        }

        $fakultas = [
            ['name' => 'Kedokteran', 'total' => 288],
            ['name' => 'Pertanian', 'total' => 5114],
            ['name' => 'Hukum', 'total' => 5985],
            ['name' => 'Teknik', 'total' => 10895],
            ['name' => 'Ekonomi dan Bisnis', 'total' => 12321],
            ['name' => 'Ilmu Sosial & Politik', 'total' => 6031],
            ['name' => 'Keguruan dan Ilmu Pendidikan', 'total' => 17229],
            ['name' => 'Pascasarjana', 'total' => 1534],
        ];

        $output = [
            'status_koneksi_api_siakang' => $apiTersedia,
            'ringkasan_mahasiswa' => [
                'mahasiswa_aktif' => 'Belum tersedia',
                'mahasiswa_tidak_aktif' => 'Belum tersedia',
                'mahasiswa_lulus' => $totalLulusan,
                'mahasiswa_baru' => 'Belum tersedia',
            ],
            'distribusi_mahasiswa_per_fakultas' => $fakultas,
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
