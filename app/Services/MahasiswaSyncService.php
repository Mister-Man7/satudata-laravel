<?php

namespace App\Services;

use App\Models\Mahasiswa;
use Illuminate\Support\Facades\DB;

class MahasiswaSyncService
{
    public function __construct(
        protected SiakangMahasiswaService $api
    )
    {
    }

    public function sync(array $parameter): array
    {
        $response = $this->api->getData($parameter);

        if (!$response['status'] || empty($response['data'])) {
            return [
                'status' => $response['status'],
                'message' => $response['message'] ?? 'Data mahasiswa tidak tersedia',
                'total' => 0,
                'received' => 0,
            ];
        }

        $rows = [];
        $now = now();

        foreach ($response['data'] as $mhs) {

            $person = $mhs['person'] ?? [];

            $rows[] = [
                'nim' => $mhs['nim'],
                'nama' => $mhs['nama'] ?? '',
                'prodi_id' => $mhs['prodi_id'] ?? null,
                'jalur_masuk_id' => $mhs['jalur_masuk_id'] ?? '',
                'program_id' => $mhs['program_id'] ?? '',
                'jenjang_id' => $mhs['jenjang_id'] ?? '',
                'dosen_wali_id' => $mhs['dosen_wali_id'] ?? '',
                'angkatan' => $mhs['angkatan'] ?? null,
                'tanggal_masuk' => $mhs['tanggal_masuk'] ?? '',
                'kewarganegaraan' => $mhs['kewarganegaraan'] ?? 'ID',

                'agama' => $mhs['agama'] ?? $person['agama'] ?? '',
                'jenis_kelamin_string' => $mhs['jenis_kelamin_string'] ?? $person['jenis_kelamin_string'] ?? $person['jenis_kelamin'] ?? '',
                'tempat_tanggal_lahir' => $mhs['tempat_tanggal_lahir'] ?? $person['tempat_tanggal_lahir'] ?? trim(($person['tempat_lahir'] ?? '') . ', ' . ($person['tanggal_lahir'] ?? ''), ', '),

                'payload' => json_encode($mhs, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::disableQueryLog();

        $chunks = array_chunk($rows, 50);

        DB::transaction(function () use ($chunks) {
            foreach ($chunks as $chunk) {
                Mahasiswa::upsert(
                    $chunk,
                    ['nim'],
                    [
                        'nama',
                        'prodi_id',
                        'jalur_masuk_id',
                        'program_id',
                        'jenjang_id',
                        'dosen_wali_id',
                        'angkatan',
                        'tanggal_masuk',
                        'kewarganegaraan',
                        'agama',
                        'jenis_kelamin_string',
                        'tempat_tanggal_lahir',
                        'payload',
                        'updated_at',
                    ]
                );
            }
        });

        return [
            'status' => true,
            'message' => 'Sinkronisasi berhasil.',
            'total' => count($rows),
            'received' => count($rows),
        ];
    }
}
