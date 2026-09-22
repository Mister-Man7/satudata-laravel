<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DosenSipp extends Model
{
    protected $fillable = [
        'nip',
        'semester',
        'sinta12',
        'sinta36',
        'jurnal_internasional_q',
        'jurnal_internasional_pbb',
        'jurnal_nasional_issn',
        'pengembangan',
        'pengabdian_masyarakat',
        'buku_referensi',
        'publikasi',
        'penelitian',
        'pengabdian',
        'penjadwalan',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'sinta12'                  => 'integer',
            'sinta36'                  => 'integer',
            'jurnal_internasional_q'   => 'integer',
            'jurnal_internasional_pbb' => 'integer',
            'jurnal_nasional_issn'     => 'integer',
            'pengembangan'             => 'integer',
            'pengabdian_masyarakat'    => 'integer',
            'buku_referensi'           => 'integer',
            'publikasi'                => 'array',
            'penelitian'               => 'array',
            'pengabdian'               => 'array',
            'penjadwalan'              => 'array',
            'payload'                  => 'array',
        ];
    }
}
