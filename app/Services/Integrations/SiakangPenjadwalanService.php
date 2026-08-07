<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;

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
            'connect_timeout' => 10,
            'timeout' => 60,
        ];
    }

    /**
     * Ambil data penjadwalan dosen.
     *
     * @param  array{semester?: string, nip?: string}  $params
     */
    public function getData(array $params = []): ApiResponse
    {
        return $this->get('/v2/rencana-studi/penjadwalan', $params);
    }
}