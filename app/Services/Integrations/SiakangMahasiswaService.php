<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SiakangMahasiswaService extends SiakangApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.mahasiswa';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.siakang.base_url'),
            'auth_type' => 'bearer_login',
            'token' => config('services.siakang.token'),
            'cf_clearance' => config('services.siakang.cf_clearance'),
            'connect_timeout' => 5,
            'timeout' => 10,
        ];
    }

    /**
     * Ambil data mahasiswa.
     *
     * SWR dual-key: data (TTL 6 jam) + freshness flag (TTL 20 menit).
     * DB lokal (mahasiswas) dipakai saat cache miss, API selalu di-refresh background.
     */
    public function getData(array $params = []): ApiResponse
    {
        $cacheKey     = 'siakang.mahasiswa.' . md5(json_encode($params));
        $staleFlagKey = $cacheKey . '.fresh';

        // 1. Cache ada — return segera, refresh jika stale
        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);

            if (!Cache::has($staleFlagKey)) {
                $this->deferApiRefresh($cacheKey, $staleFlagKey, $params);
            }

            return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa', data: $data);
        }

        // 2. DB lokal — fallback count dari mahasiswas
        try {
            $angkatan = $params['angkatan'] ?? null;
            if (class_exists(\App\Models\Mahasiswa::class) && \App\Models\Mahasiswa::exists()) {
                $q = \App\Models\Mahasiswa::query();
                if ($angkatan) {
                    $q->where('angkatan', $angkatan);
                }
                $total = $q->count();

                if ($total > 0) {
                    $dbData = [['total' => $total, 'angkatan' => $angkatan]];
                    Cache::put($cacheKey, $dbData, now()->addHours(6));
                    $this->deferApiRefresh($cacheKey, $staleFlagKey, $params);

                    return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa', data: $dbData);
                }
            }
        } catch (\Throwable $e) {
            // Lanjut ke API blocking
        }

        // 3. API blocking — cold start
        $response = $this->get('/v2/mahasiswa', $params);
        if ($response->success && !empty($response->data)) {
            Cache::put($cacheKey, $response->data, now()->addHours(6));
            Cache::put($staleFlagKey, true, now()->addMinutes(20));
        }

        return $response;
    }

    private function deferApiRefresh(string $cacheKey, string $staleFlagKey, array $params): void
    {
        if (!function_exists('defer')) {
            return;
        }

        defer(function () use ($cacheKey, $staleFlagKey, $params) {
            try {
                $response = $this->get('/v2/mahasiswa', $params);
                if ($response->success && !empty($response->data)) {
                    Cache::put($cacheKey, $response->data, now()->addHours(6));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('SWR mahasiswa refresh gagal: ' . $e->getMessage());
            } finally {
                Cache::put($staleFlagKey, true, now()->addMinutes(20));
            }
        });
    }
}