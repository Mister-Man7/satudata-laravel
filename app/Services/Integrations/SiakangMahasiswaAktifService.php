<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SiakangMahasiswaAktifService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.mahasiswa_aktif';
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
     * Ambil data mahasiswa aktif (cached).
     */
    public function getData(array $params = []): ApiResponse
    {
        $cacheKey = 'siakang.mahasiswa_aktif.' . md5(json_encode($params));

        if (Cache::has($cacheKey)) {
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: Cache::get($cacheKey),
            );
        }

        $response = $this->get('/v2/mahasiswa-aktif', $params);

        if ($response->success) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(10));
        }

        return $response;
    }
}