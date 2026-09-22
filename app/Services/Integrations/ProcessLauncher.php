<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Log;

/**
 * Peluncur proses penarikan data di latar belakang.
 *
 * Penarikan memakai Chromium lokal (satu-satunya klien yang lolos Cloudflare dari server)
 * dan bisa makan puluhan detik, jadi tidak boleh menahan permintaan web. Prosesnya
 * diluncurkan terpisah lewat perintah artisan, sementara pemanggil memantau status di cache.
 */
abstract class ProcessLauncher
{
    /**
     * Nama penarik untuk pesan log, mis. "penarik dosen".
     */
    abstract protected function label(): string;

    /**
     * Lokasi Edge/Chrome yang mungkin tersedia di host aplikasi.
     *
     * @return array<int, string>
     */
    public function browserCandidates(): array
    {
        return [
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
    }

    /**
     * Biner browser yang akan dipakai penarikan, null bila host tidak punya Chromium.
     */
    public function browser(): ?string
    {
        foreach ($this->browserCandidates() as $browserPath) {
            if (is_string($browserPath) && $browserPath !== '' && is_file($browserPath)) {
                return $browserPath;
            }
        }

        return null;
    }

    /**
     * Apakah penarikan otomatis bisa dijalankan di host ini.
     */
    public function isAvailable(): bool
    {
        return $this->browser() !== null;
    }

    /**
     * Jalankan perintah artisan di proses terpisah (tanpa menunggu selesai).
     *
     * Di Windows peluncuran memakai berkas .cmd yang dijalankan lewat PowerShell
     * `Start-Process`; cara ini penting karena proses anak yang dipegang request PHP akan
     * ikut dimatikan saat request selesai (`proc_open`, `start /B`).
     *
     * @param  array<int, string>  $arguments  argumen artisan, mis. ['sync:dosen', $nip]
     * @param  string  $logFile  nama berkas log di storage/logs
     * @param  string  $workDirectory  nama folder kerja di storage/app
     * @param  array<string, mixed>  $context  keterangan tambahan untuk log
     */
    protected function luncurkan(array $arguments, string $logFile, string $workDirectory, array $context = []): bool
    {
        if (!$this->isAvailable()) {
            Log::warning('Penarik tidak tersedia: host tanpa Edge/Chrome.', ['penarik' => $this->label()]);

            return false;
        }

        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(base_path('artisan'))
            . ' ' . implode(' ', array_map('escapeshellarg', $arguments));

        $logFile = storage_path('logs/' . $logFile);
        $workDirectory = storage_path('app/' . $workDirectory);

        if (!is_dir($workDirectory)) {
            @mkdir($workDirectory, 0777, true);
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $cmdFile = $workDirectory . '/jalan-' . uniqid() . '.cmd';

                file_put_contents($cmdFile, implode(PHP_EOL, [
                    '@echo off',
                    $command . ' >> ' . escapeshellarg($logFile) . ' 2>&1',
                    'del "%~f0"',
                    '',
                ]));

                exec('powershell -NoProfile -Command "Start-Process -FilePath \'' . $cmdFile . '\' -WindowStyle Hidden"');
            } else {
                exec($command . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &');
            }
        } catch (\Throwable $e) {
            Log::error('Gagal meluncurkan ' . $this->label() . ': ' . $e->getMessage(), $context);

            return false;
        }

        Log::info(ucfirst($this->label()) . ' diluncurkan.', $context);

        return true;
    }
}
