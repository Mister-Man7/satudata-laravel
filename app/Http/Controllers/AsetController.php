<?php

namespace App\Http\Controllers;

use App\Services\SimantapService;

class AsetController extends Controller
{
    protected $apiService;

    public function __construct(SimantapService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index()
    {
        $response = $this->apiService->makeRequest('GET', 'kampus', ['per_page' => 100]);
        $kampusList = $response['data']['data'] ?? $response['data'] ?? [];

        $datas = collect($kampusList)->map(function ($kampus) {
            return [
                'id' => $kampus['id_kampus'],
                'title' => $kampus['nama_kampus'],
                'count' => 'Lihat detail',
                'icon' => 'building',
                'updated' => $kampus['updated_at'],
            ];
        });

        return view('aset', compact('datas'), [
            'title' => 'Aset',
            'level' => 'kampus'
        ]);
    }

    public function gedung($kampusId)
    {
        $response = $this->apiService->makeRequest('GET', "gedung/", [
            'per_page' => 100,
        ]);
        $semuaGedung = $response['data']['data'] ?? $response['data'] ?? [];
        $gedungList = collect($semuaGedung)
            ->where('id_kampus', $kampusId)
            ->values()
            ->all();

        $datas = collect($gedungList)->map(function ($gedung) {

            return [
                'id' => $gedung['id_gedung'],
                'title' => $gedung['nama_gedung'],
                'icon' => 'map',
                'updated' => $gedung['updated_at'] ?? now(),
            ];
        });

        return view('aset', compact('datas'), [
            'title' => 'Gedung',
            'level' => 'gedung',
        ]);

    }

    public function ruangan($gedungId)
    {
        $response = $this->apiService->makeRequest('GET', "ruangan/by-gedung/{$gedungId}", [
            'per_page' => 100,
        ]);

        $ruanganList = $response['data']['data'] ?? $response['data'] ?? [];
        $datas = collect($ruanganList)->map(function ($ruangan) {
            return [
                'id' => $ruangan['id_ruangan'],
                'title' => $ruangan['nama_ruangan'] ?? 'Nama Ruangan',
                'count' => 'Lihat Aset',
                'icon' => 'door',
                'updated' => $ruangan['updated_at'] ?? now(),
            ];
        });

        return view('aset', compact('datas'), [
            'title' => 'Ruangan',
            'level' => 'ruangan'
        ]);
    }

    public function bmn($ruanganId)
    {
        $response = $this->apiService->makeRequest('GET', "bmn-all/by-ruangan/{$ruanganId}", [
            'per_page' => 100,
        ]);
//        dd($response);

        $bmnList = $response['data']['data'] ?? $response['data'] ?? [];

        return view('aset-bmn', [
            'title' => 'Daftar Inventaris Ruangan',
            'level' => 'bmn',
            'bmnList' => collect($bmnList)
        ]);
    }
}
