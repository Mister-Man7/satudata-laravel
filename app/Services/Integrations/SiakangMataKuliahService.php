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
    public function getMataKuliahTingkatUniversitas(array $params = []): ApiResponse
    {
        return $this->get('/v2/mata_kuliah/tingkat-universitas', $params);
    }

    /**
     * Get mata kuliah tingkat fakultas.
     */
    public function getMataKuliahTingkatFakultas(string $kodeFakultas, array $params = []): ApiResponse
    {
        return $this->get('/v2/mata_kuliah/tingkat-fakultas', array_merge(['kode_fakultas' => $kodeFakultas], $params));
    }

    /**
     * Get mata kuliah tingkat prodi.
     */
    public function getMataKuliahTingkatProdi(string $kodeProdi, array $params = []): ApiResponse
    {
        return $this->get('/v2/mata_kuliah/tingkat-prodi', array_merge(['kode_prodi' => $kodeProdi], $params));
    }

    /**
     * Fetch all items across all pages for a given endpoint.
     */
    public function getAllItems(string $endpoint, array $params = []): array
    {
        $items = [];
        $page = 1;
        do {
            $queryParams = array_merge($params, ['page' => $page]);
            $res = $this->get($endpoint, $queryParams);
            if (!$res->success) break;

            $raw = $res->data ?? [];
            $meta = isset($raw[0]) ? $raw[0] : $raw;
            $data = $meta['data'] ?? [];

            if (empty($data)) break;

            foreach ($data as $item) {
                $key = $item['id'] ?? ($item['id_unit'] ?? ($item['kode_mata_kuliah'] ?? null));
                if ($key) {
                    $items[$key] = $item;
                } else {
                    $items[] = $item;
                }
            }

            $lastPage = (int)($meta['last_page'] ?? $page);
            $page++;
        } while ($page <= $lastPage);

        return array_values($items);
    }

    /**
     * Get ALL mata kuliah tingkat universitas (all pages).
     */
    public function getAllMataKuliahTingkatUniversitas(): array
    {
        return $this->getAllItems('/v2/mata_kuliah/tingkat-universitas');
    }

    /**
     * Get ALL mata kuliah tingkat fakultas (all pages).
     */
    public function getAllMataKuliahTingkatFakultas(string $kodeFakultas): array
    {
        return $this->getAllItems('/v2/mata_kuliah/tingkat-fakultas', ['kode_fakultas' => $kodeFakultas]);
    }

    /**
     * Get ALL mata kuliah tingkat prodi (all pages).
     */
    public function getAllMataKuliahTingkatProdi(string $kodeProdi): array
    {
        return $this->getAllItems('/v2/mata_kuliah/tingkat-prodi', ['kode_prodi' => $kodeProdi]);
    }

    /**
     * Get ALL units / prodi reference list from API /v2/unit.
     */
    public function getAllUnits(): array
    {
        $res = $this->get('/v2/unit');
        if (!$res->success) return [];
        $raw = $res->data ?? [];
        if (isset($raw[0]) && is_array($raw[0])) {
            return isset($raw[0]['data']) ? $raw[0]['data'] : $raw[0];
        }
        return isset($raw['data']) ? $raw['data'] : $raw;
    }
}