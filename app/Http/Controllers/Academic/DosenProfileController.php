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
        $pengabdianData = $this->getPengabdianData($nip);

        $profile = $this->buildProfile($dosenData);
        $jadwalHariIni = $this->buildJadwalHariIni($jadwalData);
        $statistikMengajar = $this->buildStatistikMengajar($jadwalData);
        $publikasi10Tahun = $this->buildPublikasi($publikasiData);
        $publikasiTerakhir5 = $this->buildPublikasiTerakhir($publikasiData);
        $penelitianList = $this->buildPenelitian($penelitianData);
        $pengabdianList = $this->buildPengabdian($pengabdianData);
        $sintaIndexasi = $this->buildSintaIndexasi($publikasiData);

        $viewData = [
            'title' => 'Profil Dosen - ' . ($profile['nama'] ?? $nip),
            'profile' => $profile,
            'jadwalHariIni' => $jadwalHariIni,
            'statistikMengajar' => $statistikMengajar,
            'publikasi10Tahun' => $publikasi10Tahun,
            'publikasiTerakhir5' => $publikasiTerakhir5,
            'penelitianList' => $penelitianList,
            'pengabdianList' => $pengabdianList,
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
                $data = $response->data;
                if (is_array($data)) {
                    $first = reset($data);
                    return is_array($first) ? $first : $data;
                }
                return (array) $data;
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
                'page' => 1,
                'per_page' => 5,
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
                'page' => 1,
                'per_page' => 5,
            ]);
            if ($response->success) {
                return is_array($response->data) ? $response->data : [];
            }
        } catch (\Exception $e) {
            Log::warning("Gagal ambil penelitian dosen {$nip}: " . $e->getMessage());
        }
        return [];
    }

    private function getPengabdianData(string $nip): array
    {
        try {
            $response = $this->sippService->getPengabdian([
                'nip' => $nip,
                'page' => 1,
                'per_page' => 5,
            ]);
            if ($response->success) {
                return is_array($response->data) ? $response->data : [];
            }
        } catch (\Exception $e) {
            Log::warning("Gagal ambil pengabdian dosen {$nip}: " . $e->getMessage());
        }
        return [];
    }

    private function buildProfile(array $dosenData): array
    {
        $nama = $dosenData['namaPegawai'] ?? $dosenData['nama'] ?? '-';
        $gelarDepan = $dosenData['gelarDepan'] ?? $dosenData['gelar_depan'] ?? '';
        $gelarBelakang = $dosenData['gelarBelakang'] ?? $dosenData['gelar_belakang'] ?? '';
        $namaLengkap = trim($gelarDepan . ' ' . $nama . ' ' . $gelarBelakang);

        return [
            'nip' => $dosenData['nip'] ?? '-',
            'nama' => $namaLengkap,
            'jabatan' => $dosenData['jabatan'] ?? '-',
            'unit_kerja' => $dosenData['unitKerja'] ?? $dosenData['unit_kerja'] ?? '-',
            'unitKerja' => $dosenData['unitKerja'] ?? $dosenData['unit_kerja'] ?? '-',
            'pangkat' => $dosenData['pangkat'] ?? '-',
            'email' => $dosenData['emailPegawai'] ?? $dosenData['email'] ?? '-',
            'noTlp' => $dosenData['noTlp'] ?? '-',
            'statusKerja' => $dosenData['statusKerja'] ?? '-',
            'levelPegawai' => $dosenData['levelPegawai'] ?? '-',
        ];
    }

    private function buildJadwalHariIni(array $jadwalData): array
    {
        $jadwalHari = [];
        $data = $jadwalData['data'] ?? (isset($jadwalData[0]) ? $jadwalData : []);
        if (!is_array($data)) {
            return [];
        }

        $hariIniName = strtolower(now()->locale('id')->isoFormat('dddd'));
        $hariIniNum = now()->dayOfWeekIso;

        foreach ($data as $mk) {
            $namaMK = $mk['mata_kuliah']['nama'] ?? $mk['nama_mk'] ?? '-';
            foreach ($mk['jadwal'] ?? [] as $jadwal) {
                foreach ($jadwal['waktu_kuliah'] ?? [] as $waktu) {
                    $hariName = strtolower($waktu['hari'] ?? '');
                    $hariNum = (int) ($waktu['hari_numeric'] ?? 0);

                    if ($hariName === $hariIniName || ($hariNum > 0 && $hariNum === $hariIniNum)) {
                        $kelasList = collect($jadwal['kelas'] ?? [])->pluck('nama_kelas')->filter()->implode(', ');
                        $ruangNama = $waktu['ruang']['nama_ruang'] ?? (is_string($waktu['ruang'] ?? null) ? $waktu['ruang'] : '-');
                        $jamMulai = $waktu['jam_mulai'] ?? '-';
                        $jamSelesai = $waktu['jam_selesai'] ?? '-';

                        $jadwalHari[] = [
                            'nama_mk' => $namaMK,
                            'kelas' => $kelasList ?: '-',
                            'jam' => "{$jamMulai} - {$jamSelesai}",
                            'ruang' => $ruangNama,
                            'mode' => $jadwal['mode'] ?? 'OFFLINE',
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
        $data = $jadwalData['data'] ?? (isset($jadwalData[0]) ? $jadwalData : []);
        $totalSKS = 0;
        $countMK = is_array($data) ? count($data) : 0;

        if (is_array($data)) {
            foreach ($data as $mk) {
                $totalSKS += (int) ($mk['mata_kuliah']['sks'] ?? 0);
            }
        }

        return ['total_sks' => $totalSKS, 'total_mk' => $countMK];
    }

    private function extractYear(array $item): string
    {
        $detail = $item['detail'] ?? [];

        foreach (['tahun_pelaksanaan', 'tahun_kegiatan', 'tahun_usulan', 'tahun', 'year'] as $field) {
            $val = trim((string) ($detail[$field] ?? $item[$field] ?? ''));
            if (preg_match('/^\d{4}$/', $val)) {
                return $val;
            }
        }

        foreach (['tanggal_berlaku', 'tanggal_sk_penugasan'] as $dateField) {
            $val = trim((string) ($item[$dateField] ?? $detail[$dateField] ?? ''));
            if (preg_match('/^(\d{4})-\d{2}-\d{2}/', $val, $matches)) {
                return $matches[1];
            }
        }

        return '-';
    }

    private function buildPublikasi(array $publikasiData): array
    {
        $items = $publikasiData['data'] ?? $publikasiData;
        if (!is_array($items)) return [];

        $publikasi = [];
        foreach (array_slice($items, 0, 10) as $item) {
            $publikasi[] = [
                'judul' => $item['judul_portofolio'] ?? $item['judul'] ?? $item['title'] ?? '-',
                'penulis' => $item['penulis'] ?? $item['authors'] ?? '-',
                'journal' => $item['journal'] ?? $item['sumber'] ?? '-',
                'tahun' => $this->extractYear($item),
                'tipe' => $item['jenis_portofolio'] ?? $item['detail']['kategori_kegiatan'] ?? $item['tipe'] ?? $item['jenis'] ?? '-',
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
                'judul' => $item['judul_portofolio'] ?? $item['judul'] ?? $item['title'] ?? '-',
                'tahun' => $this->extractYear($item),
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
                'judul' => $item['judul_portofolio'] ?? $item['judul'] ?? $item['title'] ?? '-',
                'tahun' => $this->extractYear($item),
                'tipe' => $item['jenis_portofolio'] ?? $item['detail']['kategori_kegiatan'] ?? $item['tipe'] ?? $item['jenis'] ?? '-',
                'status_verifikasi' => $item['status_verifikasi_label'] ?? $item['status_verifikasi'] ?? '-',
                'lokasi' => $item['detail']['lokasi'] ?? '-',
            ];
        }
        return $result;
    }

    private function buildPengabdian(array $pengabdianData): array
    {
        $items = $pengabdianData['data'] ?? $pengabdianData;
        if (!is_array($items)) return [];

        $result = [];
        foreach (array_slice($items, 0, 5) as $item) {
            $result[] = [
                'judul' => $item['judul_portofolio'] ?? $item['judul'] ?? $item['title'] ?? '-',
                'tahun' => $this->extractYear($item),
                'tipe' => $item['jenis_portofolio'] ?? $item['detail']['kategori_kegiatan'] ?? $item['tipe'] ?? $item['jenis'] ?? '-',
                'status_verifikasi' => $item['status_verifikasi_label'] ?? $item['status_verifikasi'] ?? '-',
                'lokasi' => $item['detail']['lokasi'] ?? '-',
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
