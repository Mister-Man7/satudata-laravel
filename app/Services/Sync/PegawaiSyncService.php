<?php

namespace App\Services\Sync;

use App\Models\Pegawai;
use App\Services\Integrations\SimpegPegawaiService;
use Illuminate\Support\Facades\DB;

class PegawaiSyncService
{
    public function __construct(
        protected SimpegPegawaiService $api
    )
    {
    }

    public function sync(array $parameter = []): array
    {
        $response = $this->api->getData($parameter);

        if (!$response->success) {
            return [
                'status' => false,
                'message' => $response->message,
                'total' => 0,
                'received' => 0,
            ];
        }

        $dataPegawai = $response->data;
        if (isset($dataPegawai['data']) && is_array($dataPegawai['data'])) {
            $dataPegawai = $dataPegawai['data'];
        }
        if (!is_array($dataPegawai)) {
            return [
                'status' => false,
                'message' => 'Format data pegawai dari API tidak valid.',
                'total' => 0,
                'received' => 0,
            ];
        }

        $rows = [];
        $now = now();

        foreach ($dataPegawai as $pegawai) {
            $nip = trim((string)($pegawai['nip'] ?? ''));
            if (empty($nip)) {
                continue;
            }

            $rows[] = [
                'nip' => $nip,
                'kode_data' => $pegawai['kd_pegawai'] ?? $pegawai['kodeData'] ?? null,
                'id_sdm' => $pegawai['id_sdm'] ?? $pegawai['idSDM'] ?? null,
                'nama' => $pegawai['namaPegawai'] ?? $pegawai['nama_pegawai'] ?? $pegawai['nama'] ?? '',
                'gelar_depan' => $pegawai['gelar_depan'] ?? $pegawai['gelarDepan'] ?? null,
                'gelar_belakang' => $pegawai['gelar_belakang'] ?? $pegawai['gelarBelakang'] ?? null,
                'email' => $pegawai['emailPegawai'] ?? $pegawai['email'] ?? null,
                'no_tlp' => $pegawai['noTlp'] ?? $pegawai['no_tlp'] ?? null,
                'unit_kerja' => $pegawai['unitKerja'] ?? $pegawai['unit_kerja'] ?? null,
                'unit_kerja_id' => $pegawai['unitKerja_id'] ?? $pegawai['unit_kerja_id'] ?? null,
                'jabatan' => $pegawai['nama_jabatan'] ?? $pegawai['jabatan'] ?? null,
                'jabatan_id' => $pegawai['jabatan_id'] ?? null,
                'pangkat' => $pegawai['pangkat'] ?? $pegawai['nama_pangkat'] ?? null,
                'pangkat_id' => $pegawai['pangkat_id'] ?? null,
                'status_kerja' => $pegawai['nama_stspegawai'] ?? $pegawai['statusKerja'] ?? null,
                'level_pegawai' => $pegawai['nama_level_pegawai'] ?? $pegawai['levelPegawai'] ?? null,
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
