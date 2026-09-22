<?php

namespace App\Services\Integrations;

/**
 * Peluncur penarik data dosen di latar belakang.
 *
 * Tombol "Tarik data" di halaman profil tidak boleh menahan permintaan web: penarikan
 * memakai Chromium lokal (satu-satunya klien yang lolos Cloudflare dari server) dan bisa
 * makan puluhan detik. Karena itu proses dilaunch terpisah lewat perintah artisan
 * `sync:dosen`, sementara halaman memantau statusnya.
 */
class DosenSyncLauncher extends ProcessLauncher
{
    /**
     * Nama penarik untuk pesan log dan pemeriksaan ketersediaan Chromium.
     */
    protected function label(): string
    {
        return 'penarik dosen';
    }

    /**
     * Jalankan penarikan satu dosen di proses terpisah (tanpa menunggu selesai).
     *
     * Peluncuran prosesnya ditangani ProcessLauncher (berkas .cmd + PowerShell
     * `Start-Process`), karena proses anak yang dipegang request PHP akan mati bersama request.
     */
    public function sync(string $nip, string $semester, string $key): bool
    {
        return $this->luncurkan(
            ['sync:dosen', $nip, '--semester=' . $semester, '--key=' . $key],
            'sync-dosen.log',
            'sync-dosen',
            ['nip' => $nip, 'semester' => $semester, 'kunci' => $key]
        );
    }
}
