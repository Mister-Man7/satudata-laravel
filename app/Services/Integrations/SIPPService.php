<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SIPPService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'sipp';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.sipp.base_url'),
            'auth_type' => 'bearer_login',
            'connect_timeout' => 10,
            'timeout' => 30,
        ];
    }

    /**
     * Override buildRequest untuk menambahkan Referer header.
     */
    protected function buildRequest(array $config): \Illuminate\Http\Client\PendingRequest
    {
        $request = parent::buildRequest($config);

        if (!empty($config['base_url'])) {
            $request = $request->withHeaders([
                'Referer' => rtrim($config['base_url'], '/') . '/',
            ]);
        }

        return $request;
    }

    /**
     * Ambil bearer token via login, di-cache selama 50 menit.
     *
     * @throws \RuntimeException jika konfigurasi tidak lengkap atau login gagal
     */
    protected function getBearerToken(array $config): string
    {
        return Cache::remember('sipp_bearer_token', now()->addMinutes(50), function () use ($config) {
            $baseUrl = $config['base_url'] ?? '';
            $username = config('services.sipp.username');
            $password = config('services.sipp.password');

            if (empty($baseUrl) || empty($username) || empty($password)) {
                Log::error('SIPP: Konfigurasi base_url, username, atau password belum diatur.');
                throw new \RuntimeException('Konfigurasi SIPP belum lengkap.');
            }

            $response = \Illuminate\Support\Facades\Http::post(
                rtrim($baseUrl, '/') . '/api/request-token',
                [
                    'username' => $username,
                    'password' => $password,
                ]
            );

            if ($response->failed()) {
                Log::error('SIPP: Gagal mengambil token', [
                    'status' => $response->status(),
                ]);
                throw new \RuntimeException('Gagal mengambil token SIPP.');
            }

            $data = $response->json();
            $token = $data['data']['access_token'] ?? $data['access_token'] ?? $data['token'] ?? null;

            if (!$token) {
                Log::error('SIPP: Token tidak ditemukan dalam response');
                throw new \RuntimeException('Token SIPP tidak ditemukan.');
            }

            return $token;
        });
    }

    /**
     * Override request untuk handle token expired (401) → refresh & retry sekali.
     */
    protected function request(string $method, string $endpoint, array $payload = []): ApiResponse
    {
        $response = parent::request($method, $endpoint, $payload);

        if ($response->status === 401) {
            Cache::forget('sipp_bearer_token');
            Log::info('SIPP: Token expired, retry dengan token baru');
            return parent::request($method, $endpoint, $payload);
        }

        return $response;
    }

    /**
     * Ambil data publikasi.
     */
    public function getPublikasi(array $params = []): ApiResponse
    {
        return $this->get('/api/publikasi', $params);
    }

    /**
     * Ambil data penelitian.
     */
    public function getPenelitian(array $params = []): ApiResponse
    {
        return $this->get('/api/penelitian', $params);
    }
}