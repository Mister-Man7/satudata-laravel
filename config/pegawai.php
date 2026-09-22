<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nilai Filter API SIMPEG
    |--------------------------------------------------------------------------
    |
    | Nilai id yang diterima API SIMPEG untuk filter daftar pegawai. Dipisah dari
    | controller agar tidak tersebar dan mudah disesuaikan bila API berubah.
    |
    */

    'filter' => [
        'status_kerja' => [1, 2, 3, 4, 5, 6, 7, 8, 19, 20],
        'status_pegawai' => [1, 2, 3, 4, 5, 6, 7, 19, 20],
        'level_pegawai' => [2, 3, 7, 13],
        'jabatan' => [44],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kelompok Level Pegawai
    |--------------------------------------------------------------------------
    |
    | Pengelompokan nama level dari API SIMPEG untuk kartu ringkasan halaman
    | pegawai. Nama di dalam kelompok harus sama dengan `nama_level_pegawai`
    | yang dikirim API.
    |
    */

    'kelompok_level' => [
        'dosen' => ['Dosen', 'Dosen DT', 'Dosen Luar Biasa', 'Dosen LB'],
        'tendik' => ['Tenaga Kependidikan', 'Tendik'],
    ],

];
