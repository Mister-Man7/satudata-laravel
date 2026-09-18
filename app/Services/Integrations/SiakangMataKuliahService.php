<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
     * Fetch dengan SWR dual-key: data (TTL 6 jam) + freshness flag (TTL 20 menit).
     * Tidak ada DB lokal untuk mata kuliah, jadi fallback langsung ke cache lama jika API gagal.
     */
    private function cachedGet(string $endpoint, array $params = []): ApiResponse
    {
        $cacheKey     = 'siakang.matkul.' . md5($endpoint . json_encode($params));
        $staleFlagKey = $cacheKey . '.fresh';

        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);

            if (!Cache::has($staleFlagKey) && function_exists('defer')) {
                defer(function () use ($cacheKey, $staleFlagKey, $endpoint, $params) {
                    try {
                        $response = $this->get($endpoint, $params);
                        if ($response->success && !empty($response->data)) {
                            Cache::put($cacheKey, $response->data, now()->addHours(6));
                        }
                    } catch (\Throwable $e) {
                        Log::warning('SWR matkul refresh gagal: ' . $e->getMessage());
                    } finally {
                        Cache::put($staleFlagKey, true, now()->addMinutes(20));
                    }
                });
            }

            return new ApiResponse(success: true, status: 200, message: 'Data mata kuliah', data: $data);
        }

        $response = $this->get($endpoint, $params);
        if ($response->success && !empty($response->data)) {
            Cache::put($cacheKey, $response->data, now()->addHours(6));
            Cache::put($staleFlagKey, true, now()->addMinutes(20));
        }

        return $response;
    }

    public function getMataKuliahTingkatUniversitas(array $params = []): ApiResponse
    {
        return $this->cachedGet('/v2/mata_kuliah/tingkat-universitas', $params);
    }

    /**
     * Get mata kuliah tingkat fakultas.
     */
    public function getMataKuliahTingkatFakultas(string $kodeFakultas, array $params = []): ApiResponse
    {
        return $this->cachedGet('/v2/mata_kuliah/tingkat-fakultas', array_merge(['kode_fakultas' => $kodeFakultas], $params));
    }

    /**
     * Get mata kuliah tingkat prodi.
     */
    public function getMataKuliahTingkatProdi(string $kodeProdi, array $params = []): ApiResponse
    {
        return $this->cachedGet('/v2/mata_kuliah/tingkat-prodi', array_merge(['kode_prodi' => $kodeProdi], $params));
    }

    /**
     * Fetch all items across all pages for a given endpoint.
     */
    public function getAllItems(string $endpoint, array $params = [], int $maxPages = 20): array
    {
        $cacheKey     = 'siakang.matkul.all.' . md5($endpoint . json_encode($params));
        $staleFlagKey = $cacheKey . '.fresh';

        if (Cache::has($cacheKey)) {
            $items = Cache::get($cacheKey);

            if (!Cache::has($staleFlagKey) && function_exists('defer')) {
                defer(function () use ($cacheKey, $staleFlagKey, $endpoint, $params, $maxPages) {
                    try {
                        $fresh = $this->fetchAllPages($endpoint, $params, $maxPages);
                        if (!empty($fresh)) {
                            Cache::put($cacheKey, $fresh, now()->addHours(6));
                        }
                    } catch (\Throwable $e) {
                        Log::warning('SWR matkul all-pages refresh gagal: ' . $e->getMessage());
                    } finally {
                        Cache::put($staleFlagKey, true, now()->addMinutes(20));
                    }
                });
            }

            return $items;
        }

        $items = $this->fetchAllPages($endpoint, $params, $maxPages);
        if (!empty($items)) {
            Cache::put($cacheKey, $items, now()->addHours(6));
            Cache::put($staleFlagKey, true, now()->addMinutes(20));
        }

        return $items;
    }

    private function fetchAllPages(string $endpoint, array $params, int $maxPages): array
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
        } while ($page <= $lastPage && $page <= $maxPages);

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
        $cacheKey     = 'siakang.matkul.units';
        $staleFlagKey = $cacheKey . '.fresh';

        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);

            if (!Cache::has($staleFlagKey) && function_exists('defer')) {
                defer(function () use ($cacheKey, $staleFlagKey) {
                    try {
                        $res = $this->get('/v2/unit');
                        if ($res->success) {
                            $raw = $res->data ?? [];
                            $units = isset($raw[0]) && is_array($raw[0])
                                ? ($raw[0]['data'] ?? $raw[0])
                                : ($raw['data'] ?? $raw);
                            if (!empty($units)) {
                                Cache::put($cacheKey, $units, now()->addHours(6));
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('SWR units refresh gagal: ' . $e->getMessage());
                    } finally {
                        Cache::put($staleFlagKey, true, now()->addMinutes(20));
                    }
                });
            }

            return $data;
        }

        $res = $this->get('/v2/unit');
        if (!$res->success) return [];

        $raw   = $res->data ?? [];
        $units = isset($raw[0]) && is_array($raw[0])
            ? ($raw[0]['data'] ?? $raw[0])
            : ($raw['data'] ?? $raw);

        if (!empty($units)) {
            Cache::put($cacheKey, $units, now()->addHours(6));
            Cache::put($staleFlagKey, true, now()->addMinutes(20));
        }

        return $units;
    }
}