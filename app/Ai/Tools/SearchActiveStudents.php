<?php

namespace App\Ai\Tools;

use App\Models\Mahasiswa;
use App\Services\Integrations\SiakangMahasiswaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchActiveStudents implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mencari data mahasiswa aktif UNTIRTA berdasarkan nama, NIM/NPM, angkatan, atau program studi.';
    }

    public function handle(Request $request): Stringable|string
    {
        $arguments = $request->all();
        $queryText = trim((string)($arguments['search'] ?? ''));
        $angkatan = $arguments['angkatan'] ?? null;
        $page = (int)($arguments['page'] ?? 1);

        $mahasiswaService = app(SiakangMahasiswaService::class);
        $apiParams = [
            'page' => $page,
            'limit' => 10,
        ];
        if (!empty($queryText)) {
            $apiParams['search'] = $queryText;
        }
        if (!empty($angkatan)) {
            $apiParams['angkatan'] = (int)$angkatan;
        }

        $res = $mahasiswaService->getData($apiParams);

        if ($res->success && !empty($res->data)) {
            $items = is_array($res->data) ? ($res->data['data'] ?? $res->data) : [];
            $mapped = collect($items)->take(10)->map(fn($m) => [
                'nama' => $m['nama'] ?? $m['nama_mahasiswa'] ?? null,
                'nim' => $m['nim'] ?? $m['npm'] ?? null,
                'prodi' => data_get($m, 'prodi.nama_prodi_lengkap') ?? data_get($m, 'prodi.nama_prodi') ?? $m['nama_prodi'] ?? null,
                'angkatan' => $m['angkatan'] ?? null,
                'status' => $m['status_mahasiswa'] ?? 'Aktif',
            ])->all();

            return json_encode([
                'sources' => 'API SIAKANG /v2/mahasiswa',
                'total_ditemukan' => count($mapped),
                'data_mahasiswa' => $mapped,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        // Local DB Fallback
        try {
            $q = Mahasiswa::query()->with('prodi');
            if (!empty($queryText)) {
                $q->where(function ($sub) use ($queryText) {
                    $sub->where('nama', 'like', "%{$queryText}%")
                        ->orWhere('nim', 'like', "%{$queryText}%");
                });
            }
            if (!empty($angkatan)) {
                $q->where('angkatan', (int)$angkatan);
            }

            $list = $q->limit(10)->get();
            $mapped = $list->map(fn($m) => [
                'nama' => $m->nama,
                'nim' => $m->nim,
                'prodi' => $m->prodi->nama_prodi ?? 'N/A',
                'angkatan' => $m->angkatan,
                'status' => $m->status ?? 'Aktif',
            ])->all();

            return json_encode([
                'sources' => 'Database Lokal Satudata',
                'total_ditemukan' => count($mapped),
                'data_mahasiswa' => $mapped,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return json_encode([
                'error' => 'Gagal melakukan pencarian mahasiswa aktif: ' . $e->getMessage(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Kata kunci pencarian (Nama atau NIM/NPM mahasiswa).')->nullable(),
            'angkatan' => $schema->integer()->description('Tahun angkatan masuk (contoh: 2023).')->nullable(),
            'page' => $schema->integer()->description('Halaman hasil pencarian (default 1).')->nullable(),
        ];
    }
}
