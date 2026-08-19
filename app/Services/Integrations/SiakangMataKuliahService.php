<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;

class SiakangMataKuliahService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.mata_kuliah';
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
     * Get mata kuliah tingkat universitas.
     */
    public function getMataKuliahTingkatUniversitas(): ApiResponse
    {
        return $this->get('/v2/mata_kuliah/tingkat-universitas');
    }

    /**
     * Get mata kuliah tingkat fakultas.
     */
    public function getMataKuliahTingkatFakultas(string $kodeFakultas): ApiResponse
    {
        return $this->get('/v2/mata_kuliah/tingkat-fakultas', ['kode_fakultas' => $kodeFakultas]);
    }

    /**
     * Get mata kuliah tingkat prodi.
     */
    public function getMataKuliahTingkatProdi(string $kodeProdi): ApiResponse
    {
        return $this->get('/v2/mata_kuliah/tingkat-prodi', ['kode_prodi' => $kodeProdi]);
    }
}