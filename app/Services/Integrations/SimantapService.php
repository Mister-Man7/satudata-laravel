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
     * @throws \RuntimeException jika login gagal
     */
    protected function getBearerToken(array $config): string
    {
        return Cache::remember('simantap_api_token', now()->addMinutes(60), function () use ($config) {
            $email = config('services.simantap.email');
            $password = config('services.simantap.password');

            if (empty($config['base_url']) || empty($email) || empty($password)) {
                Log::error('Simantap: Konfigurasi belum lengkap');
                throw new \RuntimeException('Konfigurasi Simantap belum lengkap.');
            }

            $response = Http::post(rtrim($config['base_url'], '/') . '/auth/login', [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->failed()) {
                Log::error('Simantap: Gagal login', ['status' => $response->status()]);
                throw new \RuntimeException('Gagal login ke Simantap.');
            }

            $data = $response->json();

            if (!isset($data['success']) || $data['success'] !== true) {
                Log::error('Simantap: Login gagal', ['response' => $data]);
                throw new \RuntimeException('Login ke Simantap gagal.');
            }

            $token = $data['data']['token'] ?? null;

            if (!$token) {
                Log::error('Simantap: Token tidak ditemukan');
                throw new \RuntimeException('Token Simantap tidak ditemukan.');
            }

            return $token;
        });
    }

    /**
     * Override request untuk handle token expired (401) → refresh & retry sekali.
     */
    protected function request(string $method, string $endpoint, array $payload = []): ApiResponse
    {
        $response = parent::request($method, $endpoint, $payload);

        if ($response->status === 401) {
            Cache::forget('simantap_api_token');
            Log::info('Simantap: Token expired, retry dengan token baru');
            return parent::request($method, $endpoint, $payload);
        }

        return $response;
    }

    /**
     * Kirim request dengan method dinamis (backward compatible).
     *
     * @deprecated Gunakan get(), post(), put(), delete() secara langsung
     */
    public function makeRequest(string $method, string $endpoint, array $params = []): ?array
    {
        try {
            $response = match (strtolower($method)) {
                'get' => $this->get($endpoint, $params),
                'post' => $this->post($endpoint, $params),
                default => ApiResponse::unexpectedError("Method '{$method}' belum didukung."),
            };

            if ($response->success && !empty($response->data)) {
                return $response->data;
            }
        } catch (\Throwable $e) {
            Log::warning('Simantap API unavailable, using local database/fallback', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
        }

        return $this->hasilFallbackSimantapData($endpoint, $params);
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
                $kampusMap = [
                    'KAMPUS-SINDANGSARI' => ['nama' => 'Kampus Sindangsari', 'keyword' => 'Sindangsari', 'count' => 0],
                    'KAMPUS-PAKUPATAN' => ['nama' => 'Kampus Pakupatan', 'keyword' => 'Pakupatan', 'count' => 0],
                    'KAMPUS-KEPANDEAN' => ['nama' => 'Kampus Kepandean', 'keyword' => 'Kepandean', 'count' => 0],
                    'KAMPUS-CILEGON' => ['nama' => 'Kampus Cilegon', 'keyword' => 'Cilegon', 'count' => 0],
                    'KAMPUS-CIWARU' => ['nama' => 'Kampus Ciwaru', 'keyword' => 'Ciwaru', 'count' => 0],

                ];

                $all = \App\Models\Aset::select('lokasi_lengkap')->get();
                foreach ($all as $row) {
                    $lok = $row->lokasi_lengkap ?? '';
                    $matched = false;
                    foreach ($kampusMap as $kId => &$kData) {
                        if (stripos($lok, $kData['keyword']) !== false) {
                            $kData['count']++;
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        $kampusMap['KAMPUS-SINDANGSARI']['count']++;
                    }
                }
                unset($kData);


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
                $rows = \App\Models\Aset::select('lokasi_lengkap')
                    ->where('lokasi_lengkap', 'like', "%{$kw}%")
                    ->get();

                $gedungCounts = [];
                foreach ($rows as $r) {
                    $lParts = array_map('trim', explode('-', $r->lokasi_lengkap ?? ''));
                    $gName = $lParts[1] ?? 'Gedung Utama';
                    if (!isset($gedungCounts[$gName])) {
                        $gedungCounts[$gName] = 0;
                    }
                    $gedungCounts[$gName]++;
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

                $kw = '';
                if (str_contains($kampusId, 'PAKUPATAN')) $kw = 'Pakupatan';
                elseif (str_contains($kampusId, 'KEPANDEAN')) $kw = 'Kepandean';
                elseif (str_contains($kampusId, 'CILEGON')) $kw = 'Cilegon';
                elseif (str_contains($kampusId, 'CIWARU')) $kw = 'Ciwaru';
                elseif (str_contains($kampusId, 'SINDANGSARI')) $kw = 'Sindangsari';

                $query = \App\Models\Aset::select('lokasi_lengkap')->whereNotNull('lokasi_lengkap');
                if (!empty($kw)) {
                    $query->where('lokasi_lengkap', 'like', "%{$kw}%");
                }
                $rows = $query->get();

                $gedungCounts = [];
                foreach ($rows as $r) {
                    $lParts = array_map('trim', explode('-', $r->lokasi_lengkap ?? ''));
                    $gName = $lParts[1] ?? 'Gedung Utama';
                    if (!isset($gedungCounts[$gName])) {
                        $gedungCounts[$gName] = 0;
                    }
                    $gedungCounts[$gName]++;
                }

                $gedungList = [];
                foreach ($gedungCounts as $gName => $count) {
                    $gSlug = 'GEDUNG-' . strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', $gName));
                    $gedungList[] = [
                        'id_gedung' => $gSlug,
                        'nama_gedung' => $gName,
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

                $rows = \App\Models\Aset::select('lokasi_lengkap')->whereNotNull('lokasi_lengkap')->limit(2000)->get();

                $ruanganCounts = [];
                foreach ($rows as $r) {
                    $lParts = array_map('trim', explode('-', $r->lokasi_lengkap ?? ''));
                    $rName = end($lParts);
                    if (empty($rName) || $rName === '-') {
                        $rName = 'Ruang Operasional';
                    }
                    if (!isset($ruanganCounts[$rName])) {
                        $ruanganCounts[$rName] = 0;
                    }
                    $ruanganCounts[$rName]++;
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
                    $searchTerm = trim(str_replace(['RUANG-', 'GEDUNG-', 'KAMPUS-', '-'], [' ', ' ', ' ', ' '], $targetId));
                    if (!empty($searchTerm)) {
                        $query->where(function ($q) use ($targetId, $searchTerm) {
                            $q->where('id_ruangan', $targetId)
                              ->orWhere('lokasi_lengkap', 'like', "%{$searchTerm}%");
                        });
                    }
                } elseif (str_contains($ep, 'by-gedung/')) {
                    $parts = explode('/', $ep);
                    $targetId = urldecode(end($parts));
                    $searchTerm = trim(str_replace(['GEDUNG-', 'KAMPUS-', '-'], [' ', ' ', ' '], $targetId));
                    if (!empty($searchTerm)) {
                        $query->where(function ($q) use ($targetId, $searchTerm) {
                            $q->where('id_gedung', $targetId)
                              ->orWhere('lokasi_lengkap', 'like', "%{$searchTerm}%");
                        });
                    }
                } elseif (str_contains($ep, 'by-kampus/')) {
                    $parts = explode('/', $ep);
                    $targetId = urldecode(end($parts));
                    $searchTerm = trim(str_replace(['KAMPUS-', '-'], [' ', ' '], $targetId));
                    if (!empty($searchTerm)) {
                        $query->where(function ($q) use ($targetId, $searchTerm) {
                            $q->where('id_kampus', $targetId)
                              ->orWhere('lokasi_lengkap', 'like', "%{$searchTerm}%");
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



}