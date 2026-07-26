<?php

namespace App\Services;

use App\Models\StatistikMahasiswa;
use Illuminate\Support\Facades\DB;


class StatistikMahasiswaSyncService
{
    public function __construct(
        protected SiakangMahasiswaAktifService $api
    ) {
    }

    public function sync(array $parameters = []): array
    {
        $response = $this->api->getData($parameters);

        if (!$response['status']) {
            return $response;
        }

        $dataProdi = $response['detail_per_prodi'];
        $rows = [];
        $now = now();

        foreach ($dataProdi as $prodi) {
            $rows[] = [
                'prodi_id'               => $prodi['prodi_id'],
                'kode_prodi'             => $prodi['kode_prodi'],
                'nama_prodi'             => $prodi['nama_prodi'],
                'jenjang'                => $prodi['jenjang'],
                'fakultas_id'            => $prodi['fakultas_id'],
                'nama_fakultas'          => $prodi['fakultas'],
                'jumlah_mahasiswa_aktif' => (int) $prodi['jumlah_mahasiswa_aktif'],
                'jumlah_laki_laki'       => (int) $prodi['jumlah_laki_laki'],
                'jumlah_perempuan'       => (int) $prodi['jumlah_perempuan'],
                'angkatan_filter'        => $response['angkatan'] ?? 'semua',
                'payload'                => json_encode($prodi, JSON_UNESCAPED_UNICODE),
                'created_at'             => $now,
                'updated_at'             => $now,
            ];
        }

        if (empty($rows)) {
            return [
                'status'  => true,
                'message' => 'Data masih kosong',
                'total'   => 0,
            ];
        }

        DB::disableQueryLog();

        $chunks = array_chunk($rows, 50);

        DB::transaction(function () use ($chunks) {
            foreach ($chunks as $chunk) {
                StatistikMahasiswa::upsert(
                    $chunk,
                    ['prodi_id', 'angkatan_filter'],
                    [
                        'kode_prodi',
                        'nama_prodi',
                        'jenjang',
                        'fakultas_id',
                        'nama_fakultas',
                        'jumlah_mahasiswa_aktif',
                        'jumlah_laki_laki',
                        'jumlah_perempuan',
                        'payload',
                        'updated_at',
                    ]
                );
            }
        });

        return [
            'status'   => true,
            'message'  => 'Sinkronisasi statistik prodi berhasil.',
            'total'    => count($rows),
            'received' => count($dataProdi),
        ];
    }
}
