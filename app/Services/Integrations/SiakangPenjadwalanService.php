<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SiakangPenjadwalanService extends SiakangApiClient
{
    protected function serviceName(): string
    {
        return 'siakang.penjadwalan';
    }

    protected function config(): array
    {
        return [
            'base_url' => config('services.siakang.base_url'),
            'auth_type' => 'bearer_login',
            'token' => config('services.siakang.token'),
            'cf_clearance' => config('services.siakang.cf_clearance'),
            'connect_timeout' => 5,
            'timeout' => 15,
        ];
    }

    /**
     * Ambil data penjadwalan dosen.
     *
     * SWR dual-key: data (TTL 2 jam) + freshness flag (TTL 10 menit).
     * Data per-NIP bersifat realtime, TTL lebih pendek dibanding data agregat.
     *
     * @param  array{semester: string, nip: string}  $params
     */
    public function getData(array $params = []): ApiResponse
    {
        $semester = trim((string) ($params['semester'] ?? request()->input('semester', '')));
        $nip = trim((string) ($params['nip'] ?? ''));

        if (empty($semester) || empty($nip)) {
            return new ApiResponse(
                success: false,
                status: 422,
                message: 'Parameter semester dan nip wajib ada untuk mengambil data penjadwalan dosen.',
                data: [],
            );
        }

        $params['semester'] = $semester;
        $params['nip'] = $nip;

        $cacheKey     = 'siakang.penjadwalan.' . md5(json_encode($params));
        $staleFlagKey = $cacheKey . '.fresh';

        // 1. Cache ada — return segera, refresh jika stale
        if (Cache::has($cacheKey)) {
            $data = Cache::get($cacheKey);

            if (!Cache::has($staleFlagKey) && function_exists('defer')) {
                defer(function () use ($staleFlagKey, $nip, $semester) {
                    // PHP tidak dapat menembus Cloudflare, jadi penarikan dialihkan ke
                    // penarik Chromium lokal (lihat SourceRevalidator).
                    app(\App\Services\Integrations\SourceRevalidator::class)->trigger('siakang.penjadwalan', [
                        'nip' => $nip,
                        'semester' => $semester,
                    ]);

                    Cache::put($staleFlagKey, true, now()->addMinutes((int) config('satudata.swr.fresh_minutes', 20)));
                });
            }

            return new ApiResponse(success: true, status: 200, message: 'Data penjadwalan', data: $data);
        }

        // 2. Tidak ada cache: API tidak ditunggu di dalam request karena selalu
        // ditantang Cloudflare. Penarikan dijadwalkan, halaman memakai apa yang ada.
        app(\App\Services\Integrations\SourceRevalidator::class)->trigger('siakang.penjadwalan', [
            'nip' => $nip,
            'semester' => $semester,
        ]);

        return new ApiResponse(
            success: false,
            status: 404,
            message: 'Penjadwalan belum tersedia; penarikan lewat penarik Chromium sedang dijadwalkan.',
            data: [],
        );
    }
}