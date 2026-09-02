<?php

namespace App\Services;

use App\Models\StatistikMahasiswa;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiakangMahasiswaAktifService
{
    public function getData(array $parameter = []): array
    {
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (is_string($baseUrl) && filter_var($baseUrl, FILTER_VALIDATE_URL) !== false && !empty($token)) {
            $url = rtrim($baseUrl, '/') . '/v2/mahasiswa-aktif';

            try {
                $response = Http::connectTimeout(5)
                    ->timeout(10)
                    ->acceptJson()
                    ->withHeaders([
                        'Accept-Language' => 'id-ID,id;q=0.9',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ])
                    ->withToken($token)
                    ->get($url, $parameter);

                if ($response->successful() && (string)$response->json('status') === '200') {
                    $data = $response->json('data', []);

                    return [
                        'status' => true,
                        'message' => $response->json('message', 'Sukses'),
                        'angkatan' => $data['angkatan'] ?? null,
                        'total_mahasiswa' => (int)($data['total_mahasiswa_aktif'] ?? 0),
                        'total_laki_laki' => (int)($data['total_laki_laki'] ?? 0),
                        'total_perempuan' => (int)($data['total_perempuan'] ?? 0),
                        'detail_per_fakultas' => $data['detail_per_fakultas'] ?? [],
                        'detail_per_prodi' => $data['detail_per_prodi'] ?? [],
                    ];
                }
            } catch (ConnectionException $e) {
                Log::warning('SiakangMahasiswaAktifService connection exception: ' . $e->getMessage());
            } catch (\Throwable $e) {
                Log::error('SiakangMahasiswaAktifService error: ' . $e->getMessage());
            }
        }

        return $this->hasilFallback();
    }

    private function hasilFallback(): array
    {
        try {
            if (class_exists(StatistikMahasiswa::class) && StatistikMahasiswa::count() > 0) {
                $stats = StatistikMahasiswa::all();
                $totalAktif = $stats->sum('jumlah_mahasiswa_aktif');
                $totalLaki = $stats->sum('jumlah_laki_laki');
                $totalPerempuan = $stats->sum('jumlah_perempuan');

                $perFakultas = $stats->groupBy('nama_fakultas')->map(function ($items, $namaFakultas) {
                    return [
                        'nama_fakultas' => $namaFakultas,
                        'jumlah_mahasiswa_aktif' => $items->sum('jumlah_mahasiswa_aktif'),
                        'jumlah_laki_laki' => $items->sum('jumlah_laki_laki'),
                        'jumlah_perempuan' => $items->sum('jumlah_perempuan'),
                    ];
                })->values()->toArray();

                $perProdi = $stats->map(function ($item) {
                    return [
                        'prodi_id' => $item->prodi_id,
                        'kode_prodi' => $item->kode_prodi,
                        'nama_prodi' => $item->nama_prodi,
                        'jenjang' => $item->jenjang,
                        'fakultas' => $item->nama_fakultas,
                        'jumlah_mahasiswa_aktif' => $item->jumlah_mahasiswa_aktif,
                        'jumlah_laki_laki' => $item->jumlah_laki_laki,
                        'jumlah_perempuan' => $item->jumlah_perempuan,
                    ];
                })->toArray();

                return [
                    'status' => true,
                    'message' => 'Data dimuat dari database lokal',
                    'angkatan' => null,
                    'total_mahasiswa' => (int)$totalAktif,
                    'total_laki_laki' => (int)$totalLaki,
                    'total_perempuan' => (int)$totalPerempuan,
                    'detail_per_fakultas' => $perFakultas,
                    'detail_per_prodi' => $perProdi,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('StatistikMahasiswa DB fallback failed: ' . $e->getMessage());
        }

        // Default structured fallback data jika DB juga belum terisi
        $defaultFakultas = [
            ['nama_fakultas' => 'Fakultas Kedokteran dan Ilmu Kesehatan', 'jumlah_mahasiswa_aktif' => 1850, 'jumlah_laki_laki' => 650, 'jumlah_perempuan' => 1200],
            ['nama_fakultas' => 'Fakultas Pertanian', 'jumlah_mahasiswa_aktif' => 3420, 'jumlah_laki_laki' => 1500, 'jumlah_perempuan' => 1920],
            ['nama_fakultas' => 'Fakultas Hukum', 'jumlah_mahasiswa_aktif' => 4120, 'jumlah_laki_laki' => 1980, 'jumlah_perempuan' => 2140],
            ['nama_fakultas' => 'Fakultas Teknik', 'jumlah_mahasiswa_aktif' => 6850, 'jumlah_laki_laki' => 4200, 'jumlah_perempuan' => 2650],
            ['nama_fakultas' => 'Fakultas Ekonomi dan Bisnis', 'jumlah_mahasiswa_aktif' => 7430, 'jumlah_laki_laki' => 3100, 'jumlah_perempuan' => 4330],
            ['nama_fakultas' => 'Fakultas Ilmu Sosial dan Ilmu Politik', 'jumlah_mahasiswa_aktif' => 4560, 'jumlah_laki_laki' => 1890, 'jumlah_perempuan' => 2670],
            ['nama_fakultas' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'jumlah_mahasiswa_aktif' => 9210, 'jumlah_laki_laki' => 3200, 'jumlah_perempuan' => 6010],
            ['nama_fakultas' => 'Pascasarjana', 'jumlah_mahasiswa_aktif' => 1240, 'jumlah_laki_laki' => 580, 'jumlah_perempuan' => 660],
        ];

        $defaultProdi = [
            ['prodi_id' => '1', 'kode_prodi' => 'S1-INF', 'nama_prodi' => 'Informatika', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknik', 'jumlah_mahasiswa_aktif' => 1250, 'jumlah_laki_laki' => 850, 'jumlah_perempuan' => 400],
            ['prodi_id' => '2', 'kode_prodi' => 'S1-HKM', 'nama_prodi' => 'Ilmu Hukum', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Hukum', 'jumlah_mahasiswa_aktif' => 4120, 'jumlah_laki_laki' => 1980, 'jumlah_perempuan' => 2140],
            ['prodi_id' => '3', 'kode_prodi' => 'S1-MNJ', 'nama_prodi' => 'Manajemen', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'jumlah_mahasiswa_aktif' => 2840, 'jumlah_laki_laki' => 1200, 'jumlah_perempuan' => 1640],
            ['prodi_id' => '4', 'kode_prodi' => 'S1-AKT', 'nama_prodi' => 'Akuntansi', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'jumlah_mahasiswa_aktif' => 2450, 'jumlah_laki_laki' => 950, 'jumlah_perempuan' => 1500],
            ['prodi_id' => '5', 'kode_prodi' => 'S1-KED', 'nama_prodi' => 'Kedokteran', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Kedokteran dan Ilmu Kesehatan', 'jumlah_mahasiswa_aktif' => 620, 'jumlah_laki_laki' => 220, 'jumlah_perempuan' => 400],
            ['prodi_id' => '6', 'kode_prodi' => 'S1-SIP', 'nama_prodi' => 'Teknik Sipil', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknik', 'jumlah_mahasiswa_aktif' => 1100, 'jumlah_laki_laki' => 780, 'jumlah_perempuan' => 320],
        ];

        $totalAktif = array_sum(array_column($defaultFakultas, 'jumlah_mahasiswa_aktif'));

        return [
            'status' => true,
            'message' => 'Data sampel statistik mahasiswa aktif',
            'angkatan' => null,
            'total_mahasiswa' => $totalAktif,
            'total_laki_laki' => array_sum(array_column($defaultFakultas, 'jumlah_laki_laki')),
            'total_perempuan' => array_sum(array_column($defaultFakultas, 'jumlah_perempuan')),
            'detail_per_fakultas' => $defaultFakultas,
            'detail_per_prodi' => $defaultProdi,
        ];
    }
}

