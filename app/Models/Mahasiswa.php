<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    protected $fillable = [
        'nim',
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
    ];

    protected function casts()
    {
        return [
            'payload' => 'array',
        ];

    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id', 'id');
    }
}
