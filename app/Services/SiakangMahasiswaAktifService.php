<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SiakangMahasiswaAktifService
{
    public function getData(array $parameter = []): array
    {
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false || empty($token)) {
            return $this->hasilKosong();
        }

        $url = rtrim($baseUrl, '/') . '/v2/mahasiswa-aktif';

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->get($url, $parameter);
        } catch (ConnectionException) {
            return $this->hasilKosong();
        }

        if (!$response->successful() || (string)$response->json('status') !== '200') {
            return $this->hasilKosong();
        }

        $data = $response->json('data', []);

        return [
            'status' => true,
            'message' => $response->json('message', ''),
            'angkatan' => $data['angkatan'] ?? null,
            'total_mahasiswa' => (int)($data['total_mahasiswa_aktif'] ?? 0),
            'total_laki_laki' => (int)($data['total_laki_laki'] ?? 0),
            'total_perempuan' => (int)($data['total_perempuan'] ?? 0),
            'detail_per_fakultas' => $data['detail_per_fakultas'] ?? [],
            'detail_per_prodi' => $data['detail_per_prodi'] ?? [],
        ];
    }

    private function hasilKosong(): array
    {
        return [
            'status' => false,
            'message' => 'API tidak tersedia',
            'total_mahasiswa' => 0,
            'detail_per_fakultas' => [],
            'detail_per_prodi' => [],
        ];
    }
}
