<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;

class SiakangLulusanService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.lulusan';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.siakang.base_url'),
            'auth_type' => 'token',
            'token' => config('services.siakang.token'),
            'connect_timeout' => 10,
            'timeout' => 30,
        ];
    }

    /**
     * Ambil data ringkasan mahasiswa lulus.
     */
    public function getData(array $params = []): ApiResponse
    {
        return $this->get('/v2/mahasiswa-lulus', $params);
    }

    /**
     * Ambil daftar mahasiswa lulus (paginated).
     */
    public function getListMahasiswa(array $params = []): ApiResponse
    {
        return $this->get('/v2/mahasiswa/lulusan', $params);
    }
}