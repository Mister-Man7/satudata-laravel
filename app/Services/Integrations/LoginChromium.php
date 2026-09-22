<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Login API SIPP memakai Chromium lokal.
 *
 * Endpoint login (`/api/request-token`) menentang request PHP/cURL (Cloudflare challenge),
 * sehingga token SIPP harus diambil oleh klien berbasis Chromium — sama seperti penarikan data.
 * Dengan layanan ini token tidak perlu diperbarui manual: kredensial diambil dari .env,
 * token baru ditulis kembali ke .env, dan config runtime ikut disesuaikan.
 *
 * Nilai token tidak pernah dicetak/di-log; hanya status dan panjangnya.
 */
class LoginChromium
{
    /**
     * Biner Chromium yang tersedia di host ini.
     */
    public function browser(): ?string
    {
        return app(ChromiumFetcher::class)->browser();
    }

    /**
     * Ambil token SIPP baru lewat login di Chromium memakai kredensial .env.
     */
    public function loginSipp(): ?string
    {
        return $this->runLogin(
            'sipp',
            (string) config('services.sipp.base_url'),
            'api/request-token',
            [
                'username' => (string) config('services.sipp.username'),
                'password' => (string) config('services.sipp.password'),
            ]
        );
    }

    /**
     * Ambil token SIMANTAP (sumber data aset/BMN) lewat login di Chromium.
     *
     * Token disimpan pada cache `simantap_api_token` supaya SimantapService yang memakai
     * kunci cache yang sama ikut memanfaatkannya.
     */
    public function loginSimantap(): ?string
    {
        $token = Cache::remember('simantap_api_token', now()->addMinutes(60), function () {
            return (string) ($this->runLogin(
                'simantap',
                (string) config('services.simantap.base_url'),
                'auth/login',
                [
                    'email' => (string) config('services.simantap.email'),
                    'password' => (string) config('services.simantap.password'),
                ]
            ) ?? '');
        });

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Login generik memakai Chromium: POST kredensial ke endpoint login, kembalikan token.
     *
     * @param  array<string, string>  $credentials
     */
    private function runLogin(string $service, string $baseUrl, string $loginPath, array $credentials): ?string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $userAgent = (string) (config("services.{$service}.user_agent")
            ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

        if ($baseUrl === '' || in_array('', $credentials, true)) {
            return null;
        }

        // Base URL bisa sudah berisi /api, jadi jalur login menyesuaikan.
        $path = str_starts_with($loginPath, 'api/') ? substr($loginPath, 4) : $loginPath;
        $loginUrl = str_ends_with($baseUrl, '/api')
            ? $baseUrl . '/' . $path
            : $baseUrl . '/api/' . $path;

        $js = <<<'JS'
const urlLogin = __URL__;
const credentials = __CREDENTIALS__;

(async () => {
    let result = { status: 'ERROR', message: '', token: null };

    try {
        const res = await fetch(urlLogin, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', accept: 'application/json' },
            body: JSON.stringify(credentials),
        });

        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { data = null; }

        result = {
            status: res.status,
            message: String(data?.message ?? text).slice(0, 140),
            token: data?.data?.token ?? data?.token ?? data?.data?.access_token ?? data?.access_token ?? null,
        };
    } catch (e) {
        result = { status: 'ERROR', message: e.message, token: null };
    }

    document.getElementById('result').textContent = JSON.stringify(result);
})();
JS;

        $js = str_replace(
            ['__URL__', '__CREDENTIALS__'],
            [json_encode($loginUrl), json_encode($credentials)],
            $js
        );

        $fetcher = app(ChromiumFetcher::class);
        $dump = $fetcher->fetch(
            '<!doctype html><html><body><pre id="result">MENUNGGU</pre><script>' . $js . '</script></body></html>',
            $userAgent,
            45000
        );

        if ($dump === null) {
            return null;
        }

        $result = json_decode($fetcher->fetchResult($dump), true);
        $token = is_array($result) ? ($result['token'] ?? null) : null;

        if (!is_string($token) || $token === '') {
            Log::warning("Login {$service} lewat Chromium tidak menghasilkan token.", [
                'status' => is_array($result) ? ($result['status'] ?? null) : null,
                'message' => is_array($result) ? mb_substr((string) ($result['message'] ?? ''), 0, 120) : 'respons tidak terbaca',
            ]);

            return null;
        }

        return $token;
    }

    /**
     * Perbarui token SIPP: login lewat Chromium, tulis ke .env, sesuaikan config runtime.
     *
     * @return array{success: bool, message: string, length: int, token: ?string}
     */
    public function refreshSippToken(): array
    {
        $token = $this->loginSipp();

        if ($token === null) {
            return [
                'success' => false,
                'message' => 'Login SIPP lewat Chromium gagal; token lama tetap dipakai.',
                'length' => 0,
                'token' => null,
            ];
        }

        $saved = $this->saveTokenToEnv($token);

        // Supaya request berikutnya memakai token baru (config sudah terlanjur dimuat).
        config(['services.sipp.token' => $token]);
        Cache::forget('sipp_bearer_token');

        if (!$saved) {
            Log::warning('Token SIPP baru didapat tetapi gagal ditulis ke .env.');
        }

        return [
            'success' => true,
            'message' => $saved ? 'Token SIPP diperbarui.' : 'Token SIPP baru dipakai, tetapi gagal ditulis ke .env.',
            'length' => strlen($token),
            'token' => $token,
        ];
    }

    /**
     * Tulis token ke .env (atau berkas lain untuk pengujian) dengan mengganti baris
     * SIPP_API_TOKEN yang ada; baris lain tidak tersentuh.
     */
    public function saveTokenToEnv(string $token, ?string $file = null): bool
    {
        $file = $file ?: base_path('.env');

        if (!is_file($file)) {
            return false;
        }

        $content = (string) file_get_contents($file);
        $line = 'SIPP_API_TOKEN=' . $token;

        if (preg_match('/^SIPP_API_TOKEN=.*$/m', $content) === 1) {
            $content = (string) preg_replace('/^SIPP_API_TOKEN=.*$/m', $line, $content);
        } else {
            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }

        return file_put_contents($file, $content) !== false;
    }
}
