<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SimantapService
{
    protected $baseUrl;
    protected $credentials;

    public function __construct()
    {
        $this->baseUrl = config('services.simantap.base_url');
        $this->credentials = [
            'email' => config('services.simantap.email'),
            'password' => config('services.simantap.password'),
        ];
    }

    protected function getToken()
    {
        return Cache::remember('simantap_api_token', 3600, function () {
            $response = Http::post("{$this->baseUrl}/auth/login", $this->credentials);

            if ($response->successful()) {
                $json = $response->json();
                if (isset($json['success']) && $json['success'] === true) {
                    return $json['data']['token'];
                }
            }

            throw new \Exception('Gagal mendapatkan token');
        });
    }

    public function makeRequest($method, $endpoint, $queryParams = [])
    {
        $token = $this->getToken();

        $response = Http::withToken($token)->acceptJson()->{$method}("{$this->baseUrl}/{$endpoint}", $queryParams);

        if ($response->status() === 401) {

            Cache::forget('simantap_api_token');

            $newToken = $this->getToken();
            $response = Http::withToken($newToken)->acceptJson()->{$method}("{$this->baseUrl}/{$endpoint}", $queryParams);
        }

        if (!$response->successful() || $response->json() === null) {
            $errorMsg = match ($response->status()) {
                429 => "Server API memblokir request karena kamu terlalu cepat me-refresh (Rate Limit). Tunggu sebentar lalu coba lagi.",
                500, 502, 503, 504 => "Server API Simantap sedang down atau terlalu berat. Coba lagi nanti.",
                404 => "Endpoint API tidak ditemukan.",
                default => "Terjadi masalah tidak dikenal dengan API."
            };
            dd([
                'KETERANGAN' => $errorMsg,
                'STATUS' => $response->status(),
                'URL' => "{$this->baseUrl}/{$endpoint}",
                'RESPONSE' => $response->body()
            ]);
        }

        return $response->json();
    }
}
