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


        if ($response->json() === null) {
            dd([
                'url_yg_ditembak' => "{$this->baseUrl}/{$endpoint}",
                'status_code' => $response->status(),
                'isi_balasan' => $response->body() // Menampilkan pesan error asli (mungkin HTML/Teks)
            ]);
        }

        return $response->json();
    }
}
