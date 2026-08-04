<?php

namespace App\Services\Integrations;

use Exception;
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

            throw new Exception('Gagal mendapatkan token');
        });
    }

    public function makeRequest($method, $endpoint, $queryParams = [])
    {
        return $this->makeRequestWithRetry($method, $endpoint, $queryParams, $isRetry = false);
    }

    protected function makeRequestWithRetry($method, $endpoint, $queryParams, $isRetry)
    {
        try {
            $token = $this->getToken();
            $response = Http::withToken($token)
                ->timeout(30)
                ->acceptJson()
                ->{$method}("{$this->baseUrl}/{$endpoint}", $queryParams);

            // Token expired / invalid - refresh dan retry sekali
            $isTokenIssue = $response->status() === 401 ||
                ($response->status() === 500 && str_contains($response->body(), 'Server Error'));

            if ($isTokenIssue && !$isRetry) {
                Cache::forget('simantap_api_token');
                $newToken = $this->getToken();
                $response = Http::withToken($newToken)
                    ->timeout(30)
                    ->acceptJson()
                    ->{$method}("{$this->baseUrl}/{$endpoint}", $queryParams);
            }

            // Berhasil
            if ($response->successful() && $response->json() !== null) {
                return $response->json();
            }

            // Error tapi bukan token issue - kembalikan null supaya caller bisa handle
            $errorMsg = match ($response->status()) {
                429 => "Server API memblokir request karena terlalu cepat. Tunggu sebentar lalu coba lagi.",
                500, 502, 503, 504 => "Server API Simantap sedang down atau terjadi kesalahan.",
                404 => "Endpoint API tidak ditemukan.",
                522 => "Koneksi ke server API timeout. Silakan refresh halaman.",
                default => "Terjadi masalah dengan API (status: {$response->status()})."
            };

            \Log::warning('Simantap API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'message' => $errorMsg,
            ]);

            return null;

        } catch (\Exception $e) {
            \Log::warning('Simantap API exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
