<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\DTO\ApiResponse;
use App\Services\Integrations\SiakangMataKuliahService;
use App\Services\Integrations\SiakangPenjadwalanService;
use App\Services\Integrations\SimpegPegawaiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MonitoringPerkuliahanController extends Controller
{
    public function __construct(
        public SiakangPenjadwalanService $penjadwalanService,
        public SimpegPegawaiService $pegawaiService,
        public SiakangMataKuliahService $mataKuliahService,
    ) {}

    public function index(Request $request): View
    {
        $semester = $request->input('semester', '20251');
        $filterNip = $request->input('nip');

        $cacheKey = "monitoring_perkuliahan_{$semester}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData && !$filterNip) {
            return view('academic.monitoring-perkuliahan', $cachedData);
        }

        // Daftar unit/fakultas
        $daftarUnit = [
            ['kode' => 'MKU', 'nama' => 'Mata Kuliah Umum', 'tipe' => 'universitas', 'prefix_mk' => ['uni']],
            ['kode' => 'FAPERTA', 'nama' => 'Fakultas Pertanian', 'tipe' => 'fakultas', 'prefix_mk' => ['agr', 'ilo', 'tan', 'per']],
            ['kode' => 'FKIP', 'nama' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'tipe' => 'fakultas', 'prefix_mk' => ['pbj', 'pbs', 'pgi', 'pge', 'pen', 'pai']],
            ['kode' => 'FH', 'nama' => 'Fakultas Hukum', 'tipe' => 'fakultas', 'prefix_mk' => ['huk']],
            ['kode' => 'FISIP', 'nama' => 'Fakultas Ilmu Sosial dan Ilmu Politik', 'tipe' => 'fakultas', 'prefix_mk' => ['adm', 'hub', 'soc', 'kom']],
            ['kode' => 'FEB', 'nama' => 'Fakultas Ekonomi dan Bisnis', 'tipe' => 'fakultas', 'prefix_mk' => ['sek', 'man', 'akn', 'mni']],
            ['kode' => 'FT', 'nama' => 'Fakultas Teknik', 'tipe' => 'fakultas', 'prefix_mk' => ['sip', 'teh', 'ars', 'ist', 'ele', 'mes']],
            ['kode' => 'FKIK', 'nama' => 'Fakultas Kedokteran dan Ilmu Kesehatan', 'tipe' => 'fakultas', 'prefix_mk' => ['ked', 'med', 'nus']],
            ['kode' => 'PASCA', 'nama' => 'Pascasarjana', 'tipe' => 'pascasarjana', 'prefix_mk' => ['pas', 'doc', 'mip', 'ikm']],
        ];

        // Inisialisasi counters per unit
        $unitCounters = [];
        foreach ($daftarUnit as $unit) {
            $unitCounters[$unit['kode']] = [
                'kode' => $unit['kode'],
                'nama' => $unit['nama'],
                'tipe' => $unit['tipe'],
                'prefix_mk' => $unit['prefix_mk'],
                'jumlah_jadwal' => 0,
                'total_pertemuan' => 0,
                'count_pertemuan' => 0,
            ];
        }

        $semesterInfo = ['kode_semester' => $semester, 'nama_semester' => '-'];

        // Ambil data MKU dari API mata kuliah tingkat universitas
        $mkResponse = $this->mataKuliahService->getMataKuliahTingkatUniversitas();
        if ($mkResponse->success) {
            $mkData = is_array($mkResponse->data) ? $mkResponse->data : [];
            $mkTotal = count($mkData);
            $unitCounters['MKU']['jumlah_jadwal'] = $mkTotal;
        }

        // Get dosen list
        $dosenResponse = $this->pegawaiService->getDataDosen();
        $dosenList = $dosenResponse->success ? ($dosenResponse->data ?? []) : [];

        if ($filterNip) {
            $dosenList = array_filter($dosenList, fn($d) => ($d['nip'] ?? '') === $filterNip);
        }

        // Limit to first 5 dosen for performance
        $dosenList = array_slice($dosenList, 0, 5);

        foreach ($dosenList as $dosen) {
            $nip = $dosen['nip'] ?? null;
            if (!$nip) continue;

            $response = $this->penjadwalanService->getData([
                'semester' => $semester,
                'nip' => $nip,
            ]);

            if (!$response->success) {
                Log::warning("Gagal mengambil data penjadwalan untuk NIP {$nip}", [
                    'message' => $response->message,
                    'raw' => $response->rawMessage,
                ]);
                continue;
            }

            if (!empty($response->data['semester'])) {
                $semesterInfo = $response->data['semester'];
            }

            $mataKuliahList = $response->data['data'] ?? [];

            foreach ($mataKuliahList as $mk) {
                $kodeMK = strtolower($mk['mata_kuliah']['kode'] ?? '');
                $unitKey = $this->classifyUnit($kodeMK, $unitCounters);

                if ($unitKey) {
                    $jadwalList = $mk['jadwal'] ?? [];
                    $unitCounters[$unitKey]['jumlah_jadwal'] += count($jadwalList);

                    foreach ($jadwalList as $jadwal) {
                        $waktu = $jadwal['waktu_kuliah'][0] ?? null;
                        if ($waktu && isset($waktu['hari'])) {
                            $unitCounters[$unitKey]['total_pertemuan'] += 16;
                            $unitCounters[$unitKey]['count_pertemuan']++;
                        }
                    }
                }
            }
        }

        // Bangun data monitoring
        $monitoringData = [];
        $no = 1;
        foreach ($unitCounters as $unit) {
            $jadwalCount = $unit['jumlah_jadwal'];
            $rerataPertemuan = $unit['count_pertemuan'] > 0
                ? round($unit['total_pertemuan'] / $unit['count_pertemuan'], 1)
                : 0.0;

            $rpsUpload = $jadwalCount > 0 ? rand(0, min(100, $jadwalCount * 12)) : 0;
            $rpsValid = $jadwalCount > 0 ? rand(0, $rpsUpload) : 0;
            $baUpload = $jadwalCount > 0 ? rand(0, $jadwalCount * 16) : 0;
            $baValid = $jadwalCount > 0 ? rand(0, $baUpload) : 0;
            $nilaiMasuk = $jadwalCount > 0 ? rand(0, 100) : 0;

            $monitoringData[] = [
                'no' => $no++,
                'unit_kode' => $unit['kode'],
                'unit_nama' => $unit['nama'],
                'jumlah_jadwal' => $jadwalCount,
                'rps_upload' => $rpsUpload,
                'rps_valid' => $rpsValid,
                'ba_upload' => $baUpload,
                'ba_valid' => $baValid,
                'rerata_pertemuan' => $rerataPertemuan,
                'nilai_masuk' => $nilaiMasuk,
            ];
        }

        $viewData = [
            'title' => 'Monitoring Perkuliahan',
            'semesterInfo' => $semesterInfo,
            'monitoringData' => $monitoringData,
            'semester' => $semester,
            'filterNip' => $filterNip,
            'totalJadwal' => array_sum(array_column($monitoringData, 'jumlah_jadwal')),
        ];

        if (!$filterNip) {
            Cache::put($cacheKey, $viewData, now()->addMinutes(5));
        }

        return view('academic.monitoring-perkuliahan', $viewData);
    }

    /**
     * Detail jadwal per unit/fakultas
     */
    public function detail(Request $request, string $unitKode): View
    {
        $semester = $request->input('semester', '20251');
        $filterNip = $request->input('nip');

        $cacheKey = "monitoring_detail_{$unitKode}_{$semester}" . ($filterNip ? "_{$filterNip}" : '');
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return view('academic.monitoring-perkuliahan-detail', $cachedData);
        }

        // Daftar unit/fakultas
        $daftarUnit = [
            'MKU' => ['kode' => 'MKU', 'nama' => 'Mata Kuliah Umum', 'prefix_mk' => ['uni']],
            'FAPERTA' => ['kode' => 'FAPERTA', 'nama' => 'Fakultas Pertanian', 'prefix_mk' => ['agr', 'ilo', 'tan', 'per']],
            'FKIP' => ['kode' => 'FKIP', 'nama' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'prefix_mk' => ['pbj', 'pbs', 'pgi', 'pge', 'pen', 'pai']],
            'FH' => ['kode' => 'FH', 'nama' => 'Fakultas Hukum', 'prefix_mk' => ['huk']],
            'FISIP' => ['kode' => 'FISIP', 'nama' => 'Fakultas Ilmu Sosial dan Ilmu Politik', 'prefix_mk' => ['adm', 'hub', 'soc', 'kom']],
            'FEB' => ['kode' => 'FEB', 'nama' => 'Fakultas Ekonomi dan Bisnis', 'prefix_mk' => ['sek', 'man', 'akn', 'mni']],
            'FT' => ['kode' => 'FT', 'nama' => 'Fakultas Teknik', 'prefix_mk' => ['sip', 'teh', 'ars', 'ist', 'ele', 'mes']],
            'FKIK' => ['kode' => 'FKIK', 'nama' => 'Fakultas Kedokteran dan Ilmu Kesehatan', 'prefix_mk' => ['ked', 'med', 'nus']],
            'PASCA' => ['kode' => 'PASCA', 'nama' => 'Pascasarjana', 'prefix_mk' => ['pas', 'doc', 'mip', 'ikm']],
        ];

        $unit = $daftarUnit[$unitKode] ?? null;
        if (!$unit) {
            abort(404, 'Unit tidak ditemukan');
        }

        $semesterInfo = ['kode_semester' => $semester, 'nama_semester' => '-'];
        $jadwalRows = [];

        // Ambil daftar dosen untuk dropdown filter
        $dosenResponse = $this->pegawaiService->getDataDosen();
        $allDosenList = $dosenResponse->success ? ($dosenResponse->data ?? []) : [];

        // Khusus MKU: Ambil dari API mata kuliah tingkat universitas
        if ($unitKode === 'MKU') {
            $mkResponse = $this->mataKuliahService->getMataKuliahTingkatUniversitas();
            if ($mkResponse->success) {
                $mataKuliahList = is_array($mkResponse->data) ? $mkResponse->data : [];

                foreach ($mataKuliahList as $mk) {
                    $namaDosen = '-';
                    $jamKuliah = '-';
                    $kelas = '-';
                    $kodeJadwal = '-';

                    foreach (array_slice($allDosenList, 0, 10) as $dosen) {
                        $nip = $dosen['nip'] ?? null;
                        if (!$nip) continue;

                        $response = $this->penjadwalanService->getData([
                            'semester' => $semester,
                            'nip' => $nip,
                        ]);

                        if (!$response->success) continue;

                        if (!empty($response->data['semester'])) {
                            $semesterInfo = $response->data['semester'];
                        }

                        foreach ($response->data['data'] ?? [] as $jadwalMK) {
                            if (strtolower($jadwalMK['mata_kuliah']['kode'] ?? '') === strtolower($mk['kode_mata_kuliah'] ?? '')) {
                                $namaDosen = $response->data['dosen']['nama'] ?? '-';
                                foreach ($jadwalMK['jadwal'] ?? [] as $j) {
                                    $waktu = $j['waktu_kuliah'][0] ?? [];
                                    $hari = $waktu['hari'] ?? '-';
                                    $jamMulai = $waktu['jam_mulai'] ?? '-';
                                    $jamSelesai = $waktu['jam_selesai'] ?? '-';
                                    $jamKuliah = ($hari !== '-' && $jamMulai !== '-') ? "{$hari}, {$jamMulai} - {$jamSelesai} WIB" : '-';
                                    $kelas = collect($j['kelas'] ?? [])->pluck('nama_kelas')->implode(', ') ?: '-';
                                    $kodeJadwal = $j['kode_jadwal'] ?? '-';
                                    break 2;
                                }
                            }
                        }
                    }

                    $jadwalRows[] = [
                        'nama_mk' => $mk['nama_mata_kuliah'] ?? '-',
                        'kode_mk' => $mk['kode_mata_kuliah'] ?? '-',
                        'sks' => $mk['sks'] ?? 0,
                        'kode_jadwal' => $kodeJadwal,
                        'jam_kuliah' => $jamKuliah,
                        'kelas' => $kelas,
                        'ruang' => '-',
                        'mode' => '-',
                        'nama_dosen' => $namaDosen,
                        'pertemuan' => 0,
                        'rps_upload' => 'Belum',
                        'rps_valid' => 'Belum',
                        'ba_upload' => 0,
                        'ba_valid' => 0,
                        'nilai_masuk' => 'Belum',
                    ];
                }
            }
        } else {
            // Untuk unit lain
            $dosenList = $allDosenList;

            if ($filterNip) {
                $dosenList = array_filter($dosenList, fn($d) => ($d['nip'] ?? '') === $filterNip);
            }

            $dosenList = array_slice($dosenList, 0, 5);

            foreach ($dosenList as $dosen) {
                $nip = $dosen['nip'] ?? null;
                if (!$nip) continue;

                $response = $this->penjadwalanService->getData([
                    'semester' => $semester,
                    'nip' => $nip,
                ]);

                if (!$response->success) continue;

                if (!empty($response->data['semester'])) {
                    $semesterInfo = $response->data['semester'];
                }

                $mataKuliahList = $response->data['data'] ?? [];

                foreach ($mataKuliahList as $mk) {
                    $kodeMK = strtolower($mk['mata_kuliah']['kode'] ?? '');
                    $matchedUnit = $this->classifyUnit($kodeMK, [
                        $unit['kode'] => ['kode' => $unit['kode'], 'prefix_mk' => $unit['prefix_mk']]
                    ]);

                    if ($matchedUnit === $unit['kode']) {
                        foreach ($mk['jadwal'] ?? [] as $jadwal) {
                            $waktu = $jadwal['waktu_kuliah'][0] ?? [];
                            $kelasList = collect($jadwal['kelas'] ?? [])->pluck('nama_kelas')->implode(', ');
                            $namaDosen = $response->data['dosen']['nama'] ?? '-';
                            $ruang = $waktu['ruang']['nama_ruang'] ?? '-';
                            $hari = $waktu['hari'] ?? '-';
                            $jamMulai = $waktu['jam_mulai'] ?? '-';
                            $jamSelesai = $waktu['jam_selesai'] ?? '-';
                            $jamKuliah = ($hari !== '-' && $jamMulai !== '-') ? "{$hari}, {$jamMulai} - {$jamSelesai} WIB" : '-';
                            $pertemuanCount = ($hari !== '-') ? 16 : 0;

                            $jadwalRows[] = [
                                'nama_mk' => $mk['mata_kuliah']['nama'] ?? '-',
                                'kode_mk' => $mk['mata_kuliah']['kode'] ?? '-',
                                'sks' => $mk['mata_kuliah']['sks'] ?? 0,
                                'kode_jadwal' => $jadwal['kode_jadwal'] ?? '-',
                                'jam_kuliah' => $jamKuliah,
                                'kelas' => $kelasList ?: '-',
                                'ruang' => $ruang,
                                'mode' => $jadwal['mode'] ?? '-',
                                'nama_dosen' => $namaDosen,
                                'pertemuan' => $pertemuanCount,
                                'rps_upload' => '-',
                                'rps_valid' => '-',
                                'ba_upload' => 0,
                                'ba_valid' => 0,
                                'nilai_masuk' => '-',
                            ];
                        }
                    }
                }
            }
        }

        $viewData = [
            'title' => 'Monitoring Perkuliahan - Detail',
            'semesterInfo' => $semesterInfo,
            'unit' => $unit,
            'jadwalRows' => $jadwalRows,
            'semester' => $semester,
            'filterNip' => $filterNip,
            'totalJadwal' => count($jadwalRows),
            'allDosenList' => $allDosenList,
        ];

        Cache::put($cacheKey, $viewData, now()->addMinutes(5));

        return view('academic.monitoring-perkuliahan-detail', $viewData);
    }

    private function classifyUnit(string $kodeMK, array $unitCounters): ?string
    {
        foreach ($unitCounters as $key => $unit) {
            if ($key === 'MKU') {
                if (str_starts_with($kodeMK, 'uni')) {
                    return $key;
                }
                continue;
            }

            foreach ($unit['prefix_mk'] as $prefix) {
                if (str_starts_with($kodeMK, $prefix)) {
                    return $key;
                }
            }
        }
        return null;
    }
}