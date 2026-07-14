<?php

namespace App\Ai\Tools;

use App\Services\SiakangMahasiswaAktifService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchActiveStudents implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mencari atau mendapatkan rincian statistik mahasiswa aktif (per jenjang, fakultas, atau prodi) berdasarkan semester atau tahun angkatan di UNTIRTA.';
    }

    public function handle(Request $request): Stringable|string
    {
        $aktifService = app(SiakangMahasiswaAktifService::class);
        $arguments = $request->all();
        $parameter = [];

        if (filled($arguments['semester'] ?? null)) {
            $parameter['semester'] = (string)$arguments['semester'];
        }
        if (filled($arguments['angkatan'] ?? null)) {
            $parameter['angkatan'] = (int)$arguments['angkatan'];
        }

        $hasilApi = $aktifService->getData($parameter);

        if (!($hasilApi['tersedia'] ?? false)) {
            return json_encode([
                'error' => 'API SIAKANG tidak tersedia untuk menarik data mahasiswa aktif saat ini.',
            ]);
        }

        $requestedFakultas = $arguments['fakultas'] ?? null;
        $prodiList = $hasilApi['detail_per_prodi'] ?? [];

        if (filled($requestedFakultas)) {
            $normalizedReq = $this->normalizeText($requestedFakultas);

            $filteredProdi = collect($prodiList)->filter(function ($item) use ($normalizedReq) {
                return str_contains($this->normalizeText($item['fakultas'] ?? ''), $normalizedReq);
            })->values()->all();

            $totalMhs = collect($filteredProdi)->sum('jumlah_mahasiswa_aktif');

            return json_encode([
                'jenis_data' => 'rincian_mahasiswa_aktif_fakultas',
                'fakultas_diminta' => $requestedFakultas,
                'total_mahasiswa_aktif_fakultas' => $totalMhs,
                'rincian_per_prodi_dan_jenjang' => $filteredProdi
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        
        $fakultasList = collect($hasilApi['detail_per_fakultas'] ?? [])->filter(function ($item) {
            $namaFak = trim($item['nama_fakultas'] ?? '');
            return !(empty($namaFak) || str_ireplace(' ', '', $namaFak) === 'Tidakadafakultas' || str_contains(strtolower($namaFak), 'tidak ada'));
        })->values()->all();

        return json_encode([
            'jenis_data' => 'ringkasan_mahasiswa_aktif_universitas',
            'total_mahasiswa_aktif_universitas' => $hasilApi['total_mahasiswa_aktif'] ?? 0,
            'semester_data' => $hasilApi['semester'] ?? null,
            'ringkasan_per_fakultas' => $fakultasList,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'fakultas' => $schema->string()->description('Nama fakultas (contoh: Hukum, Teknik, Kedokteran, Ekonomi). Gunakan filter ini jika pertanyaan spesifik menanyakan fakultas tertentu.')->nullable(),
            'semester' => $schema->string()->description('Kode semester, misalnya: "20251" untuk Ganjil, "20252" untuk Genap.')->nullable(),
            'angkatan' => $schema->integer()->description('Tahun angkatan masuk (format YYYY). Misal: 2025. Jika ingin mencari data Mahasiswa Baru, isi parameter ini dengan tahun berjalan.')->nullable(),
        ];
    }

    private function normalizeText(string $value): string
    {
        return str($value)
            ->lower()
            ->replace('&', ' dan ')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
