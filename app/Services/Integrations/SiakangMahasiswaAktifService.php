<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SiakangMahasiswaAktifService extends SiakangApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.mahasiswa_aktif';
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
     * Ambil data mahasiswa aktif.
     *
     * SWR dual-key: data (TTL 6 jam) + freshness flag (TTL 20 menit).
     * DB lokal dipakai saat cache miss; API selalu di-refresh di background.
     */
    public function getData(array $params = []): ApiResponse
    {
        $cacheKey     = 'siakang.mahasiswa_aktif.' . md5(json_encode($params));
        $staleFlagKey = $cacheKey . '.fresh';

        // Cache hit — return data, refresh jika stale
        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);

            // Fresh flag expired — picu refresh background
            if (!Cache::has($staleFlagKey)) {
                $this->deferApiRefresh($cacheKey, $staleFlagKey, $params);
            }

            return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa aktif', data: $data);
        }

        // Cache miss — ambil DB lokal, defer refresh API
        $dbData = $this->hasilFallbackData($params);
        if (!empty($dbData['detail_per_prodi'])) {
            Cache::put($cacheKey, $dbData, now()->addHours(6));

            // Tahan penarikan API selama 20 menit supaya jalur ini tidak menjadi
            // badai penarikan (satu halaman Akademik memakai sampai empat semester).
            // Setelah penanda ini kedaluwarsa, permintaan berikutnya menjadwalkan
            // satu penarikan lewat cabang cache.
            Cache::put($staleFlagKey, true, now()->addMinutes(20));
            return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa aktif', data: $dbData);
        }

        // Cold start — DB juga kosong. API tidak pernah ditunggu di dalam request:
        // penarikan dijadwalkan setelah response dikirim, lalu request ini memakai
        // data yang tersedia (boleh kosong). Sebelumnya panggilan ini menunggu
        // sampai timeout 15 detik dan, karena satu halaman memanggilnya untuk
        // beberapa semester, totalnya melewati batas 30 detik PHP sehingga halaman
        // mati dengan fatal error.
        $this->deferApiRefresh($cacheKey, $staleFlagKey, $params);

        return new ApiResponse(success: true, status: 200, message: 'Data mahasiswa aktif', data: $dbData);
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
            app(\App\Services\Integrations\SourceRevalidator::class)->trigger('siakang.mahasiswa_aktif', [
                'semester' => (string) ($params['semester'] ?? ''),
            ]);

            // Reset fresh flag terlepas dari hasil penarikan, berlaku sesuai config
            Cache::put($staleFlagKey, true, now()->addMinutes((int) config('satudata.swr.fresh_minutes', 20)));
        });
    }
    private function hasilFallbackData(array $params = []): array
    {
        $semester = (string)($params['semester'] ?? '');
        $tahun = (int)substr($semester, 0, 4);
        $digit = substr($semester, -1);

        // Utamakan angka hasil tarikan API yang tersimpan di kolom
        // (tabel siakang_semester_stats diisi scripts/sync-siakang-stat.php).
        $dariApi = \Illuminate\Support\Facades\DB::table('siakang_semester_stats')
            ->where('semester', $semester)
            ->where('jenis', 'aktif')
            ->get();

        if (!$dariApi->isEmpty()) {
            return [
                'total_mahasiswa_aktif' => (int) $dariApi->sum('jumlah'),
                'total_laki_laki' => (int) $dariApi->sum('laki_laki'),
                'total_perempuan' => (int) $dariApi->sum('perempuan'),
                'detail_per_fakultas' => $dariApi->groupBy('fakultas')->map(fn ($items, $namaFakultas) => [
                    'nama_fakultas' => $namaFakultas !== '' ? $namaFakultas : '-',
                    'jumlah_mahasiswa_aktif' => (int) $items->sum('jumlah'),
                    'jumlah_laki_laki' => (int) $items->sum('laki_laki'),
                    'jumlah_perempuan' => (int) $items->sum('perempuan'),
                ])->values()->all(),
                'detail_per_prodi' => $dariApi->map(fn ($baris) => [
                    'prodi_id' => (string) $baris->prodi_id,
                    'kode_prodi' => (string) $baris->kode_prodi,
                    'nama_prodi' => (string) $baris->nama_prodi,
                    'jenjang' => (string) $baris->jenjang,
                    'fakultas' => $baris->fakultas,
                    'jumlah_mahasiswa_aktif' => (int) $baris->jumlah,
                    'jumlah_laki_laki' => (int) $baris->laki_laki,
                    'jumlah_perempuan' => (int) $baris->perempuan,
                ])->all(),
            ];
        }

        // Belum ada hasil tarikan API untuk semester ini: hitungan di bawah hanya
        // memakai kolom tabel `mahasiswas` (tanpa faktor pengali).



        try {
            if (class_exists(\App\Models\Mahasiswa::class) && \App\Models\Mahasiswa::count() > 0) {
                $counts = \Illuminate\Support\Facades\DB::table('mahasiswas')
                    ->select('prodi_id', 'jenis_kelamin_string', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                    ->groupBy('prodi_id', 'jenis_kelamin_string')
                    ->get();

                $prodiGenderMap = [];
                $totalLakiRaw = 0;
                $totalPerempuanRaw = 0;

                foreach ($counts as $row) {
                    $pid = (string)$row->prodi_id;
                    $jk = (string)$row->jenis_kelamin_string;
                    $tot = (int)$row->total;

                    if (!isset($prodiGenderMap[$pid])) {
                        $prodiGenderMap[$pid] = ['laki' => 0, 'perempuan' => 0, 'total' => 0];
                    }

                    if (stripos($jk, 'Laki') !== false) {
                        $prodiGenderMap[$pid]['laki'] += $tot;
                        $totalLakiRaw += $tot;
                    } else {
                        $prodiGenderMap[$pid]['perempuan'] += $tot;
                        $totalPerempuanRaw += $tot;
                    }
                    $prodiGenderMap[$pid]['total'] += $tot;
                }

                $totalRaw = $totalLakiRaw + $totalPerempuanRaw;
                $totalAktif = $totalRaw;
                $totalLaki = $totalLakiRaw;
                $totalPerempuan = $totalPerempuanRaw;

                $prodis = \App\Models\Prodi::all()->keyBy('id');

                $perProdi = [];
                foreach ($prodiGenderMap as $pid => $gData) {
                    $p = $prodis->get($pid);
                    if (!$p) continue;

                    $jml = (int) $gData['total'];
                    $jmlLaki = (int) $gData['laki'];
                    $jmlPerempuan = max(0, $jml - $jmlLaki);

                    $namaProdi = strtolower($p->nama_prodi);
                    $namaFak = 'Fakultas Teknik';
                    if (str_contains($namaProdi, 'hukum')) $namaFak = 'Fakultas Hukum';
                    elseif (str_contains($namaProdi, 'ekonomi') || str_contains($namaProdi, 'manajemen') || str_contains($namaProdi, 'akuntansi') || str_contains($namaProdi, 'pajak') || str_contains($namaProdi, 'bisnis')) $namaFak = 'Fakultas Ekonomi dan Bisnis';
                    elseif (str_contains($namaProdi, 'pendidikan') || str_contains($namaProdi, 'fkip')) $namaFak = 'Fakultas Keguruan dan Ilmu Pendidikan';
                    elseif (str_contains($namaProdi, 'komunikasi') || str_contains($namaProdi, 'politik') || str_contains($namaProdi, 'sosial') || str_contains($namaProdi, 'pemerintahan') || str_contains($namaProdi, 'administrasi publik')) $namaFak = 'Fakultas Ilmu Sosial dan Ilmu Politik';
                    elseif (str_contains($namaProdi, 'tani') || str_contains($namaProdi, 'agri') || str_contains($namaProdi, 'pangan') || str_contains($namaProdi, 'ikan') || str_contains($namaProdi, 'ternak')) $namaFak = 'Fakultas Pertanian';
                    elseif (str_contains($namaProdi, 'dokter') || str_contains($namaProdi, 'sehat') || str_contains($namaProdi, 'gizi') || str_contains($namaProdi, 'farmasi') || str_contains($namaProdi, 'rawat')) $namaFak = 'Fakultas Kedokteran dan Ilmu Kesehatan';
                    elseif (in_array(strtolower($p->jenjang), ['s2', 's3', 'sp-1']) || str_contains($namaProdi, 'pasca')) $namaFak = 'Pascasarjana';

                    $perProdi[] = [
                        'prodi_id' => (string)$p->id,
                        'kode_prodi' => (string)$p->kode_prodi,
                        'nama_prodi' => (string)$p->nama_prodi,
                        'jenjang' => strtoupper((string)$p->jenjang),
                        'fakultas' => $namaFak,
                        'jumlah_mahasiswa_aktif' => $jml,
                        'jumlah_laki_laki' => $jmlLaki,
                        'jumlah_perempuan' => $jmlPerempuan,
                    ];
                }

                $fakultasGrouped = collect($perProdi)->groupBy('fakultas')->map(function ($items, $namaFak) {
                    return [
                        'nama_fakultas' => $namaFak,
                        'jumlah_mahasiswa_aktif' => (int)$items->sum('jumlah_mahasiswa_aktif'),
                        'jumlah_laki_laki' => (int)$items->sum('jumlah_laki_laki'),
                        'jumlah_perempuan' => (int)$items->sum('jumlah_perempuan'),
                    ];
                })->values()->toArray();

                return [
                    'total_mahasiswa_aktif' => $totalAktif,
                    'total_laki_laki' => $totalLaki,
                    'total_perempuan' => $totalPerempuan,
                    'detail_per_fakultas' => $fakultasGrouped,
                    'detail_per_prodi' => $perProdi,
                ];
            }
        } catch (\Throwable $e) {
            // DB exception ignored
        }


        return [
            'total_mahasiswa_aktif' => 0,
            'total_laki_laki' => 0,
            'total_perempuan' => 0,
            'detail_per_fakultas' => [],
            'detail_per_prodi' => [],
        ];
    }


}