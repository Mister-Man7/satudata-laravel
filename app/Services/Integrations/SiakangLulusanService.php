<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SiakangLulusanService extends SiakangApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.lulusan';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.siakang.base_url'),
            'auth_type' => 'bearer_login',
            'token' => config('services.siakang.token'),
            'cf_clearance' => config('services.siakang.cf_clearance'),
            'connect_timeout' => 3,
            'timeout' => 8,
        ];
    }

    /**
     * Ambil data ringkasan mahasiswa lulus.
     *
     * SWR dual-key: data (TTL 6 jam) + freshness flag (TTL 20 menit).
     * DB lokal dipakai saat cache miss; API selalu di-refresh di background.
     */
    public function getData(array $params = []): ApiResponse
    {
        $cacheKey     = 'siakang.lulusan.' . md5(json_encode($params));
        $staleFlagKey = $cacheKey . '.fresh';

        // Cache hit — return data, refresh jika stale
        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);

            // Fresh flag expired — picu refresh background
            if (!Cache::has($staleFlagKey)) {
                $this->deferApiRefresh($cacheKey, $staleFlagKey, $params);
            }

            return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa lulus', data: $data);
        }

        // Cache miss — ambil DB lokal, defer refresh API
        $dbData = $this->hasilFallbackLulusanData($params);
        if (!empty($dbData['detail_per_prodi']) || ($dbData['sources'] ?? null) === 'database') {
            Cache::put($cacheKey, $dbData, now()->addHours(6));

            // Tahan penarikan API 20 menit; lihat SiakangMahasiswaAktifService.
            Cache::put($staleFlagKey, true, now()->addMinutes(20));
            return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa lulus', data: $dbData);
        }

        // Cold start — DB juga kosong. API tidak pernah ditunggu di dalam request:
        // penarikan dijadwalkan setelah response dikirim. Sebelumnya panggilan ini
        // menunggu hingga timeout 15 detik, dan karena satu halaman memanggilnya
        // untuk beberapa semester, totalnya menembus batas 30 detik PHP.
        $this->deferApiRefresh($cacheKey, $staleFlagKey, $params);

        return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa lulus', data: $dbData);
    }

    private function deferApiRefresh(string $cacheKey, string $staleFlagKey, array $params): void
    {
        if (!function_exists('defer')) {
            return;
        }

        defer(function () use ($staleFlagKey, $params) {
            // PHP tidak dapat menembus Cloudflare, jadi penarikan dialihkan ke
            // penarik Chromium lokal (lihat SourceRevalidator). Semester wajib
            // dikirim karena skrip penarik menarik data per semester.
            app(\App\Services\Integrations\SourceRevalidator::class)->trigger('siakang.lulusan', [
                'semester' => (string) ($params['semester'] ?? ''),
            ]);

            // Reset fresh flag terlepas dari hasil penarikan, berlaku sesuai config
            Cache::put($staleFlagKey, true, now()->addMinutes((int) config('satudata.swr.fresh_minutes', 20)));
        });
    }

    /**
     * Rentang tanggal satu semester (Gasal: 1 Agustus - 31 Januari, Genap:
     * 1 Februari - 31 Juli), dipakai untuk menyaring tanggal lulus pada payload.
     *
     * @return array{0: string, 1: string}
     */
    private function rentangTanggalSemester(string $kodeSemester): array
    {
        $tahun = (int) substr($kodeSemester, 0, 4);
        $jenis = substr($kodeSemester, -1);
        $tahunBerikut = $tahun + 1;

        if ($tahun === 0) {
            return ['1970-01-01', '1970-01-01'];
        }

        return $jenis === '1'
            ? ["{$tahun}-08-01", "{$tahunBerikut}-01-31"]
            : ["{$tahunBerikut}-02-01", "{$tahunBerikut}-07-31"];
    }

    private function hasilFallbackLulusanData(array $params = []): array
    {
        $parameter = $params;

        $semester = (string)($parameter['semester'] ?? '');

        // Utamakan angka hasil tarikan API yang tersimpan di kolom
        // (tabel siakang_semester_stats diisi scripts/sync-siakang-stat.php).
        $dariApi = \Illuminate\Support\Facades\DB::table('siakang_semester_stats')
            ->where('semester', $semester)
            ->where('jenis', 'lulus')
            ->get();

        if (!$dariApi->isEmpty()) {
            $detailProdi = $dariApi->map(fn ($baris) => [
                'prodi_id' => (string) $baris->prodi_id,
                'kode_prodi' => (string) $baris->kode_prodi,
                'nama_prodi' => (string) $baris->nama_prodi,
                'jenjang' => (string) $baris->jenjang,
                'fakultas' => $baris->fakultas,
                'jumlah_mahasiswa_lulus' => (int) $baris->jumlah,
            ])->all();

            $totalLulusApi = (int) $dariApi->sum('jumlah');

            return [
                'total_mahasiswa_lulus' => $totalLulusApi,
                'total' => $totalLulusApi,
                'detail_per_fakultas' => $dariApi->groupBy('fakultas')->map(fn ($items, $namaFakultas) => [
                    'nama_fakultas' => $namaFakultas !== '' ? $namaFakultas : '-',
                    'jumlah_mahasiswa_lulus' => (int) $items->sum('jumlah'),
                ])->values()->all(),
                'detail_per_prodi' => $detailProdi,
                'sources' => 'api',
            ];
        }

        [$awalSemester, $akhirSemester] = $this->rentangTanggalSemester($semester);

        try {
            $prodiList = \Illuminate\Support\Facades\DB::table('prodis')->get();
            $prodiMap = [];
            foreach ($prodiList as $p) {
                $prodiMap[$p->id] = [
                    'nama_prodi' => $p->nama_prodi,
                    'jenjang'    => strtoupper($p->jenjang ?? 'S1'),
                ];
            }

            // Hitung lulusan per prodi dari kolom tanggal berindeks `lulus_pada`
            // (diisi dari payload saat sinkronisasi). Baris tanpa tanggal lulus
            // dianggap belum diketahui, jadi tidak dihitung.
            $counts = \Illuminate\Support\Facades\DB::table('mahasiswas')
                ->select('prodi_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->whereBetween('lulus_pada', [$awalSemester, $akhirSemester])
                ->groupBy('prodi_id')
                ->get();

            $fakultasCounts = [];
            $detailProdi    = [];

            foreach ($counts as $row) {
                $pInfo = $prodiMap[$row->prodi_id] ?? [
                    'nama_prodi' => 'Program Studi Lainnya',
                    'jenjang'    => 'S1',
                ];

                $pName = $pInfo['nama_prodi'];
                $namaFak = $this->namaFakultasDariProdi($pName);
                $jumlah = (int) $row->total;

                if ($jumlah <= 0) continue;

                $fakultasCounts[$namaFak] = ($fakultasCounts[$namaFak] ?? 0) + $jumlah;

                $detailProdi[] = [
                    'prodi_id' => $row->prodi_id,
                    'nama_prodi' => $pName,
                    'jenjang' => $pInfo['jenjang'],
                    'fakultas' => $namaFak,
                    'jumlah_mahasiswa_lulus' => $jumlah,
                ];
            }

            $detailFakultas = [];
            foreach ($fakultasCounts as $namaFak => $count) {
                $detailFakultas[] = [
                    'nama_fakultas' => $namaFak,
                    'jumlah_mahasiswa_lulus' => $count,
                ];
            }

            $totalLulus = array_sum(array_column($detailFakultas, 'jumlah_mahasiswa_lulus'));

            return [
                'total_mahasiswa_lulus' => $totalLulus,
                'total' => $totalLulus,
                'detail_per_fakultas' => $detailFakultas,
                'detail_per_prodi' => array_values($detailProdi),
                // Penanda bahwa angkanya sudah dijawab database (boleh nol). Tanpa ini
                // pemanggil menganggapnya "belum ada data" dan menjadwalkan penarikan
                // API yang tidak perlu.
                'sources' => 'database',
            ];
        } catch (\Throwable $e) {
            return [
                'total_mahasiswa_lulus' => 0,
                'total' => 0,
                'detail_per_fakultas' => [],
                'detail_per_prodi' => [],
            ];
        }
    }

    private function namaFakultasDariProdi(string $pName): string
    {
        if (stripos($pName, 'Pendidikan') !== false || stripos($pName, 'FKIP') !== false) {
            return 'Fakultas Keguruan dan Ilmu Pendidikan';
        }
        if (stripos($pName, 'Teknik') !== false || stripos($pName, 'Informatika') !== false) {
            return 'Fakultas Teknik';
        }
        if (stripos($pName, 'Ekonomi') !== false || stripos($pName, 'Manajemen') !== false || stripos($pName, 'Akuntansi') !== false) {
            return 'Fakultas Ekonomi dan Bisnis';
        }
        if (stripos($pName, 'Hukum') !== false) {
            return 'Fakultas Hukum';
        }
        if (stripos($pName, 'Pertanian') !== false || stripos($pName, 'Pangan') !== false || stripos($pName, 'Perikanan') !== false) {
            return 'Fakultas Pertanian';
        }
        if (stripos($pName, 'Sosial') !== false || stripos($pName, 'Komunikasi') !== false || stripos($pName, 'Administrasi') !== false) {
            return 'Fakultas Ilmu Sosial dan Ilmu Politik';
        }
        if (stripos($pName, 'Kedokteran') !== false || stripos($pName, 'Keperawatan') !== false || stripos($pName, 'Gizi') !== false) {
            return 'Fakultas Kedokteran dan Ilmu Kesehatan';
        }
        return 'Pascasarjana';
    }


    /**
     * Ambil daftar mahasiswa lulus (paginated, cached).
     */
    public function getListMahasiswa(array $params = []): ApiResponse
    {
        $cacheKey = 'siakang.lulusan.list.' . md5(json_encode($params));

        if (Cache::has($cacheKey)) {
            return new ApiResponse(
                success: true,
                status: 200,
                message: 'Data dari cache',
                data: Cache::get($cacheKey),
            );
        }

        $response = $this->get('/v2/mahasiswa/lulusan', $params);

        if ($response->success && !empty($response->data)) {
            Cache::put($cacheKey, $response->data, now()->addMinutes(10));
            return $response;
        }

        $fallbackList = $this->hasilFallbackListLulusan($params);
        return new ApiResponse(
            success: true,
            status: 200,
            message: 'Data dimuat dari fallback lokal',
            data: $fallbackList,
        );
    }

    private function hasilFallbackListLulusan(array $params = []): array
    {
        $search = strtolower(trim((string)($params['search'] ?? '')));
        $kodeProdi = strtolower(trim((string)($params['kode_prodi'] ?? '')));
        $angkatan = (string)($params['angkatan'] ?? '');
        $tahunLulus = (string)($params['tahun_lulus'] ?? '');
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 25);

        try {
            if (class_exists(\App\Models\Mahasiswa::class) && \App\Models\Mahasiswa::count() > 0) {
                $query = \App\Models\Mahasiswa::with('prodi');

                // Daftar ini daftar lulusan, jadi baris tanpa `lulus_pada` tidak ikut
                // (dulu semua mahasiswa terdaftar di sini).
                $query->whereNotNull('lulus_pada');

                if ($search !== '') {
                    $query->where(function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%")
                          ->orWhere('nim', 'like', "%{$search}%");
                    });
                }

                if ($kodeProdi !== '') {
                    $query->whereHas('prodi', function ($q) use ($kodeProdi) {
                        $q->where('kode_prodi', 'like', "%{$kodeProdi}%")
                          ->orWhere('nama_prodi', 'like', "%{$kodeProdi}%");
                    });
                }

                if ($angkatan !== '') {
                    $query->where('angkatan', $angkatan);
                }

                if ($tahunLulus !== '') {
                    // Filter tahun lulus memakai kolom `lulus_pada` (hasil sync). Dulu
                    // di-approximate dari angkatan (tahun - 4 / - 3), sehingga baris yang
                    // tampil bisa bukan lulusan tahun yang diminta.
                    $query->whereBetween('lulus_pada', [$tahunLulus . '-01-01', $tahunLulus . '-12-31']);
                }

                $total = $query->count();
                $items = $query->skip(($page - 1) * $limit)->take($limit)->get();

                $mappedItems = $items->map(function ($mhs) {
                    return [
                        'nim' => $mhs->nim,
                        'nama' => $mhs->nama,
                        'prodi' => [
                            // Nama/kode prodi dari relasi `prodis`; tanpa tebakan.
                            'nama_prodi' => $mhs->prodi->nama_prodi ?? '-',
                            'kode_prodi' => $mhs->prodi->kode_prodi ?? '-',
                        ],
                        'angkatan' => (int) ($mhs->angkatan ?? 0),
                        // Tanggal lulus dari kolom `lulus_pada`; bila belum ada tampil
                        // kosong, tidak dihitung dari angkatan + masa studi.
                        'tanggal_lulus' => $mhs->lulus_pada,
                    ];
                })->toArray();


                return [
                    0 => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'data' => $mappedItems,
                    ]
                ];
            }
        } catch (\Throwable $e) {
            // Log or ignore DB query exception
        }

        return [
            0 => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => 0,
                'data' => [],
            ]
        ];
    }
}