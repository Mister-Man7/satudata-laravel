<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SIPPService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim((string)config('services.sipp.base_url'), '/');
        $this->username = (string)config('services.sipp.username');
        $this->password = (string)config('services.sipp.password');
    }

    public function getToken(): string
    {
        return Cache::remember('sipp_bearer_token', now()->addMinutes(50), function () {
            if (empty($this->baseUrl) || empty($this->username) || empty($this->password)) {
                throw new Exception("Konfigurasi SIPP (Base URL, Username, atau Password) belum diatur di .env / config.");
            }

            $response = Http::post("{$this->baseUrl}/api/request-token", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->failed()) {
                throw new Exception('Gagal mengambil token (Status ' . $response->status() . '): ' . $response->body());
            }

            $data = $response->json();

            $token = $data['data']['access_token'] ?? $data['access_token'] ?? $data['token'] ?? null;

            if (!$token) {
                throw new Exception("Token tidak ditemukan. Response dari server: " . json_encode($data));
            }

            return $token;
        });
    }

    public function get(string $endpoint, array $queryParams = []): array
    {
        $token = $this->getToken();

        $url = "{$this->baseUrl}/" . ltrim($endpoint, '/');

        $response = Http::withToken($token)->get($url, $queryParams);

        if ($response->status() === 401) {
            Cache::forget('sipp_bearer_token');
            throw new Exception("Token SIPP expired atau tidak valid. Silakan coba lagi.");
        }

        if ($response->failed()) {
            throw new Exception("API SIPP Error ({$response->status()}): " . $response->body());
        }

        return $response->json();
    }

    public function getPublikasi(array $params = []): array
    {
        return $this->get('api/publikasi', $params);
    }

    public function getPenelitian(array $params = []): array
    {
        return $this->get('api/penelitian', $params);
    }

}
