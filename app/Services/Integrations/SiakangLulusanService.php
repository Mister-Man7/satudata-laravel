<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

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
            'connect_timeout' => 5,
            'timeout' => 15,
        ];
    }

    /**
     * Ambil data ringkasan mahasiswa lulus (cached).
     */
    public function getData(array $params = []): ApiResponse
    {
        $cacheKey = 'siakang.lulusan.' . md5(json_encode($params));

        if (Cache::has($cacheKey)) {
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: Cache::get($cacheKey),
            );
        }

        $response = $this->get('/v2/mahasiswa-lulus', $params);

        if ($response->success) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(10));
            return $response;
        }

        $fallbackData = $this->hasilFallbackLulusanData();
        return new ApiResponse(
            success: true,
            status: 200,
            message: 'Data dari fallback lokal',
            data: $fallbackData,
        );
    }

    private function hasilFallbackLulusanData(): array
    {
        $defaultFakultasLulus = [
            ['nama_fakultas' => 'Fakultas Kedokteran dan Ilmu Kesehatan', 'jumlah_mahasiswa_lulus' => 288],
            ['nama_fakultas' => 'Fakultas Pertanian', 'jumlah_mahasiswa_lulus' => 5114],
            ['nama_fakultas' => 'Fakultas Hukum', 'jumlah_mahasiswa_lulus' => 5985],
            ['nama_fakultas' => 'Fakultas Teknik', 'jumlah_mahasiswa_lulus' => 10895],
            ['nama_fakultas' => 'Fakultas Ekonomi dan Bisnis', 'jumlah_mahasiswa_lulus' => 12321],
            ['nama_fakultas' => 'Fakultas Ilmu Sosial dan Ilmu Politik', 'jumlah_mahasiswa_lulus' => 6031],
            ['nama_fakultas' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'jumlah_mahasiswa_lulus' => 17229],
            ['nama_fakultas' => 'Pascasarjana', 'jumlah_mahasiswa_lulus' => 1534],
        ];

        $defaultProdiLulus = [
            ['prodi_id' => '1', 'nama_prodi' => 'Informatika', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknik', 'jumlah_mahasiswa_lulus' => 850],
            ['prodi_id' => '2', 'nama_prodi' => 'Ilmu Hukum', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Hukum', 'jumlah_mahasiswa_lulus' => 2100],
            ['prodi_id' => '3', 'nama_prodi' => 'Manajemen', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'jumlah_mahasiswa_lulus' => 3200],
            ['prodi_id' => '4', 'nama_prodi' => 'Pendidikan Bahasa Indonesia', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'jumlah_mahasiswa_lulus' => 2400],
        ];

        $totalLulus = array_sum(array_column($defaultFakultasLulus, 'jumlah_mahasiswa_lulus'));

        return [
            'total_mahasiswa_lulus' => $totalLulus,
            'total' => $totalLulus,
            'detail_per_fakultas' => $defaultFakultasLulus,
            'detail_per_prodi' => $defaultProdiLulus,
        ];
    }


    /**
     * Ambil daftar mahasiswa lulus (paginated, cached).
     */
    public function getListMahasiswa(array $params = []): ApiResponse
    {
        $cacheKey = 'siakang.lulusan.list.' . md5(json_encode($params));

        if (Cache::has($cacheKey)) {
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: Cache::get($cacheKey),
            );
        }

        $response = $this->get('/v2/mahasiswa/lulusan', $params);

        if ($response->success) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(10));
        }

        return $response;
    }
}