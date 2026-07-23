<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SiakangLulusanService
{
    public function getData(array $parameter): array
    {
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return $this->hasilKosong();
        }

        if (!is_string($token) || $token === '') {
            return $this->hasilKosong();
        }

        $url = rtrim($baseUrl, '/') . '/v2/mahasiswa-lulus';

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->withHeaders([
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/') . '/',
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                ])
                ->get($url, $parameter);

        } catch (ConnectionException) {
            return $this->hasilKosong();
        }

        if (!$response->successful()) {
            return $this->hasilKosong();
        }

        $isiResponse = $response->json();

        if (!isset($isiResponse['status']) || (string)$isiResponse['status'] !== '200') {
            return $this->hasilKosong();
        }

        $dataPayload = $isiResponse['data'] ?? [];

        return [
            'tersedia' => true,
            'total_mahasiswa_lulus' => (int)($dataPayload['total_mahasiswa_lulus'] ?? 0),
            'detail_per_fakultas' => $dataPayload['detail_per_fakultas'] ?? [],
            'detail_per_prodi' => $dataPayload['detail_per_prodi'] ?? [],
        ];
    }

    private function hasilKosong(): array
    {
        return [
            'tersedia' => false,
            'total_mahasiswa_lulus' => 0,
            'detail_per_fakultas' => [],
            'detail_per_prodi' => [],
        ];
    }
}
