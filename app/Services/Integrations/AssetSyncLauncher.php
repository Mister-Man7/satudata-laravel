<?php

namespace App\Services\Integrations;

/**
 * Peluncur penyegaran data aset (BMN SIMANTAP) di latar belakang.
 *
 * Tombol "Tarik data aset" di halaman aset tidak boleh menahan permintaan web: satu sesi
 * menempuh beberapa halaman API lewat Chromium lokal. Karena itu prosesnya dilaunch
 * terpisah lewat perintah `sync:aset --resume`, sementara halaman memantau statusnya.
 */
class AssetSyncLauncher extends ProcessLauncher
{
    /**
     * Nama penarik untuk pesan log dan pemeriksaan ketersediaan Chromium.
     */
    protected function label(): string
    {
        return 'penarik aset';
    }

    /**
     * Jalankan penyegaran beberapa halaman data BMN di proses terpisah.
     */
    public function sync(int $maxPages, string $key): bool
    {
        return $this->luncurkan(
            ['sync:aset', '--resume', '--max-page=' . max(1, $maxPages), '--key=' . $key],
            'sync-aset.log',
            'sync-aset',
            ['max_pages' => $maxPages, 'kunci' => $key]
        );
    }
}
