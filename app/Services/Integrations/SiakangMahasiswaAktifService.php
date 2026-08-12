<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;

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
            'connect_timeout' => 10,
            'timeout' => 90,
        ];
    }

    /**
     * Ambil data mahasiswa aktif.
     */
    public function getData(array $params = []): ApiResponse
    {
        return $this->get('/v2/mahasiswa-aktif', $params);
    }
}