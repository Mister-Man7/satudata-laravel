<?php

use App\Services\Integrations\AssetSyncLauncher;
use App\Services\Integrations\DosenSyncLauncher;

return [

    /*
    |--------------------------------------------------------------------------
    | Kontrak SWR (stale-while-revalidate)
    |--------------------------------------------------------------------------
    |
    | Dipakai semua sumber data: halaman selalu dilayani dari cache/database
    | lebih dulu, penarikan ulang dijalankan di latar. Penanda segar mencegah
    | penarikan berulang-ulang pada satu periode.
    |
    */

    'swr' => [
        'ttl_minutes' => 360,      // umur data di cache
        'fresh_minutes' => 20,     // data dianggap segar selama ini (anti-badai revalidate)
        'stale_minutes' => 60,      // lewat ini, data dianggap perlu ditarik ulang
    ],

    /*
    |--------------------------------------------------------------------------
    | Sumber data & penariknya
    |--------------------------------------------------------------------------
    |
    | Setiap sumber data punya penarik berbasis Chromium lokal (Edge/Chrome di
    | host aplikasi) karena panggilan HTTP langsung dari PHP selalu ditantang
    | Cloudflare (HTTP 403 "Just a moment"). Sumber tanpa penarik ditandai
    | launcher => null supaya UI menyebutkan bahwa penariknya belum ada.
    |
    */

    'sources' => [
        'simantap.aset' => [
            'label' => 'Aset BMN (SIMANTAP)',
            'sistem' => 'SIMANTAP',
            'keterangan' => 'Data aset dan total unit dibaca dari database SATUDATA hasil sinkronisasi SIMANTAP.',
            'launcher' => AssetSyncLauncher::class,
            'status_key' => 'aset_sync',
            'method' => 'pages',
        ],
        'siakang.penjadwalan' => [
            'label' => 'Penjadwalan dosen (SIAKANG)',
            'sistem' => 'SIAKANG',
            'keterangan' => 'Jadwal kuliah dibaca dari database SATUDATA hasil sinkronisasi SIAKANG per dosen.',
            'launcher' => DosenSyncLauncher::class,
            'status_key' => 'dosen_sync',
            'method' => 'per_lecturer',
        ],
        'sipp.portofolio' => [
            'label' => 'Portofolio dosen (SIPP)',
            'sistem' => 'SIPP',
            'keterangan' => 'Portofolio dosen dibaca dari database SATUDATA hasil sinkronisasi SIPP.',
            'launcher' => null,
            'command' => [
                'target' => 'scripts/sync-portofolio.php',
                'arguments' => ['--limit=25', '--key={key}'],
            ],
            'status_key' => 'sipp_sync',
            'method' => 'per_lecturer',
        ],
        'siakang.mahasiswa_aktif' => [
            'label' => 'Mahasiswa aktif (SIAKANG)',
            'sistem' => 'SIAKANG',
            'keterangan' => 'Statistik mahasiswa dibaca dari database SATUDATA hasil sinkronisasi SIAKANG.',
            'launcher' => null,
            'command' => [
                'target' => 'scripts/sync-siakang-stat.php',
                'arguments' => ['--jenis=aktif', '--semester={semester}', '--key={key}'],
            ],
            'status_key' => 'siakang_aktif_sync',
            'method' => 'pages',
        ],
        'siakang.lulusan' => [
            'label' => 'Mahasiswa lulus (SIAKANG)',
            'sistem' => 'SIAKANG',
            'keterangan' => 'Daftar mahasiswa lulus dibaca dari database SATUDATA hasil sinkronisasi SIAKANG.',
            'launcher' => null,
            'command' => [
                'target' => 'scripts/sync-siakang-stat.php',
                'arguments' => ['--jenis=lulus', '--semester={semester}', '--key={key}'],
            ],
            'status_key' => 'siakang_lulus_sync',
            'method' => 'pages',
        ],
        'simpeg.pegawai' => [
            'label' => 'Pegawai (SIMPEG)',
            'sistem' => 'SIMPEG',
            'keterangan' => 'Data pegawai dibaca dari database SATUDATA hasil sinkronisasi SIMPEG.',
            'launcher' => null,
            'command' => [
                'target' => 'sync:pegawai',
                'arguments' => ['--key={key}'],
            ],
            'status_key' => 'pegawai_sync',
            'method' => 'pages',
        ],
    ],

];
