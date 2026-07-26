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
            dd([
                'error' => 'Config tidak valid',
                'base_url' => $baseUrl,
                'is_valid_url' => filter_var($baseUrl, FILTER_VALIDATE_URL),
                'token_empty' => empty($token)
            ]);
        }

        $url = rtrim($baseUrl, '/') . '/v2/mahasiswa-aktif';

        try {
            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->acceptJson()
                ->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        ])
                ->withToken($token)
                ->get($url, $parameter);
        } catch (ConnectionException $e) {
            dd(['error' => 'Connection Exception / Timeout', 'message' => $e->getMessage()]);
        }

        // 3. DEBUG RESPONS API
        if (!$response->successful() || (string)$response->json('status') !== '200') {
            dd([
                'error' => 'Respons HTTP Gagal atau Status JSON tidak 200',
                'http_status_code' => $response->status(),
                'json_status_value' => $response->json('status'),
                'full_response_body' => $response->json() ?? $response->body(),
                'url_called' => $url,
                'param_sent' => $parameter
            ]);
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
