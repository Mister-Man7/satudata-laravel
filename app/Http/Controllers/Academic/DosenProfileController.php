<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Integrations\SiakangPenjadwalanService;
use App\Services\Integrations\SimpegPegawaiService;
use App\Services\Integrations\SIPPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DosenProfileController extends Controller
{
    public function __construct(
        public SimpegPegawaiService $pegawaiService,
        public SIPPService $sippService,
        public SiakangPenjadwalanService $penjadwalanService,
    ) {}

    public function show(Request $request, string $nip): View
    {
        $nip = trim($nip);
        $semester = $this->getActiveSemester($request->input('semester'));
        $semesterNama = $this->getSemesterLabel($semester);

        $cacheKey = "dosen_profile_{$nip}_{$semester}";
        if ($request->has('refresh')) {
            Cache::forget($cacheKey);
            Cache::forget("sipp_metrics_{$nip}_{$semester}");
        }

        $cachedData = Cache::get($cacheKey);

        if ($cachedData && !empty($cachedData['profile']['nip']) && ($cachedData['profile']['nama'] ?? '-') !== '-' && empty($cachedData['isSippLoading'])) {
            return view('academic.dosen-profile', $cachedData);
        }

        $dosenData      = $this->getDosenData($nip);
        $publikasiData  = $this->getPublikasiData($nip, $semester);
        $penelitianData = $this->getPenelitianData($nip, $semester);
        $pengabdianData = $this->getPengabdianData($nip, $semester);
        $jadwalData     = $this->getJadwalData($nip, $semester);

        $profile            = $this->buildProfile($dosenData);
        $publikasi10Tahun   = $this->buildPublikasiTerakhir($publikasiData);
        $penelitianList     = $this->buildPenelitian($penelitianData);
        $pengabdianList     = $this->buildPengabdian($pengabdianData);
        $sintaIndexasi      = $this->buildSintaIndexasi($publikasiData);
        $jadwalHariIni      = $this->buildJadwalHariIni($jadwalData);
        $statistikMengajar  = $this->buildStatistikMengajar($jadwalData);

        $viewData = [
            'title'             => 'Profil Dosen - ' . ($profile['nama'] ?? 'Untirta'),
            'profile'           => $profile,
            'publikasi10Tahun'  => $publikasi10Tahun,
            'penelitianList'    => $penelitianList,
            'pengabdianList'    => $pengabdianList,
            'sintaIndexasi'     => $sintaIndexasi,
            'jadwalHariIni'     => $jadwalHariIni,
            'statistikMengajar' => $statistikMengajar,
            'mataKuliahList'    => $jadwalData,
            'nip'               => $nip,
            'semester'          => $semester,
            'semesterNama'      => $semesterNama,
        ];

        Cache::put($cacheKey, $viewData, now()->addMinutes(30));

        return view('academic.dosen-profile', $viewData);
    }

    public function sippMetrics(Request $request, string $nip): \Illuminate\Http\JsonResponse
    {
        @set_time_limit(120); // Alokasi waktu cukup jika cache & database kosong dan harus fetch live SIPP
        $nip = trim($nip);
        $semester = $this->getActiveSemester($request->input('semester'));

        // 1. CEK CACHE (Prioritas pertama: jika sudah ada di cache, kembalikan seketika)
        $cachedMetrics = Cache::get("sipp_metrics_{$nip}_{$semester}");
        if ($cachedMetrics && is_array($cachedMetrics)) {
            return response()->json([
                'success'  => true,
                'semester' => $semester,
                'metrics'  => $cachedMetrics,
                'source'   => 'cache',
            ]);
        }

        // 2. LOCAL-FIRST: Cek langsung dari database MySQL dosen_sipps
        $dbRecord = \App\Models\DosenSipp::where('nip', $nip)->where('semester', $semester)->first();
        if ($dbRecord) {
            $dbMetrics = [
                'sinta12'                  => (int) $dbRecord->sinta12,
                'sinta36'                  => (int) $dbRecord->sinta36,
                'jurnal_internasional_q'   => (int) $dbRecord->jurnal_internasional_q,
                'jurnal_internasional_pbb' => (int) $dbRecord->jurnal_internasional_pbb,
                'jurnal_nasional_issn'     => (int) $dbRecord->jurnal_nasional_issn,
                'pengembangan'             => (int) $dbRecord->pengembangan,
                'pengabdian_masyarakat'    => (int) $dbRecord->pengabdian_masyarakat,
                'buku_referensi'           => (int) $dbRecord->buku_referensi,
            ];

            // Cache data dari database
            Cache::put("sipp_metrics_{$nip}_{$semester}", $dbMetrics, now()->addMinutes(10));

            // PICU BACKGROUND REVALIDATION KE API SIPP DENGAN DEFER
            if (function_exists('defer')) {
                defer(function () use ($nip, $semester, $dbMetrics) {
                    try {
                        $apiMetrics = $this->sippService->getDosenSippSummary($nip, $semester);
                        if (is_array($apiMetrics) && array_sum($apiMetrics) > 0) {
                            if ($apiMetrics != $dbMetrics) {
                                \App\Models\DosenSipp::updateOrCreate(
                                    ['nip' => $nip, 'semester' => $semester],
                                    $apiMetrics
                                );
                            }
                            Cache::put("sipp_metrics_{$nip}_{$semester}", $apiMetrics, now()->addHours(6));
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Background revalidation SIPP metrics NIP {$nip} gagal: " . $e->getMessage());
                    }
                });
            }

            return response()->json([
                'success'  => true,
                'semester' => $semester,
                'metrics'  => $dbMetrics,
                'source'   => 'database_local_first',
            ]);
        }

        // 3. Fallback jika database belum ada data untuk semester ini: Panggil API SIPP secara sinkron
        try {
            $apiMetrics = $this->sippService->getDosenSippSummary($nip, $semester);
            if (is_array($apiMetrics) && array_sum($apiMetrics) > 0) {
                Cache::put("sipp_metrics_{$nip}_{$semester}", $apiMetrics, now()->addHours(6));
                try {
                    \App\Models\DosenSipp::updateOrCreate(
                        ['nip' => $nip, 'semester' => $semester],
                        $apiMetrics
                    );
                } catch (\Throwable $e) {
                    Log::warning("Gagal simpan ke database dosen_sipps NIP {$nip}: " . $e->getMessage());
                }

                return response()->json([
                    'success'  => true,
                    'semester' => $semester,
                    'metrics'  => $apiMetrics,
                    'source'   => 'api_live',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("SIPP API timeout / error untuk NIP {$nip}: " . $e->getMessage());
        }

        // 4. Jika tidak ada di Cache, API 0/gagal, dan tidak ada di Database: kembalikan 0
        $zeroMetrics = [
            'sinta12'                  => 0,
            'sinta36'                  => 0,
            'jurnal_internasional_q'   => 0,
            'jurnal_internasional_pbb' => 0,
            'jurnal_nasional_issn'     => 0,
            'pengembangan'             => 0,
            'pengabdian_masyarakat'    => 0,
            'buku_referensi'           => 0,
        ];
        Cache::put("sipp_metrics_{$nip}_{$semester}", $zeroMetrics, now()->addMinutes(30));

        return response()->json([
            'success'  => true,
            'semester' => $semester,
            'metrics'  => $zeroMetrics,
            'source'   => 'zero_fallback',
        ]);
    }

    protected function getActiveSemester(?string $requested = null): string
    {
        if (!empty($requested)) {
            return $requested;
        }

        if (request()->has('semester') && !empty(request('semester'))) {
            return request('semester');
        }

        return '20252';
    }

    protected function getSemesterLabel(string $kodeSemester): string
    {
        $tahun = substr($kodeSemester, 0, 4);
        $jenis = substr($kodeSemester, 4, 1);
        $tahunPlus = ((int) $tahun) + 1;

        if ($jenis === '2') {
            return "{$tahun}/{$tahunPlus} Genap";
        }

        return "{$tahun}/{$tahunPlus} Gasal";
    }

    private function getDosenData(string $nip): array
    {
        $cacheKey = "dosen_item_{$nip}";
        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached) && !empty($cached['nip']) && ($cached['nama'] ?? '-') !== '-') {
            return $cached;
        }

        // 1. LOCAL-FIRST: Ambil langsung dari record MySQL database pegawais
        try {
            $dbPegawai = \App\Models\Pegawai::where('nip', trim($nip))->first();
            if ($dbPegawai) {
                $dbItem = null;
                if (is_array($dbPegawai->payload) && !empty($dbPegawai->payload)) {
                    $dbItem = $this->pegawaiService->normalizePegawaiItem($dbPegawai->payload);
                } else {
                    $dbItem = [
                        'nip'                  => $dbPegawai->nip,
                        'kd_pegawai'           => $dbPegawai->kode_data,
                        'nama'                 => $dbPegawai->nama,
                        'namaPegawai'          => $dbPegawai->nama,
                        'nama_pegawai_lengkap' => trim(($dbPegawai->gelar_depan ? $dbPegawai->gelar_depan . ' ' : '') . $dbPegawai->nama . ($dbPegawai->gelar_belakang ? ', ' . $dbPegawai->gelar_belakang : '')),
                        'gelar_depan'          => $dbPegawai->gelar_depan,
                        'gelar_belakang'       => $dbPegawai->gelar_belakang,
                        'email'                => $dbPegawai->email,
                        'emailPegawai'         => $dbPegawai->email,
                        'noTlp'                => $dbPegawai->no_tlp,
                        'unitKerja'            => $dbPegawai->unit_kerja,
                        'unit_kerja'           => $dbPegawai->unit_kerja,
                        'jabatan'              => $dbPegawai->jabatan,
                        'nama_jabatan'         => $dbPegawai->jabatan,
                        'pangkat'              => $dbPegawai->pangkat,
                        'statusKerja'          => $dbPegawai->status_kerja,
                        'nama_stspegawai'      => $dbPegawai->status_kerja,
                        'levelPegawai'         => $dbPegawai->level_pegawai,
                        'nama_level_pegawai'   => $dbPegawai->level_pegawai,
                    ];
                }

                if ($dbItem) {
                    Cache::put($cacheKey, $dbItem, now()->addMinutes(10));

                    // PICU BACKGROUND REVALIDATION KE API SIMPEG DENGAN DEFER
                    if (function_exists('defer')) {
                        defer(function () use ($nip, $cacheKey) {
                            try {
                                $this->syncSingleDosenFromApi($nip, $cacheKey);
                            } catch (\Throwable $e) {
                                Log::warning("Background revalidation SIMPEG dosen {$nip} gagal: " . $e->getMessage());
                            }
                        });
                    }

                    return $dbItem;
                }
            }
        } catch (\Throwable $e) {}

        // 2. Fallback jika tidak ditemukan di database: Panggil sinkron dari API
        $fresh = $this->syncSingleDosenFromApi($nip, $cacheKey);
        if (!empty($fresh)) {
            return $fresh;
        }

        return ['nip' => $nip, 'nama' => '-', 'jabatan' => '-'];
    }

    public function syncSingleDosenFromApi(string $nip, string $cacheKey): ?array
    {
        try {
            $response = $this->pegawaiService->getData(['nip' => $nip]);
            if ($response->success && !empty($response->data)) {
                $data = $response->data;
                if (is_array($data)) {
                    if (isset($data['data']) && is_array($data['data'])) {
                        $data = $data['data'];
                    }
                    $matched = null;
                    if (isset($data[0]) && is_array($data[0])) {
                        foreach ($data as $item) {
                            if (isset($item['nip']) && trim((string) $item['nip']) === trim($nip)) {
                                $matched = $item;
                                break;
                            }
                        }
                    }
                    if (!$matched && !empty($data)) {
                        $first = reset($data);
                        $matched = is_array($first) ? $first : (array) $data;
                    }

                    if ($matched && !empty($matched['nip'])) {
                        try {
                            \App\Models\Pegawai::updateOrCreate(
                                ['nip' => trim($nip)],
                                [
                                    'kode_data'      => $matched['kd_pegawai'] ?? $matched['kodeData'] ?? null,
                                    'id_sdm'         => $matched['id_sdm'] ?? $matched['idSDM'] ?? null,
                                    'nama'           => $matched['namaPegawai'] ?? $matched['nama_pegawai'] ?? $matched['nama'] ?? '-',
                                    'gelar_depan'    => $matched['gelar_depan'] ?? $matched['gelarDepan'] ?? null,
                                    'gelar_belakang' => $matched['gelar_belakang'] ?? $matched['gelarBelakang'] ?? null,
                                    'email'          => $matched['emailPegawai'] ?? $matched['email'] ?? null,
                                    'no_tlp'         => $matched['noTlp'] ?? $matched['no_tlp'] ?? null,
                                    'unit_kerja'     => $matched['unitKerja'] ?? $matched['unit_kerja'] ?? null,
                                    'unit_kerja_id'  => $matched['unitKerja_id'] ?? $matched['unit_kerja_id'] ?? null,
                                    'jabatan'        => $matched['nama_jabatan'] ?? $matched['jabatan'] ?? null,
                                    'jabatan_id'     => $matched['jabatan_id'] ?? null,
                                    'pangkat'        => $matched['pangkat'] ?? $matched['nama_pangkat'] ?? null,
                                    'pangkat_id'     => $matched['pangkat_id'] ?? null,
                                    'status_kerja'   => $matched['nama_stspegawai'] ?? $matched['statusKerja'] ?? null,
                                    'level_pegawai'  => $matched['nama_level_pegawai'] ?? $matched['levelPegawai'] ?? null,
                                    'payload'        => $matched,
                                ]
                            );
                        } catch (\Throwable $e) {}

                        Cache::put($cacheKey, $matched, now()->addHours(6));
                        return $matched;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Gagal sync data dosen {$nip} dari API SIMPEG: " . $e->getMessage());
        }
        return null;
    }

    private function getJadwalData(string $nip, string $semester): array
    {
        $cacheKey = "dosen_jadwal_{$nip}_{$semester}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        try {
            $response = $this->penjadwalanService->getData([
                'semester' => $semester,
                'nip'      => $nip,
            ]);
            if ($response->success && !empty($response->data)) {
                $payload = is_array($response->data) ? $response->data : [];
                $items = $payload['data'] ?? (isset($payload[0]) ? $payload : []);
                if (!empty($items)) {
                    Cache::put($cacheKey, $items, now()->addHours(6));
                    return $items;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Gagal ambil jadwal dosen {$nip}: " . $e->getMessage());
        }
        return [];
    }

    /**
     * [Local-First SWR]: Ambil portofolio Tri Dharma langsung dari database lokal (dosen_sipps),
     * lalu jadwalkan revalidasi background API SIPP via defer().
     */
    private function getPortfolioData(string $type, string $nip, string $semester): array
    {
        $cacheKey = "dosen_{$type}_{$nip}_{$semester}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        // 1. Ambil salinan lokal dari MySQL dosen_sipps
        try {
            $dbData = \App\Models\DosenSipp::where('nip', trim($nip))
                ->where('semester', $semester)
                ->value($type);
            if (empty($dbData)) {
                $dbData = \App\Models\DosenSipp::where('nip', trim($nip))
                    ->whereNotNull($type)
                    ->latest()
                    ->value($type);
            }
            if (is_array($dbData) && !empty($dbData)) {
                Cache::put($cacheKey, $dbData, now()->addMinutes(10));

                // 2. Picu background sync ke API SIPP jika ada pembaruan
                if (function_exists('defer')) {
                    defer(fn () => $this->syncPortfolioFromApi($type, $nip, $semester, $cacheKey));
                }

                return $dbData;
            }
        } catch (\Throwable $e) {}

        // 3. Fallback sinkron jika database lokal belum memiliki data
        return $this->syncPortfolioFromApi($type, $nip, $semester, $cacheKey);
    }

    /**
     * Helper background sync portofolio SIPP ke database lokal
     */
    public function syncPortfolioFromApi(string $type, string $nip, string $semester, string $cacheKey): array
    {
        try {
            $params = ['nip' => $nip, 'page' => 1, 'per_page' => 25];
            $response = match ($type) {
                'publikasi'  => $this->sippService->getPublikasi($params),
                'penelitian' => $this->sippService->getPenelitian($params),
                'pengabdian' => $this->sippService->getPengabdian($params),
                default      => null,
            };

            if ($response && $response->success && !empty($response->data)) {
                $raw = is_array($response->data) ? $response->data : [];
                $items = isset($raw['data']) && is_array($raw['data']) ? $raw['data'] : $raw;
                if (!empty($items)) {
                    try {
                        \App\Models\DosenSipp::updateOrCreate(
                            ['nip' => trim($nip), 'semester' => $semester],
                            [$type => $items]
                        );
                    } catch (\Throwable $e) {}

                    Cache::put($cacheKey, $items, now()->addHours(6));
                    return $items;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal sync {$type} dosen {$nip} dari API: " . $e->getMessage());
        }
        return [];
    }

    private function getPublikasiData(string $nip, string $semester): array
    {
        return $this->getPortfolioData('publikasi', $nip, $semester);
    }

    private function getPenelitianData(string $nip, string $semester): array
    {
        return $this->getPortfolioData('penelitian', $nip, $semester);
    }

    private function getPengabdianData(string $nip, string $semester): array
    {
        return $this->getPortfolioData('pengabdian', $nip, $semester);
    }

    private function buildProfile(array $dosenData): array
    {
        $namaLengkap = $dosenData['nama_pegawai_lengkap'] ?? null;
        if (empty($namaLengkap)) {
            $nama = $dosenData['namaPegawai'] ?? $dosenData['nama_pegawai'] ?? $dosenData['nama'] ?? '-';
            $gelarDepan = $dosenData['gelarDepan'] ?? $dosenData['gelar_depan'] ?? '';
            $gelarBelakang = $dosenData['gelarBelakang'] ?? $dosenData['gelar_belakang'] ?? '';
            $namaLengkap = trim($gelarDepan . ' ' . $nama . ' ' . $gelarBelakang);
        }

        return [
            'nip' => $dosenData['nip'] ?? '-',
            'nidn' => $dosenData['nidn'] ?? ($dosenData['payload']['nidn'] ?? null),
            'nik' => $dosenData['nik'] ?? null,
            'kd_pegawai' => $dosenData['kd_pegawai'] ?? null,
            'nama' => $namaLengkap ?: '-',
            'jabatan' => $dosenData['nama_jabatan'] ?? $dosenData['jabatan'] ?? 'Tenaga Pendidik',
            'unit_kerja' => $dosenData['unitKerja'] ?? $dosenData['unit_kerja'] ?? 'Universitas Sultan Ageng Tirtayasa',
            'pangkat' => $dosenData['pangkat'] ?? $dosenData['nama_pangkat'] ?? '-',
            'email' => $dosenData['emailPegawai'] ?? $dosenData['email'] ?? '-',
            'noTlp' => $dosenData['noTlp'] ?? $dosenData['no_tlp'] ?? '-',
            'statusKerja' => $dosenData['nama_stspegawai'] ?? $dosenData['statusKerja'] ?? 'Aktif',
            'statusPegawai' => $dosenData['nama_stspeg'] ?? $dosenData['statusPegawai'] ?? 'PNS',
            'levelPegawai' => $dosenData['nama_level_pegawai'] ?? $dosenData['levelPegawai'] ?? 'Dosen',
        ];
    }

    private function buildJadwalHariIni(array $mkList): array
    {
        if (!is_array($mkList) || empty($mkList)) {
            return [];
        }

        $hariIniName = strtolower(now()->locale('id')->isoFormat('dddd'));
        $hariIniNum  = now()->dayOfWeekIso; // 1=Mon .. 7=Sun

        $jadwalHari = [];
        foreach ($mkList as $mk) {
            $namaMK = $mk['mata_kuliah']['nama'] ?? ($mk['nama_mata_kuliah'] ?? '-');
            $kodeMK = $mk['mata_kuliah']['kode'] ?? ($mk['kode_mata_kuliah'] ?? '-');
            $sks    = $mk['mata_kuliah']['sks'] ?? ($mk['sks'] ?? 0);

            foreach ($mk['jadwal'] ?? [] as $jadwal) {
                // Format kelas
                $kelasList = is_array($jadwal['kelas'] ?? null) 
                    ? collect($jadwal['kelas'])->pluck('nama_kelas')->filter()->implode(', ')
                    : ($jadwal['nama_kelas'] ?? $jadwal['kelas'] ?? '-');

                foreach ($jadwal['waktu_kuliah'] ?? [] as $waktu) {
                    $hariName = strtolower($waktu['hari'] ?? '');
                    $hariNum  = (int) ($waktu['hari_numeric'] ?? 0);

                    if ($hariName === $hariIniName || ($hariNum > 0 && $hariNum === $hariIniNum)) {
                        $ruang = $waktu['ruang']['nama_ruang'] ?? ($waktu['nama_ruang'] ?? $waktu['ruang'] ?? '-');

                        $jadwalHari[] = [
                            'nama_mk' => $namaMK,
                            'kode_mk' => $kodeMK,
                            'sks'     => $sks,
                            'kelas'   => $kelasList ?: '-',
                            'jam'     => ($waktu['jam_mulai'] ?? '-') . ' - ' . ($waktu['jam_selesai'] ?? '-'),
                            'ruang'   => $ruang,
                            'mode'    => $jadwal['mode'] ?? 'OFFLINE',
                            'status'  => $jadwal['status'] ?? 'Belum Terlaksana',
                        ];
                    }
                }
            }
        }
        return $jadwalHari;
    }

    private function buildStatistikMengajar(array $mkList): array
    {
        $totalSKS = 0;
        $totalMK  = 0;
        $totalKelas = 0;

        foreach ($mkList as $mk) {
            if (!is_array($mk)) continue;
            $sks = (int) ($mk['mata_kuliah']['sks'] ?? $mk['sks'] ?? 0);
            $totalSKS += $sks;
            $totalMK++;
            $totalKelas += count($mk['jadwal'] ?? [1]);
        }

        return [
            'total_sks'   => $totalSKS,
            'total_mk'    => $totalMK,
            'total_kelas' => $totalKelas,
        ];
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

    private function extractDoiAndTautan(array $item): array
    {
        $detail = $item['detail'] ?? [];
        $rawDoi = trim((string)($detail['doi'] ?? $item['doi'] ?? ''));
        $rawTautan = trim((string)($detail['tautan'] ?? $detail['url'] ?? $item['tautan'] ?? $item['url'] ?? ''));

        $doiUrl = null;
        if (!empty($rawDoi)) {
            if (str_starts_with($rawDoi, 'http://') || str_starts_with($rawDoi, 'https://')) {
                $doiUrl = $rawDoi;
            } else {
                $cleaned = trim(preg_replace('/^(doi:?\s*)/i', '', $rawDoi));
                if (preg_match('/^10\.\d{4,9}\/[-._;()\/:A-Z0-9]+/i', $cleaned)) {
                    $doiUrl = 'https://doi.org/' . $cleaned;
                }
            }
        }

        $tautanUrl = null;
        if (!empty($rawTautan) && (str_starts_with($rawTautan, 'http://') || str_starts_with($rawTautan, 'https://'))) {
            $tautanUrl = $rawTautan;
        }

        return [
            'doi'     => !empty($rawDoi) ? $rawDoi : null,
            'doi_url' => $doiUrl ?: ($tautanUrl && str_contains($tautanUrl, 'doi.org') ? $tautanUrl : null),
            'tautan'  => $tautanUrl,
        ];
    }

    private function buildPublikasi(array $publikasiData): array
    {
        return $this->buildPublikasiTerakhir($publikasiData);
    }

    private function buildPublikasiTerakhir(array $publikasiData): array
    {
        $items = $publikasiData['data'] ?? $publikasiData;
        if (!is_array($items)) return [];

        $result = [];
        foreach ($items as $item) {
            $penulisList = collect($item['anggota'] ?? [])->pluck('nama')->filter()->implode(', ');
            $detail = $item['detail'] ?? [];
            $links  = $this->extractDoiAndTautan($item);

            $result[] = [
                'judul'             => $item['judul_portofolio'] ?? $item['judul'] ?? $item['title'] ?? '-',
                'penulis'           => !empty($penulisList) ? $penulisList : ($item['penulis'] ?? $item['authors'] ?? '-'),
                'journal'           => $detail['nama_jurnal'] ?? $detail['penerbit'] ?? $item['nama_jurnal'] ?? $item['journal'] ?? $item['sumber'] ?? '-',
                'penerbit'          => $detail['penerbit'] ?? $item['penerbit'] ?? '-',
                'tahun'             => $this->extractYear($item),
                'tipe'              => $detail['jenis_publikasi'] ?? $item['jenis_portofolio'] ?? $item['tipe'] ?? 'Publikasi Ilmiah',
                'status_verifikasi' => $item['status_verifikasi_label'] ?? $item['status_verifikasi'] ?? 'Terverifikasi',
                'doi'               => $links['doi'],
                'doi_url'           => $links['doi_url'],
                'tautan'            => $links['tautan'],
            ];
        }
        return $result;
    }

    private function buildPenelitian(array $penelitianData): array
    {
        $items = $penelitianData['data'] ?? $penelitianData;
        if (!is_array($items)) return [];

        $result = [];
        foreach ($items as $item) {
            $detail = $item['detail'] ?? [];
            $links  = $this->extractDoiAndTautan($item);

            $result[] = [
                'judul'             => $item['judul_portofolio'] ?? $item['judul'] ?? $item['title'] ?? '-',
                'tahun'             => $this->extractYear($item),
                'tipe'              => $detail['jenis_publikasi'] ?? $item['jenis_portofolio'] ?? $item['detail']['kategori_kegiatan'] ?? $item['tipe'] ?? $item['jenis'] ?? '-',
                'status_verifikasi' => $item['status_verifikasi_label'] ?? $item['status_verifikasi'] ?? '-',
                'lokasi'            => $detail['nama_jurnal'] ?? $detail['penerbit'] ?? $detail['lokasi'] ?? '-',
                'doi'               => $links['doi'],
                'doi_url'           => $links['doi_url'],
                'tautan'            => $links['tautan'],
            ];
        }
        return $result;
    }

    private function buildPengabdian(array $pengabdianData): array
    {
        $items = $pengabdianData['data'] ?? $pengabdianData;
        if (!is_array($items)) return [];

        $result = [];
        foreach ($items as $item) {
            $detail = $item['detail'] ?? [];
            $links  = $this->extractDoiAndTautan($item);

            $result[] = [
                'judul'             => $item['judul_portofolio'] ?? $item['judul'] ?? $item['title'] ?? '-',
                'tahun'             => $this->extractYear($item),
                'tipe'              => $detail['jenis_publikasi'] ?? $item['jenis_portofolio'] ?? $item['detail']['kategori_kegiatan'] ?? $item['tipe'] ?? $item['jenis'] ?? '-',
                'status_verifikasi' => $item['status_verifikasi_label'] ?? $item['status_verifikasi'] ?? '-',
                'lokasi'            => $detail['nama_jurnal'] ?? $detail['penerbit'] ?? $detail['lokasi'] ?? '-',
                'doi'               => $links['doi'],
                'doi_url'           => $links['doi_url'],
                'tautan'            => $links['tautan'],
            ];
        }
        return $result;
    }

    private function buildSintaIndexasi(array $publikasiData): array
    {
        return [
            'scopus' => ['dokumen' => '-', 'sitasi' => '-', 'h_index' => '-', 'i10_index' => '-', 'g_index' => '-'],
            'google_scholar' => ['dokumen' => '-', 'sitasi' => '-', 'h_index' => '-', 'i10_index' => '-', 'g_index' => '-'],
        ];
    }
}
