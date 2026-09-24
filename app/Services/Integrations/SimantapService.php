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
        return Cache::remember(LoginChromium::SIMANTAP_TOKEN_KEY, now()->addMinutes(60), function () use ($config) {
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
            Cache::forget(LoginChromium::SIMANTAP_TOKEN_KEY);
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
        // Panggilan HTTP langsung dari PHP selalu ditantang Cloudflare (HTTP 403
        // "Just a moment"), jadi penarikan dialihkan ke penarik Chromium lokal
        // lewat SourceRevalidator. Penanda segar tetap dipasang agar tidak memicu
        // penarikan beruntun.
        Cache::put($freshFlagKey, true, now()->addMinutes((int) config('satudata.swr.fresh_minutes', 20)));

        app(SourceRevalidator::class)->trigger('simantap.aset');
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
     * Ringkasan kampus langsung dari tabel `asets`: id kampus asli (kolom
     * id_kampus) beserta nama yang diambil dari segmen pertama `lokasi_lengkap`,
     * jadi tidak memakai daftar kampus tetap di kode.
     *
     * @return array<string, array{id: string, nama: string, total: int}>
     */
    protected function kampusDariData(): array
    {
        return Cache::remember('simantap_kampus_dari_data_v1', now()->addHours(6), function () {
            $rows = \Illuminate\Support\Facades\DB::table('asets')
                ->select(
                    'id_kampus',
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'),
                    \Illuminate\Support\Facades\DB::raw("MIN(CASE WHEN lokasi_lengkap IS NOT NULL AND lokasi_lengkap <> '-' THEN lokasi_lengkap END) as contoh_lokasi")
                )
                ->whereNotNull('id_kampus')
                ->whereNotIn('id_kampus', ['', 'NULL'])
                ->groupBy('id_kampus')
                ->get();

            $hasil = [];

            foreach ($rows as $row) {
                $segmen = $this->segmenLokasi((string) ($row->contoh_lokasi ?? ''));
                $nama = $segmen[0] ?? '';

                // Lokasi yang tidak terbaca dilewati, bukan diberi nama karangan.
                if ($nama === '') {
                    continue;
                }

                $hasil[(string) $row->id_kampus] = [
                    'id' => (string) $row->id_kampus,
                    'nama' => $nama,
                    'total' => (int) $row->total,
                ];
            }

            return $hasil;
        });
    }

    /**
     * Pecah `lokasi_lengkap` menjadi segmen berurutan:
     * kampus, gedung, lantai, ruangan. Segmen kosong atau '-' tetap dipertahankan
     * posisinya agar penomoran segmen tidak bergeser.
     *
     * @return array<int, string>
     */
    protected function segmenLokasi(string $lokasi): array
    {
        $lokasi = trim($lokasi);

        if ($lokasi === '' || $lokasi === '-') {
            return [];
        }

        return array_map('trim', explode(' - ', $lokasi));
    }

    /**
     * Id kampus bergaya URL lama, mis. "Kampus Sindangsari" -> KAMPUS-SINDANGSARI.
     * Dipakai agar tautan lama tetap dapat dibuka.
     */
    protected function slugKampus(string $namaKampus): string
    {
        $nama = preg_replace('/^kampus\s+/i', '', trim($namaKampus));

        return 'KAMPUS-' . strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', $nama));
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
                return Cache::remember('simantap_kampus_summary_v4', now()->addHours(6), function () {
                    // Daftar kampus beserta jumlah asetnya diambil dari kolom
                    // id_kampus pada tabel `asets`, tanpa daftar kampus tetap.
                    $kampusList = [];
                    foreach ($this->kampusDariData() as $kampus) {
                        $kampusList[] = [
                            'id_kampus' => $kampus['id'],
                            'nama_kampus' => $kampus['nama'],
                            'total_aset' => $kampus['total'],
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
            $kampusId = $parts[1] ?? '';

            try {
                $kampus = $this->kampusDariData();
                $target = $kampus[$kampusId] ?? null;

                // Tautan lama memakai id berbasis slug nama kampus.
                if ($target === null) {
                    foreach ($kampus as $kandidat) {
                        if ($this->slugKampus($kandidat['nama']) === strtoupper((string) $kampusId)) {
                            $target = $kandidat;
                            break;
                        }
                    }
                }

                $namaK = $target['nama'] ?? '';

                $distinct = $this->getDistinctLocations();
                $gedungCounts = [];

                foreach ($distinct as $r) {
                    $lok = is_array($r) ? ($r['lokasi_lengkap'] ?? '') : ($r->lokasi_lengkap ?? '');
                    $total = (int)(is_array($r) ? ($r['total'] ?? 0) : ($r->total ?? 0));

                    $segmen = $this->segmenLokasi((string) $lok);

                    // Kampus dicocokkan dari segmen pertama lokasi, bukan kata kunci.
                    if (empty($segmen) || $namaK === '' || strcasecmp($segmen[0], $namaK) !== 0) {
                        continue;
                    }

                    $gName = $segmen[1] ?? '';
                    if ($gName === '' || $gName === '-') {
                        $gName = 'Gedung tidak diketahui';
                    }

                    $gedungCounts[$gName] = ($gedungCounts[$gName] ?? 0) + $total;
                }

                $gedungList = [];
                foreach ($gedungCounts as $gName => $count) {
                    $gSlug = 'GEDUNG-' . strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', preg_replace('/^gedung\s+/i', '', $gName)));
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
                $kampusId = (string) end($parts);

                // Kampus boleh kosong: endpoint 'gedung' memang menampilkan seluruh gedung.
                $namaK = '';
                $kampus = $this->kampusDariData();
                if (isset($kampus[$kampusId])) {
                    $namaK = $kampus[$kampusId]['nama'];
                } else {
                    foreach ($kampus as $kandidat) {
                        if ($this->slugKampus($kandidat['nama']) === strtoupper($kampusId)) {
                            $namaK = $kandidat['nama'];
                            break;
                        }
                    }
                }

                $distinct = $this->getDistinctLocations();
                $gedungCounts = [];

                foreach ($distinct as $r) {
                    $lok = is_array($r) ? ($r['lokasi_lengkap'] ?? '') : ($r->lokasi_lengkap ?? '');
                    $total = (int)(is_array($r) ? ($r['total'] ?? 0) : ($r->total ?? 0));

                    $segmen = $this->segmenLokasi((string) $lok);
                    if (empty($segmen)) {
                        continue;
                    }

                    if ($namaK !== '' && strcasecmp($segmen[0], $namaK) !== 0) {
                        continue;
                    }

                    $gName = $segmen[1] ?? '';
                    if ($gName === '' || $gName === '-') {
                        $gName = 'Gedung tidak diketahui';
                    }

                    $gedungCounts[$gName] = ($gedungCounts[$gName] ?? 0) + $total;
                }

                $gedungList = [];
                foreach ($gedungCounts as $gName => $count) {
                    $gSlug = 'GEDUNG-' . strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', preg_replace('/^gedung\s+/i', '', $gName)));
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
                $targetGedung = trim(preg_replace('/^GEDUNG\s+/i', '', trim(str_replace('-', ' ', preg_replace('/^GEDUNG-/i', '', (string) $gedungSlug)))));

                $distinct = $this->getDistinctLocations();
                $ruanganCounts = [];

                foreach ($distinct as $r) {
                    $lok = is_array($r) ? ($r['lokasi_lengkap'] ?? '') : ($r->lokasi_lengkap ?? '');
                    $total = (int)(is_array($r) ? ($r['total'] ?? 0) : ($r->total ?? 0));

                    $segmen = $this->segmenLokasi((string) $lok);
                    if (empty($segmen)) {
                        continue;
                    }

                    // Gedung dicocokkan dari segmen kedua lokasi, tanpa awalan "Gedung"
                    // agar cocok dengan id bergaya GEDUNG-<nama>.
                    $namaGedung = preg_replace('/^gedung\s+/i', '', (string) ($segmen[1] ?? ''));
                    if ($targetGedung !== '' && strcasecmp($namaGedung, $targetGedung) !== 0) {
                        continue;
                    }

                    // Nama ruangan adalah segmen keempat lokasi (kampus - gedung -
                    // lantai - ruangan). Nama yang tidak berawalan "Ruang"
                    // (mis. "Bed Room 3.5", "Lobby") tetap dipakai apa adanya.
                    $rName = trim((string) ($segmen[3] ?? ''));

                    if ($rName === '' || $rName === '-') {
                        $rName = 'Ruang tidak diketahui';
                    }

                    $ruanganCounts[$rName] = ($ruanganCounts[$rName] ?? 0) + $total;
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

                    // Cocokkan lewat kolom slug berindeks lebih dulu; LIKE pada
                    // lokasi_lengkap hanya dipakai bila tidak ada baris yang cocok
                    // (mis. data lama yang belum punya slug).
                    $slug = strtoupper($targetId);

                    if ($slug !== '' && (clone $query)->where('ruangan_slug', $slug)->exists()) {
                        $query->where('ruangan_slug', $slug);
                    } elseif (!empty($likePattern)) {
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
                    // Seluruh field diambil dari kolom tabel `asets`; payload JSON tidak
                    // lagi diikutkan sebagai sumber data.
                    return [
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
                    ];
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