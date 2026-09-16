<?php

namespace App\Ai\Tools;

use App\Models\Pegawai;
use App\Services\Integrations\SimpegPegawaiService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchPegawai implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mencari data pegawai atau dosen UNTIRTA berdasarkan NIP, nama, unit kerja, atau jabatan kepegawaian.';
    }

    public function handle(Request $request): Stringable|string
    {
        $arguments = $request->all();
        $queryText = trim((string)($arguments['search'] ?? ''));
        $unitKerja = trim((string)($arguments['unit_kerja'] ?? ''));

        $simpegService = app(SimpegPegawaiService::class);
        $res = $simpegService->getData();

        if ($res->success && is_array($res->data) && count($res->data) > 0) {
            $collection = collect($res->data);

            if (!empty($queryText)) {
                $qLower = mb_strtolower($queryText);
                $collection = $collection->filter(function ($p) use ($qLower) {
                    $nama = mb_strtolower($p['nama'] ?? $p['namaPegawai'] ?? '');
                    $nip = mb_strtolower($p['nip'] ?? '');
                    return str_contains($nama, $qLower) || str_contains($nip, $qLower);
                });
            }

            if (!empty($unitKerja)) {
                $uLower = mb_strtolower($unitKerja);
                $collection = $collection->filter(function ($p) use ($uLower) {
                    $unit = mb_strtolower($p['unitKerja'] ?? '');
                    return str_contains($unit, $uLower);
                });
            }

            $mapped = $collection->take(10)->map(fn($p) => [
                'nama' => trim(($p['gelarDepan'] ?? '').' '.($p['nama'] ?? $p['namaPegawai'] ?? '').' '.($p['gelarBelakang'] ?? '')),
                'nip' => $p['nip'] ?? null,
                'unit_kerja' => $p['unitKerja'] ?? null,
                'jabatan' => $p['jabatan'] ?? null,
                'status_kerja' => $p['statusKerja'] ?? null,
                'level' => $p['levelPegawai'] ?? null,
                'email' => $p['email'] ?? $p['emailPegawai'] ?? null,
            ])->values()->all();

            return json_encode([
                'sumber' => 'API SIMPEG UNTIRTA',
                'total_ditemukan' => count($mapped),
                'data_pegawai' => $mapped,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        // Local DB Fallback
        try {
            $q = Pegawai::query();
            if (!empty($queryText)) {
                $q->where(function ($sub) use ($queryText) {
                    $sub->where('nama', 'like', "%{$queryText}%")
                        ->orWhere('nip', 'like', "%{$queryText}%");
                });
            }
            if (!empty($unitKerja)) {
                $q->where('unit_kerja', 'like', "%{$unitKerja}%");
            }

            $list = $q->limit(10)->get();
            $mapped = $list->map(fn($p) => [
                'nama' => trim(($p->gelar_depan ? $p->gelar_depan.' ' : '').$p->nama.($p->gelar_belakang ? ', '.$p->gelar_belakang : '')),
                'nip' => $p->nip,
                'unit_kerja' => $p->unit_kerja,
                'jabatan' => $p->jabatan,
                'status_kerja' => $p->status_kerja,
                'level' => $p->level_pegawai,
                'email' => $p->email,
            ])->all();

            return json_encode([
                'sumber' => 'Database Lokal Pegawais',
                'total_ditemukan' => count($mapped),
                'data_pegawai' => $mapped,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return json_encode([
                'error' => 'Gagal mencari data pegawai: ' . $e->getMessage(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Nama atau NIP pegawai/dosen.')->nullable(),
            'unit_kerja' => $schema->string()->description('Nama unit kerja atau fakultas.')->nullable(),
        ];
    }
}
