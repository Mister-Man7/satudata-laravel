<?php

namespace App\Ai\Tools;

use App\Services\Integrations\SIPPService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchPublications implements Tool
{
    public function description(): Stringable|string
    {
        return 'Mencari publikasi karya ilmiah, penelitian, atau pengabdian dosen UNTIRTA dari SIPP berdasarkan NIP, judul, atau nama dosen.';
    }

    public function handle(Request $request): Stringable|string
    {
        $arguments = $request->all();
        $nip = trim((string)($arguments['nip'] ?? ''));
        $search = trim((string)($arguments['search'] ?? ''));

        $sipp = app(SIPPService::class);
        $params = [];
        if (!empty($nip)) {
            $params['nip'] = $nip;
        }
        if (!empty($search)) {
            $params['search'] = $search;
        }

        try {
            $res = $sipp->getPublikasi($params);
            if ($res->success && !empty($res->data)) {
                $items = is_array($res->data) ? ($res->data['data'] ?? $res->data) : [];
                $mapped = collect($items)->take(10)->map(fn($p) => [
                    'judul' => $p['judul'] ?? $p['title'] ?? null,
                    'penulis' => $p['penulis'] ?? $p['author'] ?? $p['dosen'] ?? null,
                    'tahun' => $p['tahun'] ?? $p['year'] ?? null,
                    'jurnal' => $p['jurnal'] ?? $p['publisher'] ?? null,
                    'jenis' => $p['jenis'] ?? 'Publikasi Ilmiah',
                ])->all();

                return json_encode([
                    'sumber' => 'API SIPP UNTIRTA',
                    'total_ditemukan' => count($mapped),
                    'data_publikasi' => $mapped,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        } catch (\Throwable $e) {
            // API connection error
        }

        return json_encode([
            'sumber' => 'API SIPP UNTIRTA',
            'status' => 'Data publikasi belum dapat dihubungi atau tidak ada hasil untuk filter tersebut.',
            'filter_digunakan' => [
                'nip' => $nip ?: null,
                'search' => $search ?: null,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'nip' => $schema->string()->description('NIP Dosen.')->nullable(),
            'search' => $schema->string()->description('Kata kunci judul publikasi atau nama dosen.')->nullable(),
        ];
    }
}
