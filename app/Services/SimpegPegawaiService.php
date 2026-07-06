<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SimpegPegawaiService
{

    public function getData(array $parameter): array
    {
        $baseUrl = config('services.simpeg.base_url');
        $apiKeyHeader = config('services.simpeg.key');
        $apiKeyValue = config('services.simpeg.value');

        if (! is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return $this->noResult();
        }

        if (! is_string($apiKeyHeader) || $apiKeyHeader === '') {
            return $this->noResult();
        }

        if (! is_string($apiKeyValue) || $apiKeyValue === '') {
            return $this->noResult();
        }

        $url = $baseUrl;

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->withHeaders([
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/') . '/',
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                    $apiKeyHeader => $apiKeyValue,
                ])
                ->get($url, $parameter);
        } catch (ConnectionException) {
            return $this->noResult();
        }

        if (! $response->successful()) {
            return $this->noResult();
        }

        $isiResponse = $response->json();

        if (! is_array($isiResponse)) {
            return $this->noResult();
        }

        $pageResults = data_get($isiResponse, 'data.0');

        if (is_array($pageResults)) {
            $dataPegawai = $pageResults['data'] ?? [];

            if (! is_array($dataPegawai)) {
                $dataPegawai = [];
            }

            return [
                'status' => true,
                'message' => (string) ($isiResponse['message'] ?? 'Berhasil mengambil data pegawai'),
                'data' => $dataPegawai,
                'total' => (int) ($pageResults['total'] ?? 0),
                'halaman_sekarang' => (int) ($pageResults['current_page'] ?? 1),
                'halaman_terakhir' => (int) ($pageResults['last_page'] ?? 1),
            ];
        }

        $dataPegawai = $isiResponse['data'] ?? [];

        if (! is_array($dataPegawai)) {
            $dataPegawai = [];
        }

        $status = $isiResponse['status'] ?? null;

        if (is_bool($status)) {
            $tersedia = $status;
        } elseif (is_numeric($status)) {
            $tersedia = (int) $status === 1;
        } else {
            $tersedia = true;
        }

        return [
            'status' => $tersedia,
            'message' => (string) ($isiResponse['message'] ?? ($tersedia ? 'Berhasil mengambil data pegawai' : 'Data pegawai tidak tersedia')),
            'data' => $dataPegawai,
            'total' => (int) ($isiResponse['total'] ?? 0),
            'halaman_sekarang' => (int) ($isiResponse['current_page'] ?? 1),
            'halaman_terakhir' => (int) ($isiResponse['last_page'] ?? 1),
        ];
    }

    public function noResult(): array
    {
        return [
            'status' => false,
            'message' => 'Data pegawai tidak tersedia',
            'data' => [],
            'total' => 0,
            'halaman_sekarang' => 1,
            'halaman_terakhir' => 1,
        ];
    }
}
