<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SimpegPegawaiService
{

    public function getData(array $parameter = []): array
    {
        $cacheKey = $this->buildCacheKey($parameter);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

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
            $response = Http::connectTimeout(3)
                ->timeout(8)
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

        if (!is_array($isiResponse)) {
            return $this->noResult();
        }

        if (isset($isiResponse['total']) && is_numeric($isiResponse['total'])) {
            $totalData = (int)$isiResponse['total'];
            $dataPegawai = $isiResponse['data'] ?? [];
        } elseif (isset($isiResponse['data']) && is_array($isiResponse['data'])) {
            $dataPegawai = $isiResponse['data'];
            $totalData = count($dataPegawai);
        } else {
            return $this->noResult();
        }

        $result = [
            'status' => true,
            'message' => 'Berhasil mengambil data pegawai',
            'data' => $dataPegawai,
            'total' => $totalData,
            'halaman_sekarang' => 1,
            'halaman_terakhir' => 1,
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(5));

        return $result;
    }

    private function buildCacheKey(array $parameter = []): string
    {
        ksort($parameter);

        return 'simpeg_pegawai_' . md5(json_encode($parameter));
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
