<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Cache;

/**
 * Pelaporan hasil penarikan dari penarik (skrip maupun perintah artisan) ke cache.
 *
 * Payload-nya sengaja berbentuk sama dengan yang dibaca SourceRevalidator::lastPull(),
 * supaya panel kesegaran data bisa menampilkan hasil sungguhan — selesai/gagal, jumlah
 * baris, dan waktunya — bukan sekadar dugaan dari waktu tombol ditekan.
 *
 * Umur simpannya dibuat lebih panjang daripada catatan pemicu (7 hari), karena panel
 * memakainya sebagai riwayat "terakhir berhasil ditarik".
 */
class PullStatusReporter
{
    /**
     * Tulis status satu penarikan. Tidak melakukan apa pun bila kuncinya kosong.
     */
    public static function laporkan(string $kunci, string $status, string $pesan, int $jumlah = 0): void
    {
        if (trim($kunci) === '') {
            return;
        }

        Cache::put($kunci, [
            'status' => $status,
            'message' => mb_substr($pesan, 0, 250),
            'has_data' => $jumlah > 0,
            'count' => $jumlah,
            'time' => now()->toDateTimeString(),
        ], now()->addDays(7));
    }
}
