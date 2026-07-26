<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $fillable = [
        'nip',
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
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
