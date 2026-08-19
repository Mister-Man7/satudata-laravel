<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Integrations\SimpegPegawaiService;
use App\Services\Integrations\SiakangPenjadwalanService;
use App\Services\Integrations\SIPPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DosenProfileController extends Controller
{
    public function __construct(
        public SimpegPegawaiService $pegawaiService,
        public SiakangPenjadwalanService $penjadwalanService,
        public SIPPService $sippService,
    ) {}

    public function show(Request $request, string $nip): View
    {
        $semester = $request->input('semester', '20251');

        $cacheKey = "dosen_profile_{$nip}_{$semester}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return view('academic.dosen-profile', $cachedData);
        }

        $dosenData = $this->getDosenData($nip);
        $jadwalData = $this->getJadwalData($nip, $semester);
        $publikasiData = $this->getPublikasiData($nip);
        $penelitianData = $this->getPenelitianData($nip);

        $profile = $this->buildProfile($dosenData);
        $jadwalHariIni = $this->buildJadwalHariIni($jadwalData);
        $statistikMengajar = $this->buildStatistikMengajar($jadwalData);
        $publikasi10Tahun = $this->buildPublikasi($publikasiData);
        $publikasiTerakhir5 = $this->buildPublikasiTerakhir($publikasiData);
        $penelitianList = $this->buildPenelitian($penelitianData);
        $sintaIndexasi = $this->buildSintaIndexasi($publikasiData);

        $viewData = [
            'title' => 'Profil Dosen - ' . ($profile['nama'] ?? $nip),
            'profile' => $profile,
            'jadwalHariIni' => $jadwalHariIni,
            'statistikMengajar' => $statistikMengajar,
            'publikasi10Tahun' => $publikasi10Tahun,
            'publikasiTerakhir5' => $publikasiTerakhir5,
            'penelitianList' => $penelitianList,
            'sintaIndexasi' => $sintaIndexasi,
            'nip' => $nip,
            'semester' => $semester,
        ];

        Cache::put($cacheKey, $viewData, now()->addMinutes(10));

        return view('academic.dosen-profile', $viewData);
    }

    private function getDosenData(string $nip): array
    {
        try {
            $response = $this->pegawaiService->getData(['nip' => $nip]);
            if ($response->success && !empty($response->data)) {
                return is_array($response->data) ? reset($response->data) : $response->data;
            }
        } catch (\Exception $e) {
            Log::warning("Gagal ambil data dosen {$nip}: " . $e->getMessage());
        }
        return ['nip' => $nip, 'nama' => '-', 'jabatan' => '-', 'unit_kerja' => '-', 'email' => '-'];
    }

    private function getJadwalData(string $nip, string $semester): array
    {
        try {
            $response = $this->penjadwalanService->getData([
                'semester' => $semester,
                'nip' => $nip,
            ]);
            if ($response->success) {
                return $response->data ?? [];
            }
        } catch (\Exception $e) {
            Log::warning("Gagal ambil jadwal dosen {$nip}: " . $e->getMessage());
        }
        return [];
    }

    private function getPublikasiData(string $nip): array
    {
        try {
            $response = $this->sippService->getPublikasi([
                'nip' => $nip,
                'per_page' => 50,
            ]);
            if ($response->success) {
                return is_array($response->data) ? $response->data : [];
            }
        } catch (\Exception $e) {
            Log::warning("Gagal ambil publikasi dosen {$nip}: " . $e->getMessage());
        }
        return [];
    }

    private function getPenelitianData(string $nip): array
    {
        try {
            $response = $this->sippService->getPenelitian([
                'nip' => $nip,
                'per_page' => 50,
            ]);
            if ($response->success) {
                return is_array($response->data) ? $response->data : [];
            }
        } catch (\Exception $e) {
            Log::warning("Gagal ambil penelitian dosen {$nip}: " . $e->getMessage());
        }
        return [];
    }

    private function buildProfile(array $dosenData): array
    {
        $nama = $dosenData['nama'] ?? '-';
        $gelarDepan = $dosenData['gelar_depan'] ?? '';
        $gelarBelakang = $dosenData['gelar_belakang'] ?? '';
        $namaLengkap = trim($gelarDepan . ' ' . $nama . ' ' . $gelarBelakang);

        return [
            'nip' => $dosenData['nip'] ?? '-',
            'nama' => $namaLengkap,
            'jabatan' => $dosenData['jabatan'] ?? '-',
            'unit_kerja' => $dosenData['unit_kerja'] ?? '-',
            'pangkat' => $dosenData['pangkat'] ?? '-',
            'email' => $dosenData['email'] ?? '-',
        ];
    }

    private function buildJadwalHariIni(array $jadwalData): array
    {
        $jadwalHari = [];
        $data = $jadwalData['data'] ?? [];
        $hariIni = now()->locale('id')->isoFormat('dddd');

        foreach ($data as $mk) {
            foreach ($mk['jadwal'] ?? [] as $jadwal) {
                foreach ($jadwal['waktu_kuliah'] ?? [] as $waktu) {
                    $hari = $waktu['hari'] ?? '';
                    if (strtolower($hari) === strtolower($hariIni)) {
                        $kelasList = collect($jadwal['kelas'] ?? [])->pluck('nama_kelas')->implode(', ');
                        $jadwalHari[] = [
                            'nama_mk' => $mk['mata_kuliah']['nama'] ?? '-',
                            'kelas' => $kelasList ?: '-',
                            'jam' => ($waktu['jam_mulai'] ?? '-') . ' - ' . ($waktu['jam_selesai'] ?? '-'),
                            'ruang' => $waktu['ruang']['nama_ruang'] ?? '-',
                            'status' => $jadwal['status'] ?? 'Belum Terlaksana',
                        ];
                    }
                }
            }
        }
        return $jadwalHari;
    }

    private function buildStatistikMengajar(array $jadwalData): array
    {
        $data = $jadwalData['data'] ?? [];
        $totalSKS = 0;
        $countMK = 0;

        foreach ($data as $mk) {
            $totalSKS += (int) ($mk['mata_kuliah']['sks'] ?? 0);
            $countMK++;
        }

        return ['total_sks' => $totalSKS, 'total_mk' => $countMK];
    }

    private function buildPublikasi(array $publikasiData): array
    {
        $items = $publikasiData['data'] ?? $publikasiData;
        if (!is_array($items)) return [];

        $publikasi = [];
        foreach (array_slice($items, 0, 10) as $item) {
            $publikasi[] = [
                'judul' => $item['judul'] ?? $item['title'] ?? '-',
                'penulis' => $item['penulis'] ?? $item['authors'] ?? '-',
                'journal' => $item['journal'] ?? $item['sumber'] ?? '-',
                'tahun' => $item['tahun'] ?? $item['year'] ?? '-',
                'tipe' => $item['tipe'] ?? $item['jenis'] ?? '-',
            ];
        }
        return $publikasi;
    }

    private function buildPublikasiTerakhir(array $publikasiData): array
    {
        $items = $publikasiData['data'] ?? $publikasiData;
        if (!is_array($items)) return [];

        $result = [];
        foreach (array_slice($items, 0, 5) as $item) {
            $result[] = [
                'judul' => $item['judul'] ?? $item['title'] ?? '-',
                'tahun' => $item['tahun'] ?? $item['year'] ?? '-',
            ];
        }
        return $result;
    }

    private function buildPenelitian(array $penelitianData): array
    {
        $items = $penelitianData['data'] ?? $penelitianData;
        if (!is_array($items)) return [];

        $result = [];
        foreach (array_slice($items, 0, 5) as $item) {
            $result[] = [
                'judul' => $item['judul'] ?? $item['title'] ?? '-',
                'tahun' => $item['tahun'] ?? $item['year'] ?? '-',
                'tipe' => $item['tipe'] ?? $item['jenis'] ?? '-',
            ];
        }
        return $result;
    }

    private function buildSintaIndexasi(array $publikasiData): array
    {
        $items = $publikasiData['data'] ?? $publikasiData;
        $count = is_array($items) ? count($items) : 0;

        return [
            'scopus' => ['dokumen' => $count, 'sitasi' => 0, 'h_index' => 0, 'i10_index' => 0, 'g_index' => 0],
            'google_scholar' => ['dokumen' => 0, 'sitasi' => 0, 'h_index' => 0, 'i10_index' => 0, 'g_index' => 0],
        ];
    }
}
