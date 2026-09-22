<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Log;

/**
 * Penjalan Chromium headless untuk memanggil API internal kampus.
 *
 * Hanya klien berbasis Chromium (Edge/Chrome) yang lolos Cloudflare dari server, sehingga
 * beberapa alur (login token SIPP dan penarikan data) memakai Chromium headless untuk
 * menjalankan fetch di dalam halaman HTML sementara.
 *
 * Setiap sesi memakai `--user-data-dir` sementara supaya tidak bertabrakan dengan Edge/Chrome
 * yang sedang dibuka pengguna: bila profil bawaan dipakai, Chromium bisa menyerahkan proses
 * ke instansi yang sudah berjalan dan sesi headless tidak pernah selesai.
 */
class ChromiumFetcher
{
    /**
     * Biner Chromium yang tersedia di host ini.
     */
    public function browser(): ?string
    {
        $candidates = [
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            (string) getenv('LOCALAPPDATA') . '\\Google\\Chrome\\Application\\chrome.exe',
            '/usr/bin/microsoft-edge',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        ];

        foreach ($candidates as $browserPath) {
            if (is_string($browserPath) && $browserPath !== '' && is_file($browserPath)) {
                return $browserPath;
            }
        }

        return null;
    }

    /**
     * Apakah Chromium tersedia di host ini.
     */
    public function isAvailable(): bool
    {
        return $this->browser() !== null;
    }

    /**
     * Jalankan halaman HTML sementara di Chromium dan kembalikan dump DOM-nya.
     *
     * @return string|null null bila browser tidak tersedia atau sesi gagal dijalankan
     */
    public function fetch(string $html, string $userAgent, int $timeoutMs = 120000): ?string
    {
        $browser = $this->browser();

        if ($browser === null) {
            return null;
        }

        $directory = storage_path('app/chromium-sessions');
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        // Berkas sesi memuat token/kredensial, jadi sisa sesi lama (mis. proses yang mati
        // sebelum sempat membersihkan) dibuang lebih dulu.
        $this->cleanupStale($directory, 300);

        $htmlFile = $directory . '/session-' . uniqid() . '.html';
        $profileDir = $directory . '/profile-' . uniqid();

        @mkdir($profileDir, 0777, true);
        file_put_contents($htmlFile, $html);

        $command = [
            $browser,
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-extensions',
            '--user-agent=' . $userAgent,
            '--user-data-dir=' . $profileDir,
            '--virtual-time-budget=' . $timeoutMs,
            '--dump-dom',
            'file:///' . str_replace('\\', '/', $htmlFile),
        ];

        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            Log::warning('Chromium tidak dapat dijalankan.');

            return null;
        }

        $dump = (string) stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        proc_close($process);

        $this->deleteFileSecurely($htmlFile);
        $this->deleteDirectory($profileDir);

        return $dump === '' ? null : $dump;
    }

    /**
     * Hapus sisa berkas sesi dan profil Chromium yang lebih tua dari $maxAgeSeconds.
     */
    private function cleanupStale(string $directory, int $maxAgeSeconds): void
    {
        foreach (glob($directory . '/session-*.html') ?: [] as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $maxAgeSeconds) {
                $this->deleteFileSecurely($file);
            }
        }

        foreach (glob($directory . '/profile-*') ?: [] as $subDirectory) {
            if (is_dir($subDirectory) && (time() - filemtime($subDirectory)) > $maxAgeSeconds) {
                $this->deleteDirectory($subDirectory);
            }
        }
    }

    /**
     * Kosongkan isi berkas sebelum dihapus agar tidak mudah dipulihkan.
     */
    private function deleteFileSecurely(string $file): void
    {
        if (is_file($file)) {
            @file_put_contents($file, '');
        }

        @unlink($file);
    }

    /**
     * Ambil isi elemen `<pre id="result">` dari dump DOM Chromium.
     */
    public function fetchResult(string $dump, string $elementId = 'result'): string
    {
        if (preg_match('#<pre id="' . preg_quote($elementId, '#') . '">(.*?)</pre>#s', $dump, $matches) !== 1) {
            return '';
        }

        return trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5));
    }

    /**
     * Hapus folder profil Chromium sementara.
     */
    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $content = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($content as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($directory);
    }
}
