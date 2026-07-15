<?php

namespace App\Http\Controllers;

use App\Services\SimantapService;
use Illuminate\Http\Request;

class AsetController extends Controller
{
    protected $apiService;

    public function __construct(SimantapService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index(Request $request)
    {
        $params = $request->only(['per_page', 'id_satker', 'search', 'all']);
        $params['per_page'] = $params['per_page'] ?? 100;

        $response = $this->apiService->makeRequest('GET', 'kampus', $params);
        $kampusList = $response['data']['data'] ?? $response['data'] ?? [];

        $datas = collect($kampusList)->map(function ($kampus) {
            return [
                'id' => $kampus['id_kampus'],
                'title' => $kampus['nama_kampus'],
                'count' => 'Lihat detail',
                'icon' => 'building',
                'updated' => $kampus['updated_at'] ?? now(),
            ];
        });

        return view('aset', compact('datas'), [
            'title' => 'Aset',
            'level' => 'kampus'
        ]);
    }

    public function kampusById($id)
    {
        $response = $this->apiService->makeRequest('GET', "kampus/{$id}");

        return view('aset-detail', [
            'datas' => $response['data'] ?? [],
            'title' => 'Detail Kampus',
        ]);
    }

    public function kampusBySatker($satkerId)
    {
        $response = $this->apiService->makeRequest('GET', "kampus/{$satkerId}");

        return view('aset', [
            'datas' => $response['data'] ?? [],
            'title' => 'Kampus by Satker',
            'level' => 'kampus',
        ]);
    }

    public function allGedung(Request $request)
    {
        $params = $request->only(['per_page']);
        $response = $this->apiService->makeRequest('GET', 'gedung', $params);

        return view('aset', [
            'datas' => $response['data'] ?? [],
            'title' => 'Semua Gedung',
            'level' => 'gedung'
        ]);
    }

    public function gedung($kampusId)
    {
        $response = $this->apiService->makeRequest('GET', "kampus/{$kampusId}", [
            'per_page' => 100,
        ]);

        $dataKampus = $response['data'] ?? [];

        $gedungList = $dataKampus['gedung'] ?? $dataKampus['gedungs'] ?? $dataKampus['data_gedung'] ?? [];

        if (empty($gedungList)) {
            $response = $this->apiService->makeRequest('GET', "gedung/by-kampus/{$kampusId}", [
                'per_page' => 100,
            ]);

            $gedungList = $respByKampus['data']['data'] ?? $respByKampus['data'] ?? [];
        }

        if (empty($gedungList)) {
            $respAll = $this->apiService->makeRequest('GET', "gedung", [
                'per_page' => 100,
            ]);
            $semuaGedung = $respAll['data']['data'] ?? $respAll['data'] ?? [];

            $gedungList = collect($semuaGedung)
                ->where('id_kampus', $kampusId)
                ->values()
                ->all();
        }

        $datas = collect($gedungList)->map(function ($gedung) {
            return [
                'id' => $gedung['id_gedung'] ?? $gedung['id'],
                'title' => $gedung['nama_gedung'] ?? 'Nama Gedung Tidak Diketahui',
                'icon' => 'map',
                'updated' => $gedung['updated_at'] ?? now(),
            ];
        });

        return view('aset', compact('datas'), [
            'title' => 'Gedung - ' . ($dataKampus['nama_kampus'] ?? 'Daftar Gedung'),
            'level' => 'gedung',
        ]);
    }

    public function gedungById($id)
    {
        $response = $this->apiService->makeRequest('GET', "gedung/{$id}");

        return view('aset-detail', [
            'datas' => $response['data'] ?? [],
            'title' => 'Detail Gedung'
        ]);
    }

    public function lantaiGedung(Request $request)
    {
        $params = $request->only(['per_page', 'id_gedung', 'all']);
        $response = $this->apiService->makeRequest('GET', "lantai-gedung", $params);

        return view('aset-lantai', [
            'datas' => $response['data'] ?? [],
            'title' => 'Lantai Gedung'
        ]);
    }

    public function lantaiGedungById($id)
    {
        $response = $this->apiService->makeRequest('GET', "lantai-gedung/{$id}");

        return view('aset-detail', [
            'datas' => $response['data'] ?? [],
            'title' => 'Detail Lantai Gedung'
        ]);
    }

    public function lantaiGedungByGedung($gedungId)
    {
        $response = $this->apiService->makeRequest('GET', "lantai-gedung/by-gedung/{$gedungId}");

        return view('aset-detail', [
            'datas' => $response['data'] ?? [],
            'title' => 'Lantai by Gedung',
        ]);
    }

    public function allRuangan(Request $request)
    {
        $params = $request->only(['per_page']);

        $response = $this->apiService->makeRequest('GET', 'ruangan', $params);

        return view('aset', [
            'datas' => $response['data'] ?? [],
            'title' => 'Ruangan'
        ]);
    }

    public function ruangan($gedungId)
    {
        $response = $this->apiService->makeRequest('GET', "ruangan/by-gedung/{$gedungId}", [
            'per_page' => 100,
        ]);

        $gedungList = $response['data']['data'] ?? $response['data'] ?? [];
        $datas = collect($gedungList)->map(function ($ruangan) {
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

    public function ruanganById($id)
    {
        $response = $this->apiService->makeRequest('GET', "ruangan/{$id}");

        return view('aset-detail', [
            'data' => $response['data'] ?? [],
            'title' => 'Detail Ruangan'
        ]);
    }

    public function ruanganByLantai($lantaiId)
    {
        $response = $this->apiService->makeRequest('GET', "ruangan/by-lantai/{$lantaiId}");

        return view('aset', [
            'datas' => $response['data'] ?? [],
            'title' => 'Ruangan by Lantai',
            'level' => 'ruangan'
        ]);
    }

    public function bmnDisewakan(Request $request)
    {
        $params = $request->only(['per_page']);
        $response = $this->apiService->makeRequest('GET', 'bmn', $params);

        return view('aset-bmn', [
            'bmnList' => collect($response['data']['data'] ?? $response['data'] ?? []),
            'title' => 'BMN Disewakan',
            'level' => 'bmn'
        ]);
    }

    public function bmnDisewakanById($id)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/{$id}");
        return view('aset-detail', [
            'data' => $response['data'] ?? [],
            'title' => 'Detail BMN Disewakan'
        ]);
    }

    public function bmnDisewakanByKampus($kampusId)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/by-kampung/{$kampusId}");
        return view('aset-detail', [
            'data' => $response['data'] ?? [],
            'title' => 'Detail BMN Disewakan (kampus)'
        ]);
    }

    public function bmnDisewakanByGedung($gedungId)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/by-gedung/{$gedungId}");

        return view('aset-bmn', [
            'bmnList' => collect($response['data'] ?? []),
            'title' => 'BMN Disewakan (Gedung)',
            'level' => 'bmn'
        ]);
    }

    public function bmnDisewakanByRuangan($ruanganId)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/by-ruangan/{$ruanganId}");

        return view('aset-bmn', [
            'bmnList' => collect($response['data'] ?? []),
            'title' => 'BMN Disewakan (Ruangan)',
            'level' => 'bmn'
        ]);
    }

    public function bmnDisewakanByJenis($jenis)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/by-jenis/{$jenis}");

        return view('aset-bmn', [
            'bmnList' => collect($response['data'] ?? []),
            'title' => 'BMN Disewakan (Jenis)',
            'level' => 'bmn'
        ]);
    }

    public function bmnAll(Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'id_kampus', 'id_gedung', 'id_ruangan', 'id_jenis_barang', 'kondisi', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'bmn-all', $params);

        return view('aset-bmn', [
            'title' => 'Daftar Semua BMN',
            'level' => 'bmn',
            'bmnList' => collect($response['data']['data'] ?? $response['data'] ?? [])
        ]);
    }

    public function bmnAllById($id)
    {
        $response = $this->apiService->makeRequest('GET', "bmn-all/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail BMN']);
    }

    public function bmn($ruanganId, Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'kondisi', 'all']);
        $params['per_page'] = $params['per_page'] ?? 100;

        $response = $this->apiService->makeRequest('GET', "bmn-all/by-ruangan/{$ruanganId}", $params);
        $bmnList = $response['data']['data'] ?? $response['data'] ?? [];

        return view('aset-bmn', [
            'title' => 'Daftar Inventaris Ruangan',
            'level' => 'bmn',
            'bmnList' => collect($bmnList)
        ]);
    }

    public function bmnAllByKampus($kampusId, Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'kondisi', 'all']);
        $response = $this->apiService->makeRequest('GET', "bmn-all/by-kampus/{$kampusId}", $params);
        return view('aset-bmn', ['bmnList' => collect($response['data']['data'] ?? $response['data'] ?? []), 'title' => 'BMN by Kampus', 'level' => 'bmn']);
    }

    public function bmnAllByGedung($gedungId, Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'kondisi', 'all']);
        $response = $this->apiService->makeRequest('GET', "bmn-all/by-gedung/{$gedungId}", $params);
        return view('aset-bmn', ['bmnList' => collect($response['data']['data'] ?? $response['data'] ?? []), 'title' => 'BMN by Gedung', 'level' => 'bmn']);
    }

    public function bmnAllByJenisBarang($jenisBarangId, Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'kondisi', 'all']);
        $response = $this->apiService->makeRequest('GET', "bmn-all/by-jenis-barang/{$jenisBarangId}", $params);
        return view('aset-bmn', ['bmnList' => collect($response['data']['data'] ?? $response['data'] ?? []), 'title' => 'BMN by Jenis Barang', 'level' => 'bmn']);
    }

    public function jenisBarang(Request $request)
    {
        $params = $request->only(['per_page', 'status', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'jenis-barang', $params);
        return view('aset-master', ['datas' => $response['data'] ?? [], 'title' => 'Jenis Barang']);
    }

    public function jenisBarangById($id)
    {
        $response = $this->apiService->makeRequest('GET', "jenis-barang/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Jenis Barang']);
    }

    public function kodeBarang(Request $request)
    {
        $params = $request->only(['per_page', 'status', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'kode-barang', $params);
        return view('aset-master', ['datas' => $response['data'] ?? [], 'title' => 'Kode Barang']);
    }

    public function kodeBarangById($id)
    {
        $response = $this->apiService->makeRequest('GET', "kode-barang/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Kode Barang']);
    }

    public function satker(Request $request)
    {
        $params = $request->only(['per_page', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'satker', $params);
        return view('aset-master', ['datas' => $response['data'] ?? [], 'title' => 'Satker']);
    }

    public function satkerById($id)
    {
        $response = $this->apiService->makeRequest('GET', "satker/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Satker']);
    }

    public function perusahaan(Request $request)
    {
        $params = $request->only(['per_page', 'status', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'perusahaan', $params);
        return view('aset-master', ['datas' => $response['data'] ?? [], 'title' => 'Perusahaan']);
    }

    public function perusahaanById($id)
    {
        $response = $this->apiService->makeRequest('GET', "perusahaan/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Perusahaan']);
    }

    public function jenisDokumen(Request $request)
    {
        $params = $request->only(['per_page', 'status', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'jenis-dokumen', $params);
        return view('aset-master', ['datas' => $response['data'] ?? [], 'title' => 'Jenis Dokumen']);
    }

    public function jenisDokumenById($id)
    {
        $response = $this->apiService->makeRequest('GET', "jenis-dokumen/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Jenis Dokumen']);
    }

    public function tipeRuangan(Request $request)
    {
        $params = $request->only(['per_page', 'status', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'tipe-ruangan', $params);
        return view('aset-master', ['datas' => $response['data'] ?? [], 'title' => 'Tipe Ruangan']);
    }

    public function tipeRuanganById($id)
    {
        $response = $this->apiService->makeRequest('GET', "tipe-ruangan/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Tipe Ruangan']);
    }

    public function riwayatBmn(Request $request)
    {
        $params = $request->only(['per_page', 'id_bmn', 'kondisi']);
        $response = $this->apiService->makeRequest('GET', 'riwayat-pemeliharaan-bmn', $params);
        return view('aset-riwayat', ['datas' => $response['data'] ?? [], 'title' => 'Riwayat Pemeliharaan BMN']);
    }

    public function riwayatBmnById($id)
    {
        $response = $this->apiService->makeRequest('GET', "riwayat-pemeliharaan-bmn/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Riwayat BMN']);
    }

    public function riwayatBmnByBmn($bmnId)
    {
        $response = $this->apiService->makeRequest('GET', "riwayat-pemeliharaan-bmn/by-bmn/{$bmnId}");
        return view('aset-riwayat', ['datas' => $response['data'] ?? [], 'title' => 'Riwayat by BMN']);
    }

    public function riwayatGedung(Request $request)
    {
        $params = $request->only(['per_page', 'id_gedung']);
        $response = $this->apiService->makeRequest('GET', 'riwayat-pemeliharaan-gedung', $params);
        return view('aset-riwayat', ['datas' => $response['data'] ?? [], 'title' => 'Riwayat Pemeliharaan Gedung']);
    }

    public function riwayatGedungById($id)
    {
        $response = $this->apiService->makeRequest('GET', "riwayat-pemeliharaan-gedung/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Riwayat Gedung']);
    }

    public function riwayatGedungByGedung($gedungId)
    {
        $response = $this->apiService->makeRequest('GET', "riwayat-pemeliharaan-gedung/by-gedung/{$gedungId}");
        return view('aset-riwayat', ['datas' => $response['data'] ?? [], 'title' => 'Riwayat by Gedung']);
    }

    public function riwayatRuangan(Request $request)
    {
        $params = $request->only(['per_page', 'id_ruangan']);
        $response = $this->apiService->makeRequest('GET', 'riwayat-pemeliharaan-ruangan', $params);
        return view('aset-riwayat', ['datas' => $response['data'] ?? [], 'title' => 'Riwayat Pemeliharaan Ruangan']);
    }

    public function riwayatRuanganById($id)
    {
        $response = $this->apiService->makeRequest('GET', "riwayat-pemeliharaan-ruangan/{$id}");
        return view('aset-detail', ['data' => $response['data'] ?? [], 'title' => 'Detail Riwayat Ruangan']);
    }

    public function riwayatRuanganByRuangan($ruanganId)
    {
        $response = $this->apiService->makeRequest('GET', "riwayat-pemeliharaan-ruangan/by-ruangan/{$ruanganId}");
        return view('aset-riwayat', ['datas' => $response['data'] ?? [], 'title' => 'Riwayat by Ruangan']);
    }

}
