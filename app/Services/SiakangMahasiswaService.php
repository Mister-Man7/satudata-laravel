<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SiakangMahasiswaService
{
    public function getData(array $parameter = []): array
    {
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false || empty($token)) {
            return $this->noResult('Config base_url atau token di .env masih kosong.');
        }

        $url = rtrim($baseUrl, '/') . '/v2/mahasiswa';

        try {
            $response = Http::connectTimeout(5)
                ->timeout(30)
                ->acceptJson()
                ->withToken($token)
                ->withHeaders([
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/') . '/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                ])
                ->get($url, $parameter);
        } catch (ConnectionException $e) {
            return $this->noResult('Koneksi ke server SIAKANG timeout atau gagal.');
        }

        if (!$response->successful()) {
            return $this->noResult('Server SIAKANG mengembalikan error HTTP: ' . $response->status());
        }

        $isiResponse = $response->json();

        if (!is_array($isiResponse)) {
            return $this->noResult('Format balasan dari server bukan JSON.');
        }

        $status = $isiResponse['success'] ?? $isiResponse['status'] ?? false;
        if (is_string($status)) {
            $status = strtolower($status) === 'true' || $status === '200' || strtolower($status) === 'success';
        }

        if (!$status) {
            return $this->noResult($isiResponse['message'] ?? 'API merespons dengan status gagal.');
        }

        $paginasi = $isiResponse['data'][0] ?? [];

        $dataMahasiswa = $paginasi['data'] ?? [];

        if (!is_array($dataMahasiswa) || empty($dataMahasiswa)) {
            return $this->noResult('Data mahasiswa di halaman ini kosong.');
        }

        $totalData = (int)($paginasi['total'] ?? count($dataMahasiswa));
        $limitPerPage = (int)($parameter['limit'] ?? $parameter['per_page'] ?? 100);
        $halamanTerakhir = (int)($paginasi['last_page'] ?? ceil(max(1, $totalData) / max(1, $limitPerPage)));

        return [
            'status' => true,
            'message' => (string)($isiResponse['message'] ?? 'Success'),
            'data' => $dataMahasiswa,
            'total' => $totalData,
            'halaman_sekarang' => (int)($paginasi['current_page'] ?? $parameter['page'] ?? 1),
            'halaman_terakhir' => max(1, $halamanTerakhir),
        ];
    }

    public function noResult(string $pesan = 'Data mahasiswa tidak tersedia'): array
    {
        return [
            'status' => false,
            'message' => $pesan,
            'data' => [],
            'total' => 0,
            'halaman_sekarang' => 1,
            'halaman_terakhir' => 1,
        ];
    }
}
