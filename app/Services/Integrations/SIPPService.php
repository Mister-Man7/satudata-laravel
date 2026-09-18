<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SIPPService extends AbstractApiClient
{
    protected function serviceName(): string
    {
        return 'sipp';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.sipp.base_url', 'https://sipp.untirta.ac.id'),
            'auth_type' => 'bearer_login',
            // Timeout dialokasikan agar saat cache & database kosong, request API tidak terputus prematur
            'connect_timeout' => 10,
            'timeout' => 60,
        ];
    }

    /**
     * Override buildRequest untuk menambahkan browser headers & cf_clearance cookie
     * agar bisa bypass Cloudflare challenge yang memblokir request PHP.
     */
    protected function buildRequest(array $config): \Illuminate\Http\Client\PendingRequest
    {
        $request = parent::buildRequest($config);

        $headers = [
            'Accept'     => 'application/json',
            'User-Agent' => 'PostmanRuntime/7.43.0',
        ];

        // Tambahkan cf_clearance cookie jika dikonfigurasi di env
        $cfClearance = config('services.sipp.cf_clearance', '');
        if (!empty($cfClearance)) {
            $headers['Cookie'] = 'cf_clearance=' . $cfClearance;
        }

        return $request->withHeaders($headers);
    }

    /**
     * Ambil bearer token via login otomatis (/api/request-token) dan di-cache sesuai masa berlaku (30 menit).
     *
     * @throws \RuntimeException jika konfigurasi tidak lengkap atau login gagal
     */
    protected function getBearerToken(array $config): string
    {
        $cachedToken = Cache::get('sipp_bearer_token');
        if (!empty($cachedToken)) {
            return $cachedToken;
        }

        $baseUrl = $config['base_url'] ?? config('services.sipp.base_url', 'https://sipp.untirta.ac.id');
        $username = config('services.sipp.username');
        $password = config('services.sipp.password');

        if (empty($baseUrl) || empty($username) || empty($password)) {
            // Fallback jika kredensial belum ada tapi ada SIPP_API_TOKEN statis di env
            $fallbackToken = config('services.sipp.token');
            if (!empty($fallbackToken)) {
                return $fallbackToken;
            }
            Log::error('SIPP: Konfigurasi base_url, username, atau password belum diatur di .env');
            throw new \RuntimeException('Konfigurasi SIPP belum lengkap.');
        }

        $headers = [
            'Accept'     => 'application/json',
            'User-Agent' => 'PostmanRuntime/7.43.0',
        ];

        $cfClearance = config('services.sipp.cf_clearance', '');
        if (!empty($cfClearance)) {
            $headers['Cookie'] = 'cf_clearance=' . $cfClearance;
        }

        $response = \Illuminate\Support\Facades\Http::timeout(20)
            ->withHeaders($headers)
            ->post(
                rtrim($baseUrl, '/') . '/api/request-token',
                [
                    'username' => $username,
                    'password' => $password,
                ]
            );

        if ($response->failed()) {
            Log::error('SIPP: Gagal login otomatis untuk mengambil access token', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            // Fallback ke token env jika ada
            $fallbackToken = config('services.sipp.token');
            if (!empty($fallbackToken)) {
                return $fallbackToken;
            }

            throw new \RuntimeException('Gagal mengambil access token SIPP otomatis.');
        }

        $data = $response->json();
        $token = $data['data']['access_token'] ?? $data['access_token'] ?? $data['token'] ?? null;

        if (!$token) {
            Log::error('SIPP: Token tidak ditemukan dalam response login', ['body' => $response->body()]);
            throw new \RuntimeException('Token SIPP tidak ditemukan dalam response.');
        }

        // Default TTL 25 menit (token SIPP berlaku 30 menit)
        $ttl = now()->addMinutes(25);
        if (!empty($data['data']['expired_at'])) {
            try {
                $expiresAt = \Carbon\Carbon::parse($data['data']['expired_at']);
                if ($expiresAt->isFuture()) {
                    // Beri buffer 3 menit sebelum benar-benar expired
                    $ttl = $expiresAt->subMinutes(3);
                }
            } catch (\Throwable $e) {}
        }

        Cache::put('sipp_bearer_token', $token, $ttl);
        Log::info('SIPP: Berhasil auto-login dan update access token otomatis', ['expires_at' => $ttl->toDateTimeString()]);

        return $token;
    }

    /**
     * Override request untuk handle parameter semester wajib dan auto-refresh token saat 401 Unauthorized.
     */
    protected function request(string $method, string $endpoint, array $payload = []): ApiResponse
    {
        // Endpoint portofolio (publikasi, penelitian, pengabdian) berbasis NIP tidak membutuhkan filter semester
        $isPortofolio = in_array(rtrim($endpoint, '/'), ['/api/publikasi', '/api/penelitian', '/api/pengabdian']);

        // Pastikan parameter kode_semester dan semester terisi untuk data SIPP indikator/beban luaran
        if (!str_contains($endpoint, 'token') && !$isPortofolio) {
            $sem = $payload['kode_semester'] ?? $payload['semester'] ?? request()->input('semester', '20252');
            $payload['kode_semester'] = $sem;
            $payload['semester'] = $sem;
        }

        $response = parent::request($method, $endpoint, $payload);

        // Jika terkena 401 Unauthorized, hapus token di cache dan otomatis retry sekali dengan token baru
        if ($response->status === 401) {
            Cache::forget('sipp_bearer_token');
            Log::info('SIPP: Terkena 401 Unauthorized, mengambil token baru via auto-login dan melakukan retry...');
            return parent::request($method, $endpoint, $payload);
        }

        return $response;
    }

    /**
     * Ambil data dari endpoint SIPP dengan pola SWR (Stale-While-Revalidate).
     */
    public function getWithSwr(string $endpoint, array $params = []): ApiResponse
    {
        $cleanEndpoint = ltrim($endpoint, '/');
        $paramHash     = md5(serialize($params));
        $cacheKey      = "sipp.data.{$cleanEndpoint}.{$paramHash}";
        $freshFlagKey  = "sipp.fresh.{$cleanEndpoint}.{$paramHash}";

        // 1. Cek Cache
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if ($cached instanceof ApiResponse || is_array($cached)) {
                $responseObj = $cached instanceof ApiResponse ? $cached : new ApiResponse(true, 200, 'OK', $cached);

                // Jika masih segar (< 20 menit), return instan
                if (Cache::has($freshFlagKey)) {
                    return $responseObj;
                }

                // Stale hit: return data lama instan, refresh API di background
                if (function_exists('defer')) {
                    defer(function () use ($endpoint, $params, $cacheKey, $freshFlagKey) {
                        $this->refreshEndpointBackground($endpoint, $params, $cacheKey, $freshFlagKey);
                    });
                }

                return $responseObj;
            }
        }

        // 2. Cache miss: Ambil langsung dari API
        $response = $this->get($endpoint, $params);
        if ($response->success && !empty($response->data)) {
            Cache::put($cacheKey, $response->data, now()->addHours(6));
            Cache::put($freshFlagKey, true, now()->addMinutes(20));
        }

        return $response;
    }

    protected function refreshEndpointBackground(string $endpoint, array $params, string $cacheKey, string $freshFlagKey): void
    {
        try {
            $response = $this->get($endpoint, $params);
            if ($response->success && !empty($response->data)) {
                Cache::put($cacheKey, $response->data, now()->addHours(6));
                Cache::put($freshFlagKey, true, now()->addMinutes(20));
                Log::info("SIPP SWR: Background refresh berhasil untuk {$endpoint}");
            }
        } catch (\Throwable $e) {
            Log::warning("SIPP SWR: Background refresh gagal untuk {$endpoint}: " . $e->getMessage());
        }
    }

    /**
     * Ambil data publikasi.
     */
    public function getPublikasi(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/publikasi', $params);
    }

    public function getPenelitian(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/penelitian', $params);
    }

    public function getPengabdian(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/pengabdian', $params);
    }

    public function getPenelitianSinta1Sinta2(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/penelitian_sinta1_sinta2', $params);
    }

    public function getPengembangan(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/pengembangan', $params);
    }

    public function getPenelitianSinta3456(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/penelitian_sinta3456', $params);
    }

    public function getPenelitianJurnalNasionalIssn(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/penelitian_jurnal_nasional_issn', $params);
    }

    public function getPenelitianJurnalInternasionalQ1234(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/penelitian_jurnal_internasional_q1234', $params);
    }

    public function getPenelitianJurnalInternasionalPbb(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/penelitian_jurnal_internasional_pbb', $params);
    }

    public function getPengabdianMasyarakat(array $params = []): ApiResponse
    {
        return $this->getWithSwr('/api/pengabdian_masyarakat', $params);
    }

    public function getBukuReferensi(string $nip, array $params = []): ApiResponse
    {
        $params['jenis'] = 'buku_referensi';
        $params['nip']   = $nip;
        return $this->getWithSwr('/api/sipp', $params);
    }

    /**
     * Ambil ringkasan beban luaran ilmiah SIPP untuk NIP tertentu dengan pola SWR (Stale-While-Revalidate):
     * 1. Cek Cache utama (sipp_summary_*)
     *    - Jika fresh (< 20 menit) -> return <1ms instan
     *    - Jika stale (> 20 menit) -> return <1ms instan + background defer concurrent pool & sync DB
     * 2. Jika Cache miss -> Ambil dari Database lokal (dosen_sipps)
     *    - Return data DB instan (<10ms)
     *    - Simpan ke Cache sementara
     *    - Background defer concurrent pool ke API SIPP & update DB
     * 3. Jika Cache & DB kosong (cold start pertama kali) -> Jalankan concurrent pool secara synchronous
     */
    public function getDosenSippSummary(string $nip, ?string $semester = '20252'): array
    {
        $semester = $semester ?: '20252';
        $cleanNip = trim($nip);

        $summaryCacheKey = "sipp_summary_{$cleanNip}_{$semester}";
        $freshFlagKey    = "sipp_summary_fresh_{$cleanNip}_{$semester}";

        // 1. Cek Cache Utama
        if (Cache::has($summaryCacheKey)) {
            $cached = Cache::get($summaryCacheKey);
            if (is_array($cached)) {
                // Jika masih segar (< 20 menit), langsung kembalikan (<1ms)
                if (Cache::has($freshFlagKey)) {
                    return $cached;
                }

                // Stale hit: Kembalikan data lama instan, refresh concurrent pool & sync DB di background
                if (function_exists('defer')) {
                    defer(function () use ($cleanNip, $semester, $summaryCacheKey, $freshFlagKey) {
                        $this->fetchConcurrentAndSyncDb($cleanNip, $semester, $summaryCacheKey, $freshFlagKey);
                    });
                }

                return $cached;
            }
        }

        // 2. Cache Miss: Cek Database lokal (dosen_sipps)
        try {
            $dbRecord = \App\Models\DosenSipp::where('nip', $cleanNip)
                ->where('semester', $semester)
                ->first();

            if ($dbRecord) {
                $dbMetrics = [
                    'sinta12' => (int) $dbRecord->sinta12,
                    'sinta36' => (int) $dbRecord->sinta36,
                    'jurnal_internasional_q' => (int) $dbRecord->jurnal_internasional_q,
                    'jurnal_internasional_pbb' => (int) $dbRecord->jurnal_internasional_pbb,
                    'jurnal_nasional_issn' => (int) $dbRecord->jurnal_nasional_issn,
                    'pengembangan' => (int) $dbRecord->pengembangan,
                    'pengabdian_masyarakat' => (int) $dbRecord->pengabdian_masyarakat,
                    'buku_referensi' => (int) $dbRecord->buku_referensi,
                ];

                // Simpan ke cache agar request berikutnya <1ms
                Cache::put($summaryCacheKey, $dbMetrics, now()->addHours(6));

                // Picu pembaruan API secara concurrent di background dengan defer
                if (function_exists('defer')) {
                    defer(function () use ($cleanNip, $semester, $summaryCacheKey, $freshFlagKey) {
                        $this->fetchConcurrentAndSyncDb($cleanNip, $semester, $summaryCacheKey, $freshFlagKey);
                    });
                }

                return $dbMetrics;
            }
        } catch (\Throwable $e) {
            Log::warning("SIPP SWR: Gagal cek DB lokal dosen_sipps: " . $e->getMessage());
        }

        // 3. Cold start (Cache & DB belum ada data): Jalankan concurrent pool secara synchronous
        return $this->fetchConcurrentAndSyncDb($cleanNip, $semester, $summaryCacheKey, $freshFlagKey);
    }

    /**
     * Jalankan concurrent HTTP pool ke seluruh endpoint SIPP secara paralel,
     * hitung metrik beban, sinkronkan ke database dosen_sipps, dan perbarui cache.
     */
    public function fetchConcurrentAndSyncDb(string $nip, string $semester, string $summaryCacheKey, string $freshFlagKey): array
    {
        @set_time_limit(120);
        $cleanNip = trim($nip);

        $endpoints = [
            'sinta12'      => '/api/penelitian_sinta1_sinta2',
            'sinta36'      => '/api/penelitian_sinta3456',
            'jurnal_q'     => '/api/penelitian_jurnal_internasional_q1234',
            'jurnal_pbb'   => '/api/penelitian_jurnal_internasional_pbb',
            'jurnal_issn'  => '/api/penelitian_jurnal_nasional_issn',
            'pengembangan' => '/api/pengembangan',
            'pengabdian'   => '/api/pengabdian_masyarakat',
            'buku'         => '/api/sipp',
        ];

        $lists = [];
        $missing = [];

        foreach ($endpoints as $key => $endpoint) {
            $cacheKey = ($key === 'buku') ? "sipp_cache_buku_{$cleanNip}_{$semester}" : "sipp_cache_{$key}_{$semester}";
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                $lists[$key] = $cached;
            } else {
                $missing[$key] = $endpoint;
            }
        }

        // Jalankan fetch secara CONCURRENT menggunakan Http::pool untuk endpoint yang missing
        if (!empty($missing)) {
            try {
                $token = $this->getBearerToken($this->config());
                $baseUrl = rtrim(config('services.sipp.base_url', 'https://sipp.untirta.ac.id'), '/');
                $headers = [
                    'Accept'     => 'application/json',
                    'User-Agent' => 'PostmanRuntime/7.43.0',
                ];
                $cfClearance = config('services.sipp.cf_clearance', '');
                if (!empty($cfClearance)) {
                    $headers['Cookie'] = 'cf_clearance=' . $cfClearance;
                }

                $responses = \Illuminate\Support\Facades\Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($missing, $baseUrl, $token, $headers, $semester, $cleanNip) {
                    $reqs = [];
                    foreach ($missing as $key => $endpoint) {
                        $params = ['kode_semester' => $semester, 'semester' => $semester];
                        if ($key === 'buku') {
                            $params['jenis'] = 'buku_referensi';
                            $params['nip'] = $cleanNip;
                        }
                        $reqs[$key] = $pool->as($key)
                            ->withToken($token)
                            ->withHeaders($headers)
                            ->connectTimeout(10)
                            ->timeout(40)
                            ->get($baseUrl . $endpoint, $params);
                    }
                    return $reqs;
                });

                foreach ($missing as $key => $endpoint) {
                    $resp = $responses[$key] ?? null;
                    $list = [];
                    if ($resp instanceof \Illuminate\Http\Client\Response && $resp->successful()) {
                        $data = $resp->json();
                        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
                            $data = $data['data'];
                        }
                        $list = is_array($data) ? $data : [];
                        $cacheKey = ($key === 'buku') ? "sipp_cache_buku_{$cleanNip}_{$semester}" : "sipp_cache_{$key}_{$semester}";
                        if (!empty($list)) {
                            Cache::put($cacheKey, $list, now()->addHours(6));
                        }
                    }
                    $lists[$key] = $list;
                }
            } catch (\Throwable $e) {
                Log::warning("SIPP SWR: Gagal concurrent pool fetch untuk {$cleanNip}: " . $e->getMessage());
            }
        }

        $sinta12List = $lists['sinta12'] ?? [];
        $sinta36List = $lists['sinta36'] ?? [];
        $jurnalQList = $lists['jurnal_q'] ?? [];
        $jurnalPbbList = $lists['jurnal_pbb'] ?? [];
        $jurnalIssnList = $lists['jurnal_issn'] ?? [];
        $pengembanganList = $lists['pengembangan'] ?? [];
        $pengabdianList = $lists['pengabdian'] ?? [];
        $bukuRefBeban = $this->extractBebanFromList($lists['buku'] ?? [], $cleanNip);

        $metrics = [
            'sinta12' => $this->extractBebanFromList($sinta12List, $cleanNip),
            'sinta36' => $this->extractBebanFromList($sinta36List, $cleanNip),
            'jurnal_internasional_q' => $this->extractBebanFromList($jurnalQList, $cleanNip),
            'jurnal_internasional_pbb' => $this->extractBebanFromList($jurnalPbbList, $cleanNip),
            'jurnal_nasional_issn' => $this->extractBebanFromList($jurnalIssnList, $cleanNip),
            'pengembangan' => $this->extractBebanFromList($pengembanganList, $cleanNip),
            'pengabdian_masyarakat' => $this->extractBebanFromList($pengabdianList, $cleanNip),
            'buku_referensi' => $bukuRefBeban,
        ];

        // Sinkronkan ke database dosen_sipps jika terdapat metrik beban (> 0)
        $totalBeban = array_sum($metrics);
        if ($totalBeban > 0) {
            try {
                \App\Models\DosenSipp::updateOrCreate(
                    ['nip' => $cleanNip, 'semester' => $semester],
                    $metrics
                );
            } catch (\Throwable $e) {
                Log::warning("SIPP SWR: Gagal sync database dosen_sipps NIP {$cleanNip}: " . $e->getMessage());
            }
        }

        // Simpan ke cache utama & pasang fresh flag
        Cache::put($summaryCacheKey, $metrics, now()->addHours(6));
        Cache::put($freshFlagKey, true, now()->addMinutes(20));

        return $metrics;
    }

    /**
     * Helper kalkulasi beban luaran ilmiah dari list API SIPP untuk NIP tertentu.
     */
    protected function extractBebanFromList(array $list, string $nip): int
    {
        $cleanNip = trim($nip);

        // 1. Direct associative key match
        if (isset($list[$cleanNip])) {
            $val = $list[$cleanNip];
            if (is_numeric($val)) return (int)$val;
            if (is_array($val)) {
                foreach (['beban', 'total_beban', 'jumlah_beban', 'sks', 'bobot', 'total', 'jumlah'] as $field) {
                    if (isset($val[$field]) && is_numeric($val[$field])) {
                        return (int)$val[$field];
                    }
                }
                return count($val);
            }
        }

        // 2. Iterative search & summation across all items for this NIP
        $totalBeban = 0;
        $matchFound = false;

        foreach ($list as $key => $item) {
            if (!is_array($item)) {
                if (trim((string)$key) === $cleanNip && is_numeric($item)) {
                    return (int)$item;
                }
                continue;
            }

            $itemNip = trim((string)($item['nip'] ?? $item['nip_dosen'] ?? $item['nidn'] ?? $item['kd_pegawai'] ?? ''));
            if ($itemNip !== '' && $itemNip === $cleanNip) {
                $matchFound = true;
                $itemBeban = null;
                foreach (['beban', 'total_beban', 'jumlah_beban', 'sks', 'bobot', 'total', 'jumlah'] as $field) {
                    if (isset($item[$field]) && is_numeric($item[$field])) {
                        $itemBeban = (int)$item[$field];
                        break;
                    }
                }
                $totalBeban += ($itemBeban !== null ? $itemBeban : 1);
            }
        }

        return $matchFound ? $totalBeban : 0;
    }
}