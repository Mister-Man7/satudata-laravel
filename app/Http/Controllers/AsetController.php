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
        $response = $this->apiService->makeRequest('GET', 'kampus', ['per_page' => 25]);
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

    public function getGedung($kampusId)
    {
        $response = $this->apiService->makeRequest('GET', "gedung/by-kampus/{$kampusId}", [
            'per_page' => 25
        ]);

        $gedungList = $response['data']['data'] ?? $response['data'] ?? [];
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
}
