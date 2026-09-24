<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Services\Integrations\AssetSyncLauncher;
use App\Services\Integrations\SimantapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
        $params['per_page'] = $params['per_page'] ?? config('aset.per_page.daftar', 100);

        $response = $this->apiService->makeRequest('GET', 'kampus', $params);
        $kampusList = $response['data']['data'] ?? $response['data'] ?? [];

        $warning = null;
        $statusInfo = null;

        if ($response === null) {
            $warning = 'Gagal mengambil data kampus dari server. Silakan refresh halaman.';
        } else {
            // Ambil info status barang untuk alert
            $statusInfo = $this->getStatusBarangInfo();
        }

        $kampusCounts = $this->asetCountsBy('id_kampus');

        $datas = collect($kampusList)->map(function ($kampus) use ($kampusCounts) {
            $id = $kampus['id_kampus'] ?? null;
            $totalAset = (int) ($kampus['total_aset'] ?? $kampusCounts[$id] ?? 0);

            return [
                'id' => $id,
                'title' => $kampus['nama_kampus'],
                // API /kampus tidak mengembalikan total aset → agregasi tabel `asets`.
                'count' => $totalAset > 0
                    ? number_format($totalAset, 0, ',', '.') . ' Unit Aset'
                    : null,
                'total_aset' => $totalAset,
                'icon' => 'building',
                'updated' => $kampus['updated_at'] ?? now(),
            ];
        });


        $hitungRingkasan = function () use ($kampusList, $kampusCounts) {
            if (!class_exists(\App\Models\Aset::class) || !\Illuminate\Support\Facades\Schema::hasTable('asets')) {
                return null;
            }
            $totalUnit = \App\Models\Aset::count();
            $nilaiPerolehan = \App\Models\Aset::sum('nilai_perolehan');
            $kondisiBaik = $this->scopeKondisi(\App\Models\Aset::query(), 'baik')->count();
            $kondisiRusakBerat = $this->scopeKondisi(\App\Models\Aset::query(), 'rusak_berat')->count();
            $kondisiRusakRingan = $this->scopeKondisi(\App\Models\Aset::query(), 'rusak_ringan')->count();

            $kampusBreakdown = [];
            $listToProcess = !empty($kampusList) ? $kampusList : [];

            if (empty($listToProcess)) {
                $listToProcess = $this->kampusDariData($kampusCounts);
            }

            foreach ($listToProcess as $kampus) {
                $name = $kampus['nama_kampus'] ?? '-';
                $kId = $kampus['id_kampus'] ?? null;
                $kw = trim(str_ireplace('kampus', '', $name));

                $qBase = \App\Models\Aset::query();
                // Id kampus dipakai langsung lewat kolom id_kampus; kampus hasil
                // fallback pun berasal dari kolom yang sama di tabel aset.
                if (!empty($kId) && isset($kampusCounts[$kId])) {
                    $qBase->where('id_kampus', $kId);
                } elseif (!empty($kw)) {
                    $qBase->where('lokasi_lengkap', 'like', '%' . $kw . '%');
                }

                $baik = $this->scopeKondisi(clone $qBase, 'baik')->count();
                $rusakRingan = $this->scopeKondisi(clone $qBase, 'rusak_ringan')->count();
                $rusakBerat = $this->scopeKondisi(clone $qBase, 'rusak_berat')->count();

                // Baris yang tidak punya id kampus atau lokasi tidak diatribusikan
                // ke kampus mana pun, jadi angka per kampus murni dari data.

                $total = $baik + $rusakRingan + $rusakBerat;
                $pctBaik = $total > 0 ? round(($baik / $total) * 100, 1) : 0;

                $kampusBreakdown[] = [
                    'id_kampus' => $kId,
                    'nama_kampus' => $name,
                    'total_unit' => $total,
                    'kondisi_baik' => $baik,
                    'kondisi_rusak_ringan' => $rusakRingan,
                    'kondisi_rusak_berat' => $rusakBerat,
                    'pct_baik' => $pctBaik,
                ];
            }

            return [
                'total_unit' => $totalUnit,
                'nilai_perolehan' => $nilaiPerolehan,
                'kondisi_baik' => $kondisiBaik,
                'kondisi_rusak_berat' => $kondisiRusakBerat,
                'kondisi_rusak_ringan' => $kondisiRusakRingan,
                'total_kampus' => count($kampusBreakdown),
                // Unit yang belum punya id kampus atau lokasi pada data sumber.
                'total_tanpa_kampus' => max(0, $totalUnit - array_sum(array_column($kampusBreakdown, 'total_unit'))),
                'kampus_breakdown' => $kampusBreakdown,
            ];
        };

        $summaryStats = $this->swr('aset_big_card_stats', 60, 10, $hitungRingkasan);

        return view('Assets.aset', compact('datas', 'warning', 'statusInfo', 'summaryStats'), [
            'title' => 'Aset',
            'level' => 'kampus'
        ]);
    }

    /**
     * Stale-while-revalidate untuk data turunan database: hasil cache langsung
     * dipakai, pembaruannya dijalankan di latar belakang saat penanda segar lewat.
     */
    protected function swr(string $key, int $ttlMenit, int $umurSegarMenit, callable $hitung)
    {
        $freshFlagKey = $key . '.fresh';

        if (\Illuminate\Support\Facades\Cache::has($key)) {
            $data = \Illuminate\Support\Facades\Cache::get($key);

            if (!\Illuminate\Support\Facades\Cache::has($freshFlagKey) && function_exists('defer')) {
                defer(function () use ($key, $freshFlagKey, $ttlMenit, $umurSegarMenit, $hitung) {
                    try {
                        \Illuminate\Support\Facades\Cache::put($key, $hitung(), now()->addMinutes($ttlMenit));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("SWR {$key} gagal diperbarui: " . $e->getMessage());
                    } finally {
                        \Illuminate\Support\Facades\Cache::put($freshFlagKey, true, now()->addMinutes($umurSegarMenit));
                    }
                });
            }

            return $data;
        }

        $data = $hitung();
        \Illuminate\Support\Facades\Cache::put($key, $data, now()->addMinutes($ttlMenit));
        \Illuminate\Support\Facades\Cache::put($freshFlagKey, true, now()->addMinutes($umurSegarMenit));

        return $data;
    }

    /**
     * Pemetaan kunci kondisi ke kode dan label, sumbernya config/aset.php.
     */
    protected function kondisi(string $kunci): array
    {
        return config("aset.kondisi.{$kunci}", ['kode' => null, 'label' => null]);
    }

    /**
     * Batasi query ke satu kondisi lewat kode numeriknya (mis. 1 = Baik).
     *
     * Hanya kolom `kondisi` yang dicocokkan supaya indeks `asets(kondisi)` dan
     * `asets(id_kampus, kondisi)` dapat dipakai. Mencocokkan `kondisi_text` dengan
     * OR memaksa pemindaian seluruh tabel, sedangkan datanya selalu sinkron dengan
     * kodenya (1 = Baik, 3 = Rusak Berat), jadi hasilnya identik.
     */
    protected function scopeKondisi($query, string $kunci)
    {
        return $query->where('kondisi', $this->kondisi($kunci)['kode']);
    }

    /**
     * Daftar kampus dari data aset yang tersimpan, dipakai bila API /kampus
     * tidak mengembalikan data. Id maupun namanya berasal dari tabel `asets`.
     */
    protected function kampusDariData(array $kampusCounts): array
    {
        $list = [];

        foreach (array_keys($kampusCounts) as $idKampus) {
            if (empty($idKampus) || strtoupper((string) $idKampus) === 'NULL') {
                continue;
            }

            $list[] = [
                'id_kampus' => $idKampus,
                'nama_kampus' => $this->namaKampusDariData((string) $idKampus),
            ];
        }

        return $list;
    }

    /**
     * Nama kampus diambil dari segmen pertama kolom `lokasi_lengkap`
     * (mis. "Kampus Sindangsari - Asrama Putri - Lantai 3 - Bed Room 3.5").
     * Bila tidak ada, id kampus dipakai apa adanya. Payload JSON tidak dibaca
     * sebagai sumber data.
     */
    protected function namaKampusDariData(string $idKampus): string
    {
        $nama = null;

        try {
            $lokasi = \App\Models\Aset::where('id_kampus', $idKampus)
                ->whereNotNull('lokasi_lengkap')
                ->where('lokasi_lengkap', '!=', '-')
                ->value('lokasi_lengkap');

            if (!empty($lokasi)) {
                $nama = trim(explode(' - ', $lokasi)[0]);
            }
        } catch (\Throwable $e) {
            $nama = null;
        }

        return !empty($nama) ? $nama : $idKampus;
    }

    /**
     * Get status barang info untuk alert
     * Menggunakan cache 1 jam untuk hindari timeout
     */
    protected function getStatusBarangInfo()
    {
        try {
            $cached = \Cache::get('aset_status_summary');
            if ($cached !== null) {
                return $cached;
            }

            if (class_exists(\Illuminate\Support\Facades\DB::class) && \Illuminate\Support\Facades\Schema::hasTable('asets')) {
                $total = \Illuminate\Support\Facades\DB::table('asets')->count();
                $ases = \Illuminate\Support\Facades\DB::table('asets');
                $rusakBerat = $this->scopeKondisi(clone $ases, 'rusak_berat')->count();
                $rusakRingan = $this->scopeKondisi(clone $ases, 'rusak_ringan')->count();
                $baik = $this->scopeKondisi(clone $ases, 'baik')->count();
                $lainnya = max(0, $total - ($baik + $rusakRingan + $rusakBerat));

                if ($total > 0) {
                    if ($rusakRingan === 0 && $rusakBerat === 0 && $lainnya === 0) {
                        $result = [
                            'type' => 'success',
                            'message' => "Seluruh inventaris dalam kondisi baik (" . number_format($total, 0, ',', '.') . " unit)",
                            'total' => $total,
                        ];
                    } elseif ($rusakBerat > 0) {
                        $message = "Ditemukan " . number_format($rusakBerat, 0, ',', '.') . " unit dalam kondisi rusak berat";
                        if ($rusakRingan > 0) {
                            $message .= " dan " . number_format($rusakRingan, 0, ',', '.') . " unit rusak ringan";
                        }
                        $message .= " dari total " . number_format($total, 0, ',', '.') . " unit inventaris BMN";
                        $result = [
                            'type' => 'error',
                            'message' => $message,
                            'total' => $total,
                        ];
                    } elseif ($rusakRingan > 0) {
                        $result = [
                            'type' => 'warning',
                            'message' => "Terdapat " . number_format($rusakRingan, 0, ',', '.') . " unit inventaris dengan kondisi rusak ringan dari total " . number_format($total, 0, ',', '.') . " unit",
                            'total' => $total,
                        ];
                    } else {
                        $result = [
                            'type' => 'info',
                            'message' => "Total " . number_format($total, 0, ',', '.') . " unit inventaris tercatat dalam sistem",
                            'total' => $total,
                        ];
                    }

                    \Cache::put('aset_status_summary', $result, now()->addHours(1));
                    return $result;
                }
            }
        } catch (\Throwable $e) {
            \Log::error('getStatusBarangInfo error', ['message' => $e->getMessage()]);
        }

        return null;
    }


    /**
     * Agregasi jumlah aset per kolom (`id_kampus`/`id_gedung`/`id_ruangan`) dari tabel `asets`.
     *
     * Endpoint list Simantap (/kampus, /gedung, /ruangan) tidak mengembalikan total aset,
     * sedangkan endpoint detail hanya memberi jumlah anaknya (jumlah_gedung/jumlah_lantai).
     * Nilai agregasi ini identik dengan `meta.total` pada /bmn-all/by-{kampus|gedung|ruangan}/{id}.
     * Kolom-kolom ini belum ter-indeks, jadi hasilnya di-cache sebentar.
     */
    protected function asetCountsBy(string $column): array
    {
        try {
            if (!class_exists(\App\Models\Aset::class) || !\Illuminate\Support\Facades\Schema::hasTable('asets')) {
                return [];
            }

            return $this->swr("aset_counts_by.{$column}", 10, 5, function () use ($column) {
                return \App\Models\Aset::query()
                    ->select($column, \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
                    ->whereNotNull($column)
                    ->groupBy($column)
                    ->pluck('total', $column)
                    ->map(fn ($total) => (int) $total)
                    ->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
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
        $response = $this->apiService->makeRequest('GET', "kampus/by-satker/{$satkerId}");

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

        return view('Assets.aset', [
            'datas' => $datas,
            'title' => 'Kampus by Satker',
            'level' => 'kampus',
        ]);
    }

    public function allGedung(Request $request)
    {
        $params = $request->only(['per_page']);
        $response = $this->apiService->makeRequest('GET', 'gedung', $params);

        return view('Assets.aset', [
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

            $gedungList = $response['data'];
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

        $gedungCounts = $this->asetCountsBy('id_gedung');

        $datas = collect($gedungList)->map(function ($gedung) use ($gedungCounts) {
            $id = $gedung['id_gedung'] ?? $gedung['id'] ?? null;
            $totalAset = (int) ($gedung['total_aset'] ?? $gedungCounts[$id] ?? 0);

            return [
                'id' => $id,
                'title' => $gedung['nama_gedung'] ?? 'Nama Gedung Tidak Diketahui',
                // API /kampus/{id} tidak mengembalikan total BMN per gedung → agregasi tabel `asets`.
                'count' => $totalAset > 0
                    ? number_format($totalAset, 0, ',', '.') . ' Unit BMN'
                    : null,
                'icon' => 'map',
                'updated' => $gedung['updated_at'] ?? now(),
            ];
        });


        return view('Assets.aset', compact('datas'), [
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

        return view('Assets.aset', [
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
        $ruanganCounts = $this->asetCountsBy('id_ruangan');

        $datas = collect($gedungList)->map(function ($ruangan) use ($ruanganCounts) {
            $id = $ruangan['id_ruangan'] ?? null;
            $totalAset = (int) ($ruangan['total_aset'] ?? $ruanganCounts[$id] ?? 0);

            return [
                'id' => $id,
                'title' => $ruangan['nama_ruangan'] ?? 'Nama Ruangan',
                // API /ruangan/by-gedung/{id} tidak mengembalikan total BMN → agregasi tabel `asets`.
                'count' => $totalAset > 0
                    ? number_format($totalAset, 0, ',', '.') . ' Unit BMN'
                    : null,
                'icon' => 'door',
                'updated' => $ruangan['updated_at'] ?? now(),
            ];
        });

        $namaGedung = ucwords(strtolower(trim(str_replace(['GEDUNG-', '-'], [' ', ' '], $gedungId))));
        if (empty($namaGedung)) {
            $namaGedung = 'Gedung';
        }

        return view('Assets.aset', compact('datas'), [
            'title' => 'Ruangan - ' . $namaGedung,
            'level' => 'ruangan',
            'parentTitle' => $namaGedung,
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

        return view('Assets.aset', [
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
        $response = $this->apiService->makeRequest('GET', "bmn/by-kampus/{$kampusId}");
        return view('aset-detail', [
            'data' => $response['data'] ?? [],
            'title' => 'Detail BMN Disewakan (kampus)'
        ]);
    }

    public function bmnDisewakanByGedung($gedungId)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/by-gedung/{$gedungId}");

        return view('Assets.aset-bmn', [
            'bmnList' => collect($response['data'] ?? []),
            'title' => 'BMN Disewakan (Gedung)',
            'level' => 'bmn'
        ]);
    }

    public function bmnDisewakanByRuangan($ruanganId)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/by-ruangan/{$ruanganId}");

        return view('Assets.aset-bmn', [
            'bmnList' => collect($response['data'] ?? []),
            'title' => 'BMN Disewakan (Ruangan)',
            'level' => 'bmn'
        ]);
    }

    public function bmnDisewakanByJenis($jenis)
    {
        $response = $this->apiService->makeRequest('GET', "bmn/by-jenis/{$jenis}");

        return view('Assets.aset-bmn', [
            'bmnList' => collect($response['data'] ?? []),
            'title' => 'BMN Disewakan (Jenis)',
            'level' => 'bmn'
        ]);
    }

    public function bmnAll(Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'id_kampus', 'id_gedung', 'id_ruangan', 'id_jenis_barang', 'kondisi', 'search', 'all']);
        $response = $this->apiService->makeRequest('GET', 'bmn-all', $params);

        return view('Assets.aset-bmn', [
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
        $params['per_page'] = $params['per_page'] ?? config('aset.per_page.semua', 1000);

        $response = $this->apiService->makeRequest('GET', "bmn-all/by-ruangan/{$ruanganId}", $params);
        $rawList = $response['data']['data'] ?? $response['data'] ?? [];
        $bmnList = collect($rawList);

        $namaRuangan = null;
        if ($bmnList->isNotEmpty()) {
            $first = $bmnList->first();
            $lok = is_array($first) ? ($first['lokasi_lengkap'] ?? '') : ($first->lokasi_lengkap ?? '');
            if (!empty($lok)) {
                // Nama ruangan diambil dari segmen keempat lokasi
                // (kampus - gedung - lantai - ruangan); nama yang tidak berawalan
                // "Ruang" (mis. "Bed Room 3.5") tetap dipakai apa adanya.
                $segmen = array_map('trim', explode(' - ', $lok));
                $namaRuangan = trim((string) ($segmen[3] ?? '')) ?: null;
            }
        }

        if (empty($namaRuangan)) {
            $cleanSlug = preg_replace('/^RUANG-/i', '', $ruanganId);
            $namaRuangan = ucwords(strtolower(trim(str_replace('-', ' ', $cleanSlug))));
        }
        if (empty($namaRuangan)) {
            $namaRuangan = '-';
        }

        return view('Assets.aset-bmn', [
            'title' => 'Inventaris ' . $namaRuangan,
            'ruanganName' => $namaRuangan,
            'ruanganId' => $ruanganId,
            'level' => 'bmn',
            'bmnList' => $bmnList
        ]);
    }

    public function bmnAllByKampus($kampusId, Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'kondisi', 'all']);
        $response = $this->apiService->makeRequest('GET', "bmn-all/by-kampus/{$kampusId}", $params);
        return view('Assets.aset-bmn', ['bmnList' => collect($response['data']['data'] ?? $response['data'] ?? []), 'title' => 'BMN by Kampus', 'level' => 'bmn']);
    }

    public function bmnAllByGedung($gedungId, Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'kondisi', 'all']);
        $response = $this->apiService->makeRequest('GET', "bmn-all/by-gedung/{$gedungId}", $params);
        return view('Assets.aset-bmn', ['bmnList' => collect($response['data']['data'] ?? $response['data'] ?? []), 'title' => 'BMN by Gedung', 'level' => 'bmn']);
    }

    public function bmnAllByJenisBarang($jenisBarangId, Request $request)
    {
        $params = $request->only(['per_page', 'status_sewa', 'kondisi', 'all']);
        $response = $this->apiService->makeRequest('GET', "bmn-all/by-jenis-barang/{$jenisBarangId}", $params);
        return view('Assets.aset-bmn', ['bmnList' => collect($response['data']['data'] ?? $response['data'] ?? []), 'title' => 'BMN by Jenis Barang', 'level' => 'bmn']);
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

    /**
     * Mulai penyegaran data aset (BMN SIMANTAP) dari tombol di halaman aset.
     *
     * Penarikan berjalan di proses terpisah memakai Chromium lokal di host aplikasi;
     * halaman memantau hasilnya lewat syncStatus(). Pengguna tidak mengisi apa pun.
     */
    public function syncData(AssetSyncLauncher $launcher): JsonResponse
    {
        $key = 'aset_sync';
        $cooldownKey = 'aset_sync_cooldown';
        $status = Cache::get($key);

        // Status "jalan" yang terlalu lama dianggap basi (mis. proses penarik mati),
        // supaya tombol tetap bisa dipakai lagi.
        $masihBerjalan = is_array($status)
            && ($status['status'] ?? '') === 'jalan'
            && isset($status['time'])
            && Carbon::parse($status['time'])->addMinutes(15)->greaterThanOrEqualTo(now());

        if ($masihBerjalan) {
            return response()->json([
                'success' => true,
                'status' => 'jalan',
                'message' => 'Penyegaran data aset sedang berjalan.',
            ]);
        }

        if (!$launcher->isAvailable()) {
            Cache::put($key, [
                'status' => 'gagal',
                'message' => 'Host aplikasi tidak memiliki Edge/Chrome.',
                'time' => now()->toDateTimeString(),
            ], now()->addMinutes(15));

            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'message' => 'Penarikan otomatis tidak tersedia di server ini (butuh Edge/Chrome di host aplikasi).',
            ], 503);
        }

        // Jeda 3 menit: mencegah peluncuran berulang akibat tombol ditekan berkali-kali.
        if (Cache::has($cooldownKey)) {
            return response()->json([
                'success' => true,
                'status' => 'diam',
                'message' => 'Data aset baru saja disegarkan.',
            ]);
        }

        // Satu klik menyegarkan beberapa halaman BMN; jumlahnya dibatasi agar sumber daya
        // host tetap terkendali. Sisa halaman dilanjutkan pada penekanan berikutnya.
        $maxPages = max(1, (int) config('aset.tarik.max_pages', 5));

        Cache::put($cooldownKey, true, now()->addMinutes(3));
        Cache::put($key, [
            'status' => 'jalan',
            'message' => 'Menyegarkan data aset BMN dari SIMANTAP...',
            'time' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        if (!$launcher->sync($maxPages, $key)) {
            Cache::put($key, [
                'status' => 'gagal',
                'message' => 'Gagal menjalankan penarik data aset.',
                'time' => now()->toDateTimeString(),
            ], now()->addMinutes(15));

            return response()->json([
                'success' => false,
                'status' => 'gagal',
                'message' => 'Gagal menjalankan penarik data aset.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'status' => 'jalan',
            'message' => 'Penyegaran data aset dimulai.',
        ], 202);
    }

    /**
     * Status penyegaran data aset, dipantau oleh tombol di halaman aset.
     */
    public function syncStatus(): JsonResponse
    {
        $status = Cache::get('aset_sync');

        return response()->json([
            'success' => true,
            'status' => is_array($status) ? ($status['status'] ?? 'belum') : 'belum',
            'has_data' => is_array($status) ? ($status['has_data'] ?? null) : null,
            'time' => is_array($status) ? ($status['time'] ?? null) : null,
            'message' => is_array($status) ? ($status['message'] ?? '') : '',
            'count' => is_array($status) ? ($status['count'] ?? null) : null,
            'page' => is_array($status) ? ($status['page'] ?? null) : null,
            'total_db' => is_array($status) ? ($status['total_db'] ?? null) : null,
        ]);
    }

}
