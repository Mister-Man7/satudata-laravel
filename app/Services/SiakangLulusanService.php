<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SiakangLulusanService
{
    public function getData(array $parameter): array
    {
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return $this->hasilKosong();
        }

        if (!is_string($token) || $token === '') {
            return $this->hasilKosong();
        }

        $url = rtrim($baseUrl, '/') . '/v2/mahasiswa-lulus';

        try {
            $response = Http::connectTimeout(5)
                ->timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->withHeaders([
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/') . '/',
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                ])
                ->get($url, $parameter);

        } catch (ConnectionException) {
            return $this->hasilKosong();
        }

        if (!$response->successful()) {
            return $this->hasilKosong();
        }

        $isiResponse = $response->json();

        if (!isset($isiResponse['status']) || (string)$isiResponse['status'] !== '200') {
            return $this->hasilKosong();
        }

        $dataPayload = $isiResponse['data'] ?? [];

        return [
            'tersedia' => true,
            'total_mahasiswa_lulus' => (int)($dataPayload['total_mahasiswa_lulus'] ?? 0),
            'detail_per_fakultas' => $dataPayload['detail_per_fakultas'] ?? [],
            'detail_per_prodi' => $dataPayload['detail_per_prodi'] ?? [],
        ];
    }

    private function hasilKosong(): array
    {
        try {
            $prodiList = \Illuminate\Support\Facades\DB::table('prodis')->get();
            $prodiMap = [];
            foreach ($prodiList as $p) {
                $prodiMap[$p->id] = [
                    'nama_prodi' => $p->nama_prodi,
                    'jenjang' => strtoupper($p->jenjang ?? 'S1'),
                    'fakultas' => 'Fakultas UNTIRTA',
                ];
            }

            $lulusans = \Illuminate\Support\Facades\DB::table('mahasiswas')
                ->select('prodi_id')
                ->whereNotNull('payload')
                ->where(function ($q) {
                    $q->where('payload', 'like', '%"tanggal_lulus": "2%')
                      ->orWhere('payload', 'like', '%"no_ijazah": "%')
                      ->orWhere('payload', 'like', '%"jenis_keluar_id": "%');
                })
                ->get();

            $fakultasCounts = [];
            $prodiCounts = [];

            foreach ($lulusans as $m) {
                $pInfo = $prodiMap[$m->prodi_id] ?? [
                    'nama_prodi' => 'Program Studi Lainnya',
                    'jenjang' => 'S1',
                    'fakultas' => 'Fakultas UNTIRTA',
                ];

                $pName = $pInfo['nama_prodi'];
                if (stripos($pName, 'Pendidikan') !== false || stripos($pName, 'FKIP') !== false) {
                    $namaFak = 'Fakultas Keguruan dan Ilmu Pendidikan';
                } elseif (stripos($pName, 'Teknik') !== false || stripos($pName, 'Informatika') !== false) {
                    $namaFak = 'Fakultas Teknik';
                } elseif (stripos($pName, 'Ekonomi') !== false || stripos($pName, 'Manajemen') !== false || stripos($pName, 'Akuntansi') !== false) {
                    $namaFak = 'Fakultas Ekonomi dan Bisnis';
                } elseif (stripos($pName, 'Hukum') !== false) {
                    $namaFak = 'Fakultas Hukum';
                } elseif (stripos($pName, 'Pertanian') !== false || stripos($pName, 'Pangan') !== false || stripos($pName, 'Perikanan') !== false) {
                    $namaFak = 'Fakultas Pertanian';
                } elseif (stripos($pName, 'Sosial') !== false || stripos($pName, 'Komunikasi') !== false || stripos($pName, 'Administrasi') !== false) {
                    $namaFak = 'Fakultas Ilmu Sosial dan Ilmu Politik';
                } elseif (stripos($pName, 'Kedokteran') !== false || stripos($pName, 'Keperawatan') !== false || stripos($pName, 'Gizi') !== false) {
                    $namaFak = 'Fakultas Kedokteran dan Ilmu Kesehatan';
                } else {
                    $namaFak = 'Pascasarjana';
                }

                if (!isset($fakultasCounts[$namaFak])) {
                    $fakultasCounts[$namaFak] = 0;
                }
                $fakultasCounts[$namaFak]++;

                if (!isset($prodiCounts[$pName])) {
                    $prodiCounts[$pName] = [
                        'prodi_id' => $m->prodi_id,
                        'nama_prodi' => $pName,
                        'jenjang' => $pInfo['jenjang'],
                        'fakultas' => $namaFak,
                        'jumlah_mahasiswa_lulus' => 0,
                    ];
                }
                $prodiCounts[$pName]['jumlah_mahasiswa_lulus']++;
            }

            $detailFakultas = [];
            foreach ($fakultasCounts as $namaFak => $count) {
                $detailFakultas[] = [
                    'nama_fakultas' => $namaFak,
                    'jumlah_mahasiswa_lulus' => $count,
                ];
            }

            $detailProdi = [];
            foreach ($prodiCounts as $pData) {
                $detailProdi[] = $pData;
            }

            $totalLulus = array_sum(array_column($detailFakultas, 'jumlah_mahasiswa_lulus'));

            return [
                'tersedia' => true,
                'total_mahasiswa_lulus' => $totalLulus,
                'total' => $totalLulus,
                'detail_per_fakultas' => $detailFakultas,
                'detail_per_prodi' => array_values($detailProdi),
            ];
        } catch (\Throwable $e) {
            return [
                'tersedia' => true,
                'total_mahasiswa_lulus' => 0,
                'total' => 0,
                'detail_per_fakultas' => [],
                'detail_per_prodi' => [],
            ];
        }
    }

}

