<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimantapService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'simantap';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.simantap.base_url'),
            'auth_type' => 'bearer_login',
            'connect_timeout' => 10,
            'timeout' => 30,
        ];
    }

    /**
     * Ambil bearer token via login, di-cache selama 60 menit.
     *
     * @throws \RuntimeException jika login gagal dan token fallback tidak ada
     */
    protected function getBearerToken(array $config): string
    {
        return Cache::remember('simantap_api_token', now()->addMinutes(60), function () use ($config) {
            $email = config('services.simantap.email');
            $password = config('services.simantap.password');
            $fallbackToken = config('services.simantap.token');

            if (empty($config['base_url']) || empty($email) || empty($password)) {
                if (!empty($fallbackToken)) {
                    return $fallbackToken;
                }
                Log::error('Simantap: Konfigurasi belum lengkap');
                throw new \RuntimeException('Konfigurasi Simantap belum lengkap.');
            }

            $baseUrl = rtrim($config['base_url'], '/');
            $loginUrl = str_ends_with($baseUrl, '/api') ? $baseUrl . '/auth/login' : $baseUrl . '/api/auth/login';

            try {
                $response = Http::timeout(10)->post($loginUrl, [
                    'email' => $email,
                    'password' => $password,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $token = $data['data']['token'] ?? $data['token'] ?? null;
                    if ($token) {
                        return $token;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Simantap: Gagal request login, menggunakan token fallback: ' . $e->getMessage());
            }

            if (!empty($fallbackToken)) {
                return $fallbackToken;
            }

            throw new \RuntimeException('Login ke Simantap gagal.');
        });
    }

    /**
     * Normalisasi endpoint agar tidak terjadi duplikasi '/api/api/...'.
     */
    protected function normalizeEndpoint(string $endpoint): string
    {
        $ep = ltrim($endpoint, '/');
        $baseUrl = rtrim(config('services.simantap.base_url', ''), '/');
        if (str_ends_with($baseUrl, '/api') && str_starts_with($ep, 'api/')) {
            $ep = substr($ep, 4);
        }
        return '/' . ltrim($ep, '/');
    }

    /**
     * Override request untuk normalisasi endpoint & handle token expired (401) → refresh & retry sekali.
     */
    protected function request(string $method, string $endpoint, array $payload = []): ApiResponse
    {
        $endpoint = $this->normalizeEndpoint($endpoint);
        $response = parent::request($method, $endpoint, $payload);

        if ($response->status === 401) {
            Cache::forget('simantap_api_token');
            Log::info('Simantap: Token expired, retry dengan token baru');
            return parent::request($method, $endpoint, $payload);
        }

        return $response;
    }

    /**
     * Kirim request dengan pola SWR (Stale-While-Revalidate):
     * 1. Cek Cache utama (simantap.data.*)
     *    - Jika fresh (< 20 menit) -> return <1ms instan
     *    - Jika stale (> 20 menit) -> return <1ms instan + background defer hit API Simantap
     * 2. Jika Cache miss -> Ambil dari Database lokal (App\Models\Aset 97k data)
     *    - Return data DB instan (<50ms)
     *    - Cache data DB sementara
     *    - Background defer hit API Simantap untuk revalidasi data terbaru
     * 3. Jika Cache & Database kosong (cold start) -> hit API Simantap secara synchronous
     */
    public function makeRequest(string $method, string $endpoint, array $params = []): ?array
    {
        $method = strtoupper($method);

        // Jika bukan GET (POST, PUT, DELETE), kirim langsung ke API
        if ($method !== 'GET') {
            try {
                $response = match ($method) {
                    'POST' => $this->post($endpoint, $params),
                    'PUT' => $this->put($endpoint, $params),
                    'DELETE' => $this->delete($endpoint, $params),
                    default => ApiResponse::unexpectedError("Method '{$method}' belum didukung."),
                };
                return $response->success ? ($response->data ?? []) : null;
            } catch (\Throwable $e) {
                Log::warning("Simantap: Gagal request {$method} {$endpoint}: " . $e->getMessage());
                return null;
            }
        }

        $cleanEndpoint = ltrim($this->normalizeEndpoint($endpoint), '/');
        $paramHash = md5(serialize($params));
        $cacheKey = "simantap.data.{$cleanEndpoint}.{$paramHash}";
        $freshFlagKey = "simantap.fresh.{$cleanEndpoint}.{$paramHash}";

        // 1. Cek Cache
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            if (is_array($cachedData)) {
                // Jika masih fresh (< 20 menit), langsung kembalikan (<1ms)
                if (Cache::has($freshFlagKey)) {
                    return $cachedData;
                }

                // Stale hit: Kembalikan data lama instan, refresh API di background
                if (function_exists('defer')) {
                    defer(function () use ($endpoint, $params, $cacheKey, $freshFlagKey) {
                        $this->refreshFromApiBackground($endpoint, $params, $cacheKey, $freshFlagKey);
                    });
                }

                return $cachedData;
            }
        }

        // 2. Cache Miss: Ambil dari Database lokal (hasilFallbackSimantapData dari 97.715 data asets)
        $dbData = $this->hasilFallbackSimantapData($endpoint, $params);
        $hasDbData = !empty($dbData['data']) || !empty($dbData['id_kampus']) || (isset($dbData['total']) && $dbData['total'] > 0);

        if ($hasDbData) {
            // Simpan data DB ke cache agar request berikutnya <1ms
            Cache::put($cacheKey, $dbData, now()->addHours(6));

            // Picu pembaruan dari API di background dengan defer
            if (function_exists('defer')) {
                defer(function () use ($endpoint, $params, $cacheKey, $freshFlagKey) {
                    $this->refreshFromApiBackground($endpoint, $params, $cacheKey, $freshFlagKey);
                });
            }

            return $dbData;
        }

        // 3. Cold start (Cache & DB tidak ada data): Hit API secara langsung
        try {
            $response = $this->get($endpoint, $params);
            if ($response->success && !empty($response->data)) {
                $apiData = $response->data;
                Cache::put($cacheKey, $apiData, now()->addHours(6));
                Cache::put($freshFlagKey, true, now()->addMinutes(20));
                return $apiData;
            }
        } catch (\Throwable $e) {
            Log::warning("Simantap: Gagal fetch synchronous dari API {$endpoint}: " . $e->getMessage());
        }

        return $dbData;
    }

    /**
     * Refresh data dari API Simantap di background via defer().
     * Jika API mengembalikan data sukses, perbarui cache & pasang fresh flag.
     */
    protected function refreshFromApiBackground(string $endpoint, array $params, string $cacheKey, string $freshFlagKey): void
    {
        try {
            $response = $this->get($endpoint, $params);
            if ($response->success && !empty($response->data)) {
                $apiData = $response->data;
                Cache::put($cacheKey, $apiData, now()->addHours(6));
                Cache::put($freshFlagKey, true, now()->addMinutes(20));
                Log::info("Simantap SWR: Berhasil background refresh dari API untuk {$endpoint}");
            }
        } catch (\Throwable $e) {
            Log::warning("Simantap SWR: Gagal background refresh untuk {$endpoint}: " . $e->getMessage());
        }
    }

    /**
     * Ambil daftar lokasi unik teragregasi langsung dari MySQL (hanya ~350 baris dari total 97.715 data).
     * Mencegah fatal error memory exhaustion (128MB limit) pada PHP.
     */
    protected function getDistinctLocations(): array
    {
        return Cache::remember('simantap_distinct_locations_v3', now()->addHours(6), function () {
            return \Illuminate\Support\Facades\DB::table('asets')
                ->select('lokasi_lengkap', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                ->whereNotNull('lokasi_lengkap')
                ->where('lokasi_lengkap', '!=', '-')
                ->groupBy('lokasi_lengkap')
                ->get()
                ->map(fn($item) => [
                    'lokasi_lengkap' => (string)($item->lokasi_lengkap ?? ''),
                    'total' => (int)($item->total ?? 0),
                ])
                ->all();
        });
    }

    /**
     * Data fallback dinamis dari 97.715 data BMN riil di database (`App\Models\Aset`).
     * Dikelompokkan per Kampus, Gedung, dan Ruangan secara otomatis.
     */
    private function hasilFallbackSimantapData(string $endpoint, array $params = []): array
    {
        $ep = trim($endpoint, '/');

        // 1. Kampus list (kampus, kampus/by-satker) - Agregasi dinamis per Kampus dari 97.715 data Aset
        if ($ep === 'kampus' || str_starts_with($ep, 'kampus/by-satker')) {
            try {
                return Cache::remember('simantap_kampus_summary_v3', now()->addHours(6), function () {
                    $kampusMap = [
                        'KAMPUS-SINDANGSARI' => ['nama' => 'Kampus Sindangsari', 'keyword' => 'Sindangsari', 'count' => 0],
                        'KAMPUS-PAKUPATAN' => ['nama' => 'Kampus Pakupatan', 'keyword' => 'Pakupatan', 'count' => 0],
                        'KAMPUS-KEPANDEAN' => ['nama' => 'Kampus Kepandean', 'keyword' => 'Kepandean', 'count' => 0],
                        'KAMPUS-CILEGON' => ['nama' => 'Kampus Cilegon', 'keyword' => 'Cilegon', 'count' => 0],
                        'KAMPUS-CIWARU' => ['nama' => 'Kampus Ciwaru', 'keyword' => 'Ciwaru', 'count' => 0],
                    ];

                    $totalAll = \Illuminate\Support\Facades\DB::table('asets')->count();
                    $sumOther = 0;

                    $distinct = $this->getDistinctLocations();
                    foreach ($distinct as $row) {
                        $lok = is_array($row) ? ($row['lokasi_lengkap'] ?? '') : ($row->lokasi_lengkap ?? '');
                        $cnt = (int)(is_array($row) ? ($row['total'] ?? 0) : ($row->total ?? 0));
                        foreach ($kampusMap as $kId => &$kData) {
                            if ($kId === 'KAMPUS-SINDANGSARI') continue;
                            if (stripos($lok, $kData['keyword']) !== false) {
                                $kData['count'] += $cnt;
                                $sumOther += $cnt;
                                break;
                            }
                        }
                        unset($kData);
                    }

                    $kampusMap['KAMPUS-SINDANGSARI']['count'] = max(0, $totalAll - $sumOther);

                    $kampusList = [];
                    foreach ($kampusMap as $kId => $kData) {
                        $kampusList[] = [
                            'id_kampus' => $kId,
                            'nama_kampus' => $kData['nama'],
                            'total_aset' => $kData['count'],
                            'updated_at' => now()->toDateTimeString(),
                        ];
                    }

                    return [
                        'data' => [
                            'data' => $kampusList,
                            'total' => count($kampusList),
                        ]
                    ];
                });
            } catch (\Throwable $e) {
                // Fallthrough
            }
        }

        // 2. Detail Kampus (kampus/{id}) -> List Gedung dinamis pada Kampus tersebut
        if (str_starts_with($ep, 'kampus/')) {
            $parts = explode('/', $ep);
            $kampusId = strtoupper($parts[1] ?? 'KAMPUS-SINDANGSARI');

            $kw = 'Sindangsari';
            $namaK = 'Kampus Sindangsari';
            if (str_contains($kampusId, 'PAKUPATAN')) { $kw = 'Pakupatan'; $namaK = 'Kampus Pakupatan'; }
            elseif (str_contains($kampusId, 'KEPANDEAN')) { $kw = 'Kepandean'; $namaK = 'Kampus Kepandean'; }
            elseif (str_contains($kampusId, 'CILEGON')) { $kw = 'Cilegon'; $namaK = 'Kampus Cilegon'; }
            elseif (str_contains($kampusId, 'CIWARU')) { $kw = 'Ciwaru'; $namaK = 'Kampus Ciwaru'; }

            try {
                $distinct = $this->getDistinctLocations();
                $gedungCounts = [];

                foreach ($distinct as $r) {
                    $lok = is_array($r) ? ($r['lokasi_lengkap'] ?? '') : ($r->lokasi_lengkap ?? '');
                    $total = (int)(is_array($r) ? ($r['total'] ?? 0) : ($r->total ?? 0));

                    $matched = false;
                    if (stripos($lok, $kw) !== false) {
                        $matched = true;
                    } elseif ($kampusId === 'KAMPUS-SINDANGSARI') {
                        $isOther = false;
                        foreach (['Pakupatan', 'Kepandean', 'Cilegon', 'Ciwaru'] as $otherKw) {
                            if (stripos($lok, $otherKw) !== false) {
                                $isOther = true;
                                break;
                            }
                        }
                        if (!$isOther) {
                            $matched = true;
                        }
                    }

                    if ($matched) {
                        $lParts = array_map('trim', explode('-', $lok));
                        $gName = $lParts[1] ?? 'Gedung Utama';
                        if (empty($gName) || $gName === '-') {
                            $gName = 'Gedung Rektorat / Utama';
                        }
                        $gedungCounts[$gName] = ($gedungCounts[$gName] ?? 0) + $total;
                    }
                }

                $gedungList = [];
                foreach ($gedungCounts as $gName => $count) {
                    $gSlug = 'GEDUNG-' . strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', $gName));
                    $gedungList[] = [
                        'id_gedung' => $gSlug,
                        'nama_gedung' => $gName,
                        'id_kampus' => $kampusId,
                        'total_aset' => $count,
                        'updated_at' => now()->toDateTimeString(),
                    ];
                }

                return [
                    'data' => [
                        'id_kampus' => $kampusId,
                        'nama_kampus' => $namaK,
                        'gedung' => $gedungList,
                    ]
                ];
            } catch (\Throwable $e) {
                // Fallthrough
            }
        }

        // 3. Gedung / Gedung by kampus
        if ($ep === 'gedung' || str_starts_with($ep, 'gedung/by-kampus')) {
            try {
                $parts = explode('/', $ep);
                $kampusId = strtoupper(end($parts));

                $kw = 'Sindangsari';
                if (str_contains($kampusId, 'PAKUPATAN')) $kw = 'Pakupatan';
                elseif (str_contains($kampusId, 'KEPANDEAN')) $kw = 'Kepandean';
                elseif (str_contains($kampusId, 'CILEGON')) $kw = 'Cilegon';
                elseif (str_contains($kampusId, 'CIWARU')) $kw = 'Ciwaru';

                $distinct = $this->getDistinctLocations();
                $gedungCounts = [];

                foreach ($distinct as $r) {
                    $lok = is_array($r) ? ($r['lokasi_lengkap'] ?? '') : ($r->lokasi_lengkap ?? '');
                    $total = (int)(is_array($r) ? ($r['total'] ?? 0) : ($r->total ?? 0));

                    $matched = false;
                    if (str_contains($kampusId, 'KAMPUS-')) {
                        if (stripos($lok, $kw) !== false) {
                            $matched = true;
                        } elseif (str_contains($kampusId, 'SINDANGSARI')) {
                            $isOther = false;
                            foreach (['Pakupatan', 'Kepandean', 'Cilegon', 'Ciwaru'] as $otherKw) {
                                if (stripos($lok, $otherKw) !== false) {
                                    $isOther = true;
                                    break;
                                }
                            }
                            if (!$isOther) {
                                $matched = true;
                            }
                        }
                    } else {
                        $matched = true;
                    }

                    if ($matched) {
                        $lParts = array_map('trim', explode('-', $lok));
                        $gName = $lParts[1] ?? 'Gedung Utama';
                        if (empty($gName) || $gName === '-') {
                            $gName = 'Gedung Rektorat / Utama';
                        }
                        $gedungCounts[$gName] = ($gedungCounts[$gName] ?? 0) + $total;
                    }
                }

                $gedungList = [];
                foreach ($gedungCounts as $gName => $count) {
                    $gSlug = 'GEDUNG-' . strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', $gName));
                    $gedungList[] = [
                        'id_gedung' => $gSlug,
                        'nama_gedung' => $gName,
                        'id_kampus' => $kampusId,
                        'total_aset' => $count,
                        'updated_at' => now()->toDateTimeString(),
                    ];
                }

                return [
                    'data' => [
                        'data' => $gedungList,
                        'total' => count($gedungList),
                    ]
                ];
            } catch (\Throwable $e) {
                // Fallthrough
            }
        }

        // 4. Ruangan / Ruangan by gedung / Ruangan by lantai
        if ($ep === 'ruangan' || str_starts_with($ep, 'ruangan/by-gedung') || str_starts_with($ep, 'ruangan/by-lantai')) {
            try {
                $parts = explode('/', $ep);
                $gedungSlug = end($parts);
                $targetGedung = trim(str_replace(['GEDUNG-', '-'], [' ', ' '], $gedungSlug));

                $distinct = $this->getDistinctLocations();
                $ruanganCounts = [];

                foreach ($distinct as $r) {
                    $lok = is_array($r) ? ($r['lokasi_lengkap'] ?? '') : ($r->lokasi_lengkap ?? '');
                    $total = (int)(is_array($r) ? ($r['total'] ?? 0) : ($r->total ?? 0));

                    if (empty($targetGedung) || $targetGedung === 'RUANGAN' || stripos($lok, $targetGedung) !== false) {
                        $lParts = array_map('trim', explode('-', $lok));
                        $rName = end($lParts);
                        if (empty($rName) || $rName === '-') {
                            $rName = 'Ruang Operasional';
                        }
                        $ruanganCounts[$rName] = ($ruanganCounts[$rName] ?? 0) + $total;
                    }
                }

                $ruanganList = [];
                foreach (array_slice($ruanganCounts, 0, 50) as $rName => $count) {
                    $rSlug = 'RUANG-' . strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', $rName));
                    $ruanganList[] = [
                        'id_ruangan' => $rSlug,
                        'nama_ruangan' => $rName,
                        'total_aset' => $count,
                        'updated_at' => now()->toDateTimeString(),
                    ];
                }

                return [
                    'data' => [
                        'data' => $ruanganList,
                        'total' => count($ruanganList),
                    ]
                ];
            } catch (\Throwable $e) {
                // Fallthrough
            }
        }

        // 5. BMN / BMN-ALL / BMN by ruangan/gedung/kampus/jenis (Data 97.715 Aset riil dari DB)
        if (str_starts_with($ep, 'bmn')) {
            try {
                $page = (int)($params['page'] ?? 1);
                $perPage = (int)($params['per_page'] ?? 100);

                $query = \App\Models\Aset::query();

                // Filter spesifik berdasarkan URL endpoint (ruangan, gedung, kampus, jenis)
                if (str_contains($ep, 'by-ruangan/')) {
                    $parts = explode('/', $ep);
                    $targetId = urldecode(end($parts));
                    $clean = preg_replace('/^(RUANG|GEDUNG|KAMPUS)-/i', '', $targetId);
                    $words = array_values(array_filter(explode('-', $clean), fn($w) => strlen(trim($w)) > 0));
                    $likePattern = !empty($words) ? ('%' . implode('%', $words) . '%') : '';

                    if (!empty($likePattern)) {
                        $query->where(function ($q) use ($targetId, $likePattern) {
                            $q->where('id_ruangan', $targetId)
                              ->orWhere('lokasi_lengkap', 'like', $likePattern);
                        });
                    }
                } elseif (str_contains($ep, 'by-gedung/')) {
                    $parts = explode('/', $ep);
                    $targetId = urldecode(end($parts));
                    $clean = preg_replace('/^(GEDUNG|KAMPUS)-/i', '', $targetId);
                    $words = array_values(array_filter(explode('-', $clean), fn($w) => strlen(trim($w)) > 0));
                    $likePattern = !empty($words) ? ('%' . implode('%', $words) . '%') : '';

                    if (!empty($likePattern)) {
                        $query->where(function ($q) use ($targetId, $likePattern) {
                            $q->where('id_gedung', $targetId)
                              ->orWhere('lokasi_lengkap', 'like', $likePattern);
                        });
                    }
                } elseif (str_contains($ep, 'by-kampus/')) {
                    $parts = explode('/', $ep);
                    $targetId = urldecode(end($parts));
                    $clean = preg_replace('/^(KAMPUS)-/i', '', $targetId);
                    $words = array_values(array_filter(explode('-', $clean), fn($w) => strlen(trim($w)) > 0));
                    $likePattern = !empty($words) ? ('%' . implode('%', $words) . '%') : '';

                    if (!empty($likePattern)) {
                        $query->where(function ($q) use ($targetId, $likePattern) {
                            $q->where('id_kampus', $targetId)
                              ->orWhere('lokasi_lengkap', 'like', $likePattern);
                        });
                    }
                } elseif (str_contains($ep, 'by-jenis/')) {
                    $parts = explode('/', $ep);
                    $targetId = urldecode(end($parts));
                    if (!empty($targetId)) {
                        $query->where('id_jenis_barang', $targetId);
                    }
                }

                // Filter tambahan dari request parameter (search, kondisi)
                if (!empty($params['search'])) {
                    $s = $params['search'];
                    $query->where(function ($q) use ($s) {
                        $q->where('nama_kode_barang', 'like', "%{$s}%")
                          ->orWhere('nama_jenis_barang', 'like', "%{$s}%")
                          ->orWhere('merk', 'like', "%{$s}%")
                          ->orWhere('lokasi_lengkap', 'like', "%{$s}%");
                    });
                }

                if (isset($params['kondisi'])) {
                    $query->where('kondisi', $params['kondisi']);
                }

                $total = $query->count();
                $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

                $mapped = $items->map(function ($item) {
                    $payload = is_array($item->payload) ? $item->payload : (json_decode($item->payload ?? '{}', true) ?: []);
                    return array_merge([
                        'id_bmn' => $item->id_bmn,
                        'id_satker' => $item->id_satker,
                        'id_kampus' => $item->id_kampus,
                        'id_gedung' => $item->id_gedung,
                        'id_ruangan' => $item->id_ruangan,
                        'id_jenis_barang' => $item->id_jenis_barang,
                        'nama_jenis_barang' => $item->nama_jenis_barang,
                        'id_kode_barang' => $item->id_kode_barang,
                        'nama_kode_barang' => $item->nama_kode_barang,
                        'nup' => $item->nup,
                        'merk' => $item->merk,
                        'tipe' => $item->tipe,
                        'tgl_perolehan' => $item->tgl_perolehan,
                        'kondisi' => (int)$item->kondisi,
                        'kondisi_text' => $item->kondisi_text,
                        'status_sewa' => (int)$item->status_sewa,
                        'nilai_perolehan' => (float)$item->nilai_perolehan,
                        'nilai_buku' => (float)$item->nilai_buku,
                        'lokasi_lengkap' => $item->lokasi_lengkap,
                        'umur_barang' => $item->umur_barang,
                    ], $payload);
                })->toArray();

                return [
                    'data' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'data' => $mapped,
                    ]
                ];
            } catch (\Throwable $e) {
                // Ignore DB error
            }
        }


        return [
            'data' => []
        ];
    }

    /**
     * Typed convenience methods wrapping makeRequest with SWR.
     */
    public function getKampus(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'kampus', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }

    public function getGedung(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'gedung', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }

    public function getRuangan(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'ruangan', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }

    public function getBmn(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'bmn', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }

    public function getBmnAll(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'bmn-all', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }

    public function getJenisBarang(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'jenis-barang', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }

    public function getKodeBarang(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'kode-barang', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }

    public function getSatker(array $params = []): ApiResponse
    {
        $data = $this->makeRequest('GET', 'satker', $params);
        return new ApiResponse(success: true, status: 200, message: 'OK', data: $data);
    }
}