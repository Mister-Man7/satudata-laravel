<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatistikMahasiswa extends Model
{
    protected $table = 'statistik_mahasiswas';

    protected $fillable = [
        'prodi_id',
        'kode_prodi',
        'nama_prodi',
        'jenjang',
        'fakultas_id',
        'nama_fakultas',
        'jumlah_mahasiswa_aktif',
        'jumlah_laki_laki',
        'jumlah_perempuan',
        'angkatan_filter',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'jumlah_mahasiswa_aktif' => 'integer',
            'jumlah_laki_laki' => 'integer',
            'jumlah_perempuan' => 'integer',
        ];
    }
}
