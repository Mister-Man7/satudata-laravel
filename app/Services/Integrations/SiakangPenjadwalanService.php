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
     * Ambil data penjadwalan dosen (cached).
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

        $cacheKey = 'siakang.penjadwalan.' . md5(json_encode($params));

        if (Cache::has($cacheKey)) {
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: Cache::get($cacheKey),
            );
        }

        $response = $this->get('/rencana-studi/penjadwalan', $params);

        if ($response->success) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(10));
        }

        return $response;
    }
}