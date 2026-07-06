<?php

namespace App\Ai\Tools;

use App\Services\SiakangLulusanService;
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
            $parameter['search'] = (string) $arguments['search'];
        }

        if (filled($arguments['kode_prodi'] ?? null)) {
            $parameter['kode_prodi'] = (string) $arguments['kode_prodi'];
        }

        if (filled($arguments['angkatan'] ?? null)) {
            $parameter['angkatan'] = (int) $arguments['angkatan'];
        }

        if (filled($arguments['tahun_lulus'] ?? null)) {
            $parameter['tahun_lulus'] = (int) $arguments['tahun_lulus'];
        }

        if (filled($arguments['fakultas'] ?? null)) {
            return $this->handleFacultySummary($lulusanService, $parameter, (string) $arguments['fakultas']);
        }

        if (filled($arguments['page'] ?? null)) {
            $parameter['page'] = (int) $arguments['page'];
        } else {
            $parameter['page'] = 1;
        }

        $parameter['limit'] = 10; // limit to 10 for chatbot usage to keep token size reasonable

        $hasilApi = $lulusanService->ambilData($parameter);

        if (! $hasilApi['tersedia']) {
            return json_encode([
                'error' => 'API SIAKANG tidak tersedia untuk melakukan pencarian lulusan saat ini.',
            ]);
        }

        // Clean up the data returned to only essential fields to save token space
        $daftarMahasiswa = collect($hasilApi['data'])->map(fn ($mhs) => [
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
     * @param  array<string, int|string>  $parameter
     */
    private function handleFacultySummary(SiakangLulusanService $lulusanService, array $parameter, string $facultyQuery): Stringable|string
    {
        $faculty = $this->resolveFaculty($facultyQuery);

        if ($faculty === null) {
            return json_encode([
                'error' => 'Fakultas tidak dikenali.',
                'fakultas_diminta' => $facultyQuery,
                'fakultas_tersedia' => array_column($this->facultyCatalog(), 'name'),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $allGraduates = [];
        $page = 1;
        $lastPage = 1;

        do {
            $hasilApi = $lulusanService->ambilData([
                ...$parameter,
                'limit' => 1000,
                'page' => $page,
            ]);

            if (! $hasilApi['tersedia']) {
                return json_encode([
                    'error' => 'API SIAKANG tidak tersedia untuk menghitung lulusan fakultas saat ini.',
                    'fakultas' => $faculty['name'],
                    'filter' => $parameter,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            $allGraduates = [
                ...$allGraduates,
                ...$hasilApi['data'],
            ];
            $lastPage = max(1, (int) $hasilApi['halaman_terakhir']);
            $page++;
        } while ($page <= $lastPage);

        $facultyCodes = array_map('strval', $faculty['kode_prodi']);
        $graduates = collect($allGraduates)
            ->filter(fn (array $graduate): bool => in_array((string) data_get($graduate, 'prodi.kode_prodi'), $facultyCodes, true));

        $byProgram = $graduates
            ->groupBy(fn (array $graduate): string => (string) (data_get($graduate, 'prodi.nama_prodi') ?? 'Prodi tidak diketahui'))
            ->map(fn ($items, string $program): array => [
                'prodi' => $program,
                'jumlah_lulus' => $items->count(),
            ])
            ->values()
            ->sortBy('prodi')
            ->values()
            ->all();

        return json_encode([
            'jenis_data' => 'ringkasan_lulusan_fakultas',
            'sumber' => 'API SIAKANG /v2/mahasiswa/lulusan',
            'filter' => [
                'fakultas' => $faculty['name'],
                'tahun_lulus' => $parameter['tahun_lulus'] ?? null,
                'angkatan' => $parameter['angkatan'] ?? null,
            ],
            'total_lulus' => $graduates->count(),
            'jumlah_data_tahun_ini_semua_fakultas' => count($allGraduates),
            'kode_prodi_dihitung' => $facultyCodes,
            'rincian_per_prodi' => $byProgram,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array{name: string, aliases: array<int, string>, kode_prodi: array<int, string>}|null
     */
    private function resolveFaculty(string $query): ?array
    {
        $normalizedQuery = $this->normalizeText($query);

        foreach ($this->facultyCatalog() as $faculty) {
            $matches = collect([$faculty['name'], ...$faculty['aliases']])
                ->contains(fn (string $alias): bool => str_contains($normalizedQuery, $this->normalizeText($alias)));

            if ($matches) {
                return $faculty;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, aliases: array<int, string>, kode_prodi: array<int, string>}>
     */
    private function facultyCatalog(): array
    {
        return [
            [
                'name' => 'Kedokteran',
                'aliases' => ['fk', 'fakultas kedokteran'],
                'kode_prodi' => ['8831', '8832', '8881', '8882', '8883', '8884'],
            ],
            [
                'name' => 'Pertanian',
                'aliases' => ['fp', 'fakultas pertanian'],
                'kode_prodi' => ['4441', '4442', '4443', '4444'],
            ],
            [
                'name' => 'Hukum',
                'aliases' => ['fh', 'fakultas hukum'],
                'kode_prodi' => ['7773'],
            ],
            [
                'name' => 'Teknik',
                'aliases' => ['ft', 'fakultas teknik'],
                'kode_prodi' => ['3331', '3333', '3334', '3335', '3336', '3337'],
            ],
            [
                'name' => 'Ekonomi dan Bisnis',
                'aliases' => ['feb', 'ekonomi bisnis', 'fakultas ekonomi dan bisnis'],
                'kode_prodi' => ['5501', '5502', '5504', '5551', '5552', '5553', '5554'],
            ],
            [
                'name' => 'Ilmu Sosial dan Ilmu Politik',
                'aliases' => ['fisip', 'ilmu sosial politik', 'ilmu sosial & politik', 'ilmu sosial dan politik', 'fakultas ilmu sosial dan ilmu politik'],
                'kode_prodi' => ['6661', '6662', '6670'],
            ],
            [
                'name' => 'Keguruan dan Ilmu Pendidikan',
                'aliases' => ['fkip', 'keguruan ilmu pendidikan', 'fakultas keguruan dan ilmu pendidikan'],
                'kode_prodi' => ['2221', '2222', '2223', '2224', '2225', '2227', '2228', '2237', '2280', '2281', '2282', '2283', '2284', '2285', '2286', '2287', '2288', '2289', '2290'],
            ],
            [
                'name' => 'Pascasarjana',
                'aliases' => ['pasca', 'program pascasarjana', 'sekolah pascasarjana'],
                'kode_prodi' => ['7771', '7774', '7776', '7777', '7778', '7779', '7780', '7781', '7782', '7783', '7784', '7787', '7789'],
            ],
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
