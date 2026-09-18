<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SiakangPenjadwalanService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.penjadwalan';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.siakang.base_url'),
            'auth_type' => 'token',
            'token' => config('services.siakang.token'),
            'connect_timeout' => 5,
            'timeout' => 15,
        ];
    }

    /**
     * Ambil data penjadwalan dosen.
     *
     * SWR dual-key: data (TTL 2 jam) + freshness flag (TTL 10 menit).
     * Data per-NIP bersifat realtime, TTL lebih pendek dibanding data agregat.
     *
     * @param  array{semester: string, nip: string}  $params
     */
    public function getData(array $params = []): ApiResponse
    {
        $semester = trim((string) ($params['semester'] ?? request()->input('semester', '')));
        $nip = trim((string) ($params['nip'] ?? ''));

        if (empty($semester) || empty($nip)) {
            return new ApiResponse(
                success: false,
                status: 422,
                message: 'Parameter semester dan nip wajib ada untuk mengambil data penjadwalan dosen.',
                data: [],
            );
        }

        $params['semester'] = $semester;
        $params['nip'] = $nip;

        $cacheKey     = 'siakang.penjadwalan.' . md5(json_encode($params));
        $staleFlagKey = $cacheKey . '.fresh';

        // 1. Cache ada — return segera, refresh jika stale
        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);

            if (!Cache::has($staleFlagKey) && function_exists('defer')) {
                defer(function () use ($cacheKey, $staleFlagKey, $params) {
                    try {
                        $response = $this->get('/rencana-studi/penjadwalan', $params);
                        if ($response->success && !empty($response->data)) {
                            Cache::put($cacheKey, $response->data, now()->addHours(2));
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('SWR penjadwalan refresh gagal: ' . $e->getMessage());
                    } finally {
                        Cache::put($staleFlagKey, true, now()->addMinutes(10));
                    }
                });
            }

            return new ApiResponse(success: true, status: 200, message: 'Data penjadwalan', data: $data);
        }

        // 2. API blocking — tidak ada DB lokal untuk data penjadwalan per-NIP
        $response = $this->get('/rencana-studi/penjadwalan', $params);

        if ($response->success && !empty($response->data)) {
            Cache::put($cacheKey, $response->data, now()->addHours(2));
            Cache::put($staleFlagKey, true, now()->addMinutes(10));
        }

        return $response;
    }
}