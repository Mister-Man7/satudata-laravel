<?php

namespace App\Ai\Tools;

use App\Services\Integrations\SiakangLulusanService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchGraduates implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Mencari, memfilter, atau menghitung data mahasiswa yang telah lulus (alumni) berdasarkan nama/NPM, kode program studi, fakultas, angkatan masuk, atau tahun kelulusan.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $lulusanService = app(SiakangLulusanService::class);
        $arguments = $request->all();

        $parameter = [];

        if (filled($arguments['search'] ?? null)) {
            $parameter['search'] = (string)$arguments['search'];
        }

        if (filled($arguments['kode_prodi'] ?? null)) {
            $parameter['kode_prodi'] = (string)$arguments['kode_prodi'];
        }

        if (filled($arguments['angkatan'] ?? null)) {
            $parameter['angkatan'] = (int)$arguments['angkatan'];
        }

        if (filled($arguments['tahun_lulus'] ?? null)) {
            $parameter['tahun_lulus'] = (int)$arguments['tahun_lulus'];
        }

        if (filled($arguments['fakultas'] ?? null)) {
            return $this->handleFacultySummary($lulusanService, $parameter, (string)$arguments['fakultas']);
        }

        if (filled($arguments['page'] ?? null)) {
            $parameter['page'] = (int)$arguments['page'];
        } else {
            $parameter['page'] = 1;
        }

        $parameter['limit'] = 10; // limit to 10 for chatbot usage to keep token size reasonable

        $hasilApi = $lulusanService->getData($parameter);

        if (!$hasilApi['tersedia']) {
            return json_encode([
                'error' => 'API SIAKANG tidak tersedia untuk melakukan pencarian lulusan saat ini.',
            ]);
        }

        // Clean up the data returned to only essential fields to save token space
        $daftarMahasiswa = collect($hasilApi['data'])->map(fn($mhs) => [
            'nama' => $mhs['nama'] ?? null,
            'nim' => $mhs['nim'] ?? $mhs['npm'] ?? null,
            'prodi' => data_get($mhs, 'prodi.nama_prodi_lengkap') ?? data_get($mhs, 'prodi.nama_prodi'),
            'kode_prodi' => data_get($mhs, 'prodi.kode_prodi'),
            'angkatan' => $mhs['angkatan'] ?? null,
            'tanggal_lulus' => $mhs['tanggal_lulus'] ?? null,
            'ipk' => $mhs['ipk'] ?? null,
        ])->all();

        return json_encode([
            'total_ditemukan' => $hasilApi['total'],
            'halaman_sekarang' => $hasilApi['halaman_sekarang'],
            'halaman_terakhir' => $hasilApi['halaman_terakhir'],
            'data_lulusan' => $daftarMahasiswa,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Kata kunci pencarian nama atau NPM mahasiswa.')->nullable(),
            'kode_prodi' => $schema->string()->description('Kode program studi (prodi).')->nullable(),
            'angkatan' => $schema->integer()->description('Tahun angkatan masuk mahasiswa (format YYYY).')->nullable(),
            'tahun_lulus' => $schema->integer()->description('Tahun kelulusan mahasiswa (format YYYY).')->nullable(),
            'page' => $schema->integer()->description('Halaman hasil pencarian (pagination).')->nullable(),
            'fakultas' => $schema->string()->description('Nama atau singkatan fakultas, misalnya FISIP, Ilmu Sosial dan Ilmu Politik, Teknik, FKIP, Ekonomi dan Bisnis. Gunakan ini untuk pertanyaan jumlah lulusan per fakultas, terutama jika disertai tahun kelulusan.')->nullable(),
        ];
    }

    /**
     * @param array<string, int|string> $parameter
     */
    private function handleFacultySummary(SiakangLulusanService $lulusanService, array $parameter, string $facultyQuery): Stringable|string
    {
        $hasilApi = $lulusanService->getData([
            ...$parameter,
            'limit' => 100,
            'page' => 1,
        ]);

        if (!$hasilApi['tersedia']) {
            return json_encode([
                'error' => 'API SIAKANG tidak tersedia untuk menghitung lulusan fakultas saat ini.',
                'fakultas_query' => $facultyQuery,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $detailFakultas = $hasilApi['detail_per_fakultas'] ?? [];
        $detailProdi = $hasilApi['detail_per_prodi'] ?? [];

        // Match faculty query against dynamic faculty list
        $queryNormalized = $this->normalizeText($facultyQuery);
        $matchedFaculty = null;
        $matchedTotal = 0;

        foreach ($detailFakultas as $f) {
            $namaFak = $f['nama_fakultas'] ?? '';
            if (str_contains($this->normalizeText($namaFak), $queryNormalized) || str_contains($queryNormalized, $this->normalizeText($namaFak))) {
                $matchedFaculty = $namaFak;
                $matchedTotal = $f['jumlah_mahasiswa_lulus'] ?? 0;
                break;
            }
        }

        // Filter prodi under matched faculty
        $matchedProdiList = [];
        if ($matchedFaculty) {
            foreach ($detailProdi as $p) {
                if (($p['fakultas'] ?? '') === $matchedFaculty || str_contains($this->normalizeText($p['fakultas'] ?? ''), $queryNormalized)) {
                    $matchedProdiList[] = [
                        'prodi' => $p['nama_prodi'] ?? 'Prodi',
                        'jumlah_lulus' => $p['jumlah_mahasiswa_lulus'] ?? 0,
                    ];
                }
            }
        }

        return json_encode([
            'jenis_data' => 'ringkasan_lulusan_fakultas',
            'fakultas' => $matchedFaculty ?? $facultyQuery,
            'filter' => [
                'tahun_lulus' => $parameter['tahun_lulus'] ?? null,
                'angkatan' => $parameter['angkatan'] ?? null,
            ],
            'total_lulus' => $matchedTotal > 0 ? $matchedTotal : array_sum(array_column($matchedProdiList, 'jumlah_lulus')),
            'rincian_per_prodi' => $matchedProdiList,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

