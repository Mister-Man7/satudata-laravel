<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dasar untuk semua service API SIAKANG.
 *
 * Token bearer diambil otomatis dari endpoint login memakai kredensial di .env
 * lalu di-cache, sehingga tidak perlu diperbarui manual. Bila server menolak
 * token (401), cache token dibuang dan permintaan dicoba ulang sekali.
 *
 * Subclass cukup mengimplementasikan config() dan serviceName().
 */
abstract class SiakangApiClient extends AbstractApiClient
{
    /**
     * Ambil bearer token dari endpoint login SIAKANG, di-cache 50 menit.
     */
    protected function getBearerToken(array $config): string
    {
        return Cache::remember('siakang_bearer_token', now()->addMinutes(50), function () use ($config) {
            $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
            $username = config('services.siakang.username');
            $password = config('services.siakang.password');
            $fallbackToken = config('services.siakang.token');

            if ($baseUrl === '' || empty($username) || empty($password)) {
                if (!empty($fallbackToken)) {
                    return $fallbackToken;
                }

                Log::error('Siakang: base_url, username, atau password belum diatur di .env');
                throw new \RuntimeException('Konfigurasi Siakang belum lengkap.');
            }

            $headers = [
                'Accept' => 'application/json',
                'User-Agent' => $this->userAgent(),
            ];

            $cookie = $this->cloudflareCookie($config);
            if ($cookie !== null) {
                $headers['Cookie'] = $cookie;
            }

            // base_url bisa sudah berisi /api, jadi path login menyesuaikan.
            $loginUrl = str_ends_with($baseUrl, '/api')
                ? $baseUrl . '/request-token'
                : $baseUrl . '/api/request-token';

            try {
                $response = Http::timeout(20)->withHeaders($headers)->post($loginUrl, [
                    'username' => $username,
                    'password' => $password,
                ]);

                if ($response->successful()) {
                    $token = $response->json('data.access_token') ?? $response->json('access_token');

                    if (!empty($token)) {
                        return (string) $token;
                    }
                }

                Log::warning('Siakang: login tidak mengembalikan token', ['status' => $response->status()]);
            } catch (\Throwable $e) {
                Log::warning('Siakang: login error, memakai token fallback: ' . $e->getMessage());
            }

            if (!empty($fallbackToken)) {
                return $fallbackToken;
            }

            throw new \RuntimeException('Login ke Siakang gagal.');
        });
    }

    /**
     * Ulangi sekali dengan token baru bila server menolak token (401).
     */
    protected function request(string $method, string $endpoint, array $payload = []): ApiResponse
    {
        $response = parent::request($method, $endpoint, $payload);

        if ($response->status === 401) {
            Cache::forget('siakang_bearer_token');
            Log::info('Siakang: token ditolak, mencoba ulang dengan token baru');

            $response = parent::request($method, $endpoint, $payload);
        }

        return $response;
    }
}
