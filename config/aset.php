<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kondisi Barang
    |--------------------------------------------------------------------------
    |
    | Pemetaan kode `kondisi` dari API Simantap ke label `kondisi_text`. Ini
    | satu-satunya tempat pemetaan tersebut ditulis; controller dan view
    | membacanya dari sini.
    |
    | Kode 1, 2, dan 3 adalah kode yang dipakai data Simantap. Kode 4 belum
    | pernah muncul pada data tersinkron, jadi belum dipetakan; tambahkan di
    | sini bila label resminya sudah diketahui.
    |
    */

    'kondisi' => [
        'baik' => ['kode' => 1, 'label' => 'Baik'],
        'rusak_ringan' => ['kode' => 2, 'label' => 'Rusak Ringan'],
        'rusak_berat' => ['kode' => 3, 'label' => 'Rusak Berat'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Jumlah Data per Halaman
    |--------------------------------------------------------------------------
    |
    | Nilai bawaan `per_page` saat meminta daftar dari API Simantap.
    |
    */

    'per_page' => [
        'daftar' => 100,
        'semua' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Penyegaran Data Aset
    |--------------------------------------------------------------------------
    |
    | Jumlah halaman BMN yang disegarkan setiap kali tombol "Tarik data aset"
    | ditekan. Sinkronisasi penuh mencapai puluhan ribu baris, jadi penarikan
    | dijalankan bertahap: sisa halaman dilanjutkan pada penekanan berikutnya.
    |
    */

    'tarik' => [
        'max_pages' => 5,
    ],

];
