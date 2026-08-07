<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SimpegPegawaiService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'simpeg.pegawai';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.simpeg.base_url'),
            'auth_type' => 'api_key',
            'api_key_header' => config('services.simpeg.key', 'simpeg2023'),
            'api_key_value' => config('services.simpeg.value'),
            'connect_timeout' => 5,
            'timeout' => 15,
        ];
    }

    /**
     * Ambil data pegawai (cached).
     */
    public function getData(array $params = []): ApiResponse
    {
        $cacheKey = 'simpeg_pegawai_' . md5(json_encode($params));

        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: $cachedData,
            );
        }

        $response = $this->get('/employee', $params);

        if ($response->success) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(5));
        }

        return $response;
    }

    /**
     * Ambil data dosen (cached).
     */
    public function getDataDosen(string $endpoint = 'pegawai', array $params = []): ApiResponse
    {
        $cacheKey = 'simpeg_dosen_' . md5($endpoint . json_encode($params));

        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: $cachedData,
            );
        }

        $response = $this->get('/' . ltrim($endpoint, '/'), $params);

        if ($response->success) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(5));
        }

        return $response;
    }
}