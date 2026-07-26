<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    use HasFactory;

    protected $table = 'prodis';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'kode_prodi',
        'nama_prodi',
        'jenjang',
        'fakultas_id',
    ];

    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class, 'prodi_id', 'id');
    }
}
