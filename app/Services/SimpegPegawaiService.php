<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SimpegPegawaiService
{

    public function getData(array $parameter = []): array
    {
        $baseUrl = config('services.simpeg.base_url');
        $apiKeyHeader = config('services.simpeg.key');
        $apiKeyValue = config('services.simpeg.value');

        if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return $this->noResult();
        }

        if (!is_string($apiKeyHeader) || $apiKeyHeader === '') {
            return $this->noResult();
        }

        if (!is_string($apiKeyValue) || $apiKeyValue === '') {
            return $this->noResult();
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(30)
                ->acceptJson()
                ->withHeaders([
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/') . '/',
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                    $apiKeyHeader => $apiKeyValue,
                ])
                ->get($baseUrl, $parameter);
        } catch (ConnectionException) {
            return $this->noResult();
        }

        if (!$response->successful()) {
            return $this->noResult();
        }

        $isiResponse = $response->json();

        if (!is_array($isiResponse) || !isset($isiResponse['data']) || !is_array($isiResponse['data'])) {
            return $this->noResult();
        }

        $dataPegawai = $isiResponse['data'];
        $totalData = count($dataPegawai);

        return [
            'status' => true,
            'message' => 'Berhasil mengambil data pegawai',
            'data' => $dataPegawai,
            'total' => $totalData,
            'halaman_sekarang' => 1,
            'halaman_terakhir' => 1,
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
