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

    public function getListMahasiswa(array $parameter): array
    {
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false || empty($token)) {
            return ['tersedia' => false, 'data' => [], 'total' => 0];
        }

        $url = rtrim($baseUrl, '/') . '/v2/mahasiswa/lulusan';

        try {
            $response = Http::connectTimeout(10)
                ->timeout(30)
                ->acceptJson()
                ->withToken($token)
                ->withHeaders([
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/') . '/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url, $parameter);

        } catch (\Exception $e) {
            return ['tersedia' => false, 'data' => [], 'total' => 0];
        }

        if (!$response->successful()) {
            return ['tersedia' => false, 'data' => [], 'total' => 0];
        }

        $hasil = $response->json();

        if (!isset($hasil['success']) || $hasil['success'] !== true) {
            return ['tersedia' => false, 'data' => [], 'total' => 0];
        }

        $paginasi = $hasil['data'][0] ?? [];

        return [
            'tersedia' => true,
            'data' => $paginasi['data'] ?? [],
            'total' => $paginasi['total'] ?? 0,
            'current_page' => $paginasi['current_page'] ?? 1,
            'per_page' => $paginasi['per_page'] ?? 15,
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
