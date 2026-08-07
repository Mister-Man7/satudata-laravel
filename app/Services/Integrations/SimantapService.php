<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimantapService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'simantap';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.simantap.base_url'),
            'auth_type' => 'bearer_login',
            'connect_timeout' => 10,
            'timeout' => 30,
        ];
    }

    /**
     * Ambil bearer token via login, di-cache selama 60 menit.
     *
     * @throws \RuntimeException jika login gagal
     */
    protected function getBearerToken(array $config): string
    {
        return Cache::remember('simantap_api_token', now()->addMinutes(60), function () use ($config) {
            $email = config('services.simantap.email');
            $password = config('services.simantap.password');

            if (empty($config['base_url']) || empty($email) || empty($password)) {
                Log::error('Simantap: Konfigurasi belum lengkap');
                throw new \RuntimeException('Konfigurasi Simantap belum lengkap.');
            }

            $response = Http::post(rtrim($config['base_url'], '/') . '/auth/login', [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->failed()) {
                Log::error('Simantap: Gagal login', ['status' => $response->status()]);
                throw new \RuntimeException('Gagal login ke Simantap.');
            }

            $data = $response->json();

            if (!isset($data['success']) || $data['success'] !== true) {
                Log::error('Simantap: Login gagal', ['response' => $data]);
                throw new \RuntimeException('Login ke Simantap gagal.');
            }

            $token = $data['data']['token'] ?? null;

            if (!$token) {
                Log::error('Simantap: Token tidak ditemukan');
                throw new \RuntimeException('Token Simantap tidak ditemukan.');
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
            Cache::forget('simantap_api_token');
            Log::info('Simantap: Token expired, retry dengan token baru');
            return parent::request($method, $endpoint, $payload);
        }

        return $response;
    }

    /**
     * Kirim request dengan method dinamis (backward compatible).
     *
     * @deprecated Gunakan get(), post(), put(), delete() secara langsung
     */
    public function makeRequest(string $method, string $endpoint, array $params = []): ?array
    {
        $response = match (strtolower($method)) {
            'get' => $this->get($endpoint, $params),
            'post' => $this->post($endpoint, $params),
            default => ApiResponse::unexpectedError("Method '{$method}' belum didukung."),
        };

        return $response->success ? $response->data : null;
    }
}