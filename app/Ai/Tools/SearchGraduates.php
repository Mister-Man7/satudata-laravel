<?php

namespace App\Ai\Tools;

use App\Services\SiakangLulusanService;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class SearchGraduates implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Mencari atau memfilter data mahasiswa yang telah lulus (alumni) berdasarkan kata kunci nama/NPM, kode program studi, angkatan masuk, atau tahun kelulusan.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $lulusanService = app(SiakangLulusanService::class);

        $parameter = [];

        if (filled($request['search'])) {
            $parameter['search'] = (string) $request['search'];
        }

        if (filled($request['kode_prodi'])) {
            $parameter['kode_prodi'] = (string) $request['kode_prodi'];
        }

        if (filled($request['angkatan'])) {
            $parameter['angkatan'] = (int) $request['angkatan'];
        }

        if (filled($request['tahun_lulus'])) {
            $parameter['tahun_lulus'] = (int) $request['tahun_lulus'];
        }

        if (filled($request['page'])) {
            $parameter['page'] = (int) $request['page'];
        } else {
            $parameter['page'] = 1;
        }

        $parameter['limit'] = 10; // limit to 10 for chatbot usage to keep token size reasonable

        $hasilApi = $lulusanService->ambilData($parameter);

        if (!$hasilApi['tersedia']) {
            return json_encode([
                'error' => 'API SIAKANG tidak tersedia untuk melakukan pencarian lulusan saat ini.',
            ]);
        }

        // Clean up the data returned to only essential fields to save token space
        $daftarMahasiswa = collect($hasilApi['data'])->map(fn ($mhs) => [
            'nama' => $mhs['nama'] ?? null,
            'npm' => $mhs['npm'] ?? null,
            'prodi' => $mhs['prodi'] ?? null,
            'angkatan' => $mhs['angkatan'] ?? null,
            'tahun_lulus' => $mhs['tahun_lulus'] ?? null,
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
        ];
    }
}
