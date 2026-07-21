<?php

namespace App\Services;

use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;

class PegawaiSyncService
{
    public function __construct(
        protected SimpegPegawaiService $api
    )
    {
    }

    public function sync(array $parameter): array
    {
        $response = $this->api->getData($parameter);

        if (!$response['status']) {
            return $response;
        }

        $dataPegawai = $response['data'];
        $rows = [];
        $now = now();

        foreach ($dataPegawai as $pegawai) {
            if (empty($pegawai['nip'])) {
                continue;
            }

            $rows[] = [
                'nip' => $pegawai['nip'],
                'kode_data' => $pegawai['kodeData'] ?? null,
                'id_sdm' => $pegawai['idSDM'] ?? null,
                'nama' => $pegawai['namaPegawai'] ?? '',
                'gelar_depan' => $pegawai['gelarDepan'] ?? null,
                'gelar_belakang' => $pegawai['gelarBelakang'] ?? null,
                'email' => $pegawai['emailPegawai'] ?? null,
                'no_tlp' => $pegawai['noTlp'] ?? null,
                'unit_kerja' => $pegawai['unitKerja'] ?? null,
                'unit_kerja_id' => $pegawai['unitKerja_id'] ?? null,
                'jabatan' => $pegawai['jabatan'] ?? null,
                'jabatan_id' => $pegawai['jabatan_id'] ?? null,
                'pangkat' => $pegawai['pangkat'] ?? null,
                'pangkat_id' => $pegawai['pangkat_id'] ?? null,
                'status_kerja' => $pegawai['statusKerja'] ?? null,
                'level_pegawai' => $pegawai['levelPegawai'] ?? null,
                'payload' => json_encode($pegawai, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return [
                'status' => true,
                'message' => 'Tidak ada data valid untuk disinkronkan.',
                'total' => 0,
                'received' => 0,
            ];
        }

        DB::disableQueryLog();

        $chunks = array_chunk($rows, 50);

        DB::transaction(function () use ($chunks) {
            foreach ($chunks as $chunk) {
                Pegawai::upsert(
                    $chunk,
                    ['nip'],
                    [
                        'kode_data',
                        'id_sdm',
                        'nama',
                        'gelar_depan',
                        'gelar_belakang',
                        'email',
                        'no_tlp',
                        'unit_kerja',
                        'unit_kerja_id',
                        'jabatan',
                        'jabatan_id',
                        'pangkat',
                        'pangkat_id',
                        'status_kerja',
                        'level_pegawai',
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
            'received' => count($dataPegawai),
        ];
    }
}
