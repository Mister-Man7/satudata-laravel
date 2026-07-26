<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    protected $table = 'asets';

    protected $fillable = [
        'id_bmn',
        'id_satker',
        'id_kampus',
        'id_gedung',
        'id_lantai_gedung',
        'id_ruangan',
        'id_jenis_barang',
        'nama_jenis_barang',
        'id_kode_barang',
        'nama_kode_barang',
        'nup',
        'merk',
        'tipe',
        'tgl_perolehan',
        'kondisi',
        'kondisi_text',
        'intra_ekstra',
        'status_sewa',
        'nilai_perolehan',
        'nilai_buku',
        'lokasi_lengkap',
        'umur_barang',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'tgl_perolehan' => 'date',
            'nilai_perolehan' => 'decimal:2',
            'nilai_buku' => 'decimal:2',
            'payload' => 'array',
        ];
    }
}
