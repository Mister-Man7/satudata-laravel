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

        $response = $this->get('', $params);

        if ($response->success && is_array($response->data) && count($response->data) > 0) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(5));
            return $response;
        }

        // Fallback dinamis dari 1.936 record database `pegawais`
        try {
            $dbItems = \App\Models\Pegawai::all()->map(function ($item) {
                return [
                    'kodeData' => $item->kode_data,
                    'nip' => $item->nip,
                    'idSDM' => $item->id_sdm,
                    'namaPegawai' => $item->nama,
                    'nama' => $item->nama,
                    'gelarDepan' => $item->gelar_depan,
                    'gelarBelakang' => $item->gelar_belakang,
                    'emailPegawai' => $item->email,
                    'email' => $item->email,
                    'noTlp' => $item->no_tlp,
                    'unitKerja' => $item->unit_kerja,
                    'jabatan' => $item->jabatan,
                    'pangkat' => $item->pangkat,
                    'statusKerja' => $item->status_kerja,
                    'levelPegawai' => $item->level_pegawai,
                    'unitKerja_id' => $item->unit_kerja_id,
                    'jabatan_id' => $item->jabatan_id,
                    'pangkat_id' => $item->pangkat_id,
                ];
            })->toArray();

            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dimuat dari database lokal pegawais',
                data: $dbItems,
            );
        } catch (\Throwable $e) {
            return $response;
        }
    }

    /**
     * Ambil data dosen (cached dari API SIMPEG / DB).
     */
    public function getDataDosen(string $endpoint = '', array $params = []): ApiResponse
    {
        $cacheKey = 'simpeg_dosen_live_' . md5($endpoint . json_encode($params));

        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: $cachedData,
            );
        }

        $path = ($endpoint === 'pegawai' || $endpoint === '/pegawai') ? '' : '/' . ltrim($endpoint, '/');
        $response = $this->get($path, $params);

        if ($response->success && is_array($response->data) && count($response->data) > 0) {
            $dosenList = collect($response->data)
                ->filter(function ($item) {
                    $level = trim((string)($item['levelPegawai'] ?? ''));
                    return strcasecmp($level, 'Dosen') === 0 || stripos($level, 'Dosen') !== false;
                })
                ->map(function ($item) {
                    if (!isset($item['nama']) && isset($item['namaPegawai'])) {
                        $item['nama'] = $item['namaPegawai'];
                    }
                    if (!isset($item['email']) && isset($item['emailPegawai'])) {
                        $item['email'] = $item['emailPegawai'];
                    }
                    return $item;
                })
                ->values()
                ->toArray();

            Cache::put($cacheKey, $dosenList, now()->addMinutes(15));

            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dosen berhasil dimuat dari API SIMPEG',
                data: $dosenList,
            );
        }

        $fallbackData = $this->hasilFallbackDataDosen();
        return new ApiResponse(
            success: true,
            status: 200,
            message: 'Data dimuat dari database pegawais',
            data: $fallbackData,
        );
    }

    /**
     * Data dosen dinamis dari 1.936 record database `pegawais`.
     * Tanpa array statis / hardcoded.
     */
    private function hasilFallbackDataDosen(): array
    {
        try {
            $rows = \App\Models\Pegawai::where(function ($q) {
                $q->where('level_pegawai', 'like', '%Dosen%')
                  ->orWhere('jabatan', 'like', '%Dosen%')
                  ->orWhere('jabatan', 'like', '%Lektor%')
                  ->orWhere('jabatan', 'like', '%Asisten Ahli%')
                  ->orWhere('jabatan', 'like', '%Profesor%');
            })->get();

            return $rows->map(function ($item) {
                return [
                    'nip' => $item->nip,
                    'nama' => trim(($item->gelar_depan ? $item->gelar_depan . ' ' : '') . $item->nama . ($item->gelar_belakang ? ', ' . $item->gelar_belakang : '')),
                    'namaPegawai' => $item->nama,
                    'unitKerja' => $item->unit_kerja,
                    'statusKerja' => $item->status_kerja,
                    'levelPegawai' => $item->level_pegawai,
                    'jabatan' => $item->jabatan,
                    'pangkat' => $item->pangkat,
                    'email' => $item->email,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

}