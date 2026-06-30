<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SiakangLulusanService
{
    /**
     * @param  array<string, int|string>  $parameter
     * @return array<string, mixed>
     */
    public function ambilData(array $parameter): array
    {
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (! is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return $this->hasilKosong();
        }

        if (! is_string($token) || $token === '') {
            return $this->hasilKosong();
        }

        $url = rtrim($baseUrl, '/').'/v2/mahasiswa/lulusan';

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->withHeaders([
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/').'/',
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                ])
                ->get($url, $parameter);
        } catch (ConnectionException) {
            return $this->hasilKosong();
        }

        if (! $response->successful()) {
            return $this->hasilKosong();
        }

        $isiResponse = $response->json();
        $hasilPagination = data_get($isiResponse, 'data.0');

        if (! is_array($hasilPagination)) {
            return $this->hasilKosong();
        }

        $dataMahasiswa = $hasilPagination['data'] ?? [];

        if (! is_array($dataMahasiswa)) {
            $dataMahasiswa = [];
        }

        return [
            'tersedia' => true,
            'data' => $dataMahasiswa,
            'total' => (int) ($hasilPagination['total'] ?? 0),
            'halaman_sekarang' => (int) ($hasilPagination['current_page'] ?? 1),
            'halaman_terakhir' => (int) ($hasilPagination['last_page'] ?? 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hasilKosong(): array
    {
        return [
            'tersedia' => false,
            'data' => [],
            'total' => 0,
            'halaman_sekarang' => 1,
            'halaman_terakhir' => 1,
        ];
    }
}
