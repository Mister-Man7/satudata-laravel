<?php

namespace App\Console\Commands;

use App\Models\Aset;
use App\Services\Sync\AsetSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('sync:aset
    {--page=1 : Halaman awal sinkronisasi}
    {--limit=100 : Jumlah data per halaman}
    {--max-page=1 : Jumlah halaman per sesi, 0 = tanpa batas}
    {--resume : Mulai dari halaman setelah posisi terakhir yang tercatat}
    {--key= : Kunci cache status yang dipantau halaman aset}')]
#[Description('Sinkronisasi data BMN (Aset) dari API SIMANTAP secara bertahap')]
class SyncAsetCommand extends Command
{
    /**
     * Posisi halaman terakhir yang sudah tersinkron, dipakai opsi --resume.
     */
    public const LAST_PAGE_CACHE_KEY = 'aset_sync_last_page';

    public function handle(AsetSyncService $service): int
    {
        $this->info('🚀 Memulai sinkronisasi data Aset BMN dari SIMANTAP...');

        $page = max(1, (int) $this->option('page'));
        $limit = max(1, (int) $this->option('limit'));
        $maxPages = max(0, (int) $this->option('max-page'));
        $statusKey = (string) ($this->option('key') ?: 'aset_sync');

        // Sinkronisasi penuh mencapai puluhan ribu baris, jadi dijalankan bertahap: opsi
        // --resume meneruskan dari halaman terakhir yang tercatat pada sesi sebelumnya.
        if ($this->option('resume')) {
            $recordedPage = (int) Cache::get(self::LAST_PAGE_CACHE_KEY, 0);

            if ($recordedPage >= 1) {
                $page = $recordedPage + 1;

                $this->info("↪️ Melanjutkan dari halaman {$page} (terakhir tercatat: {$recordedPage}).");
            }
        }

        $this->info('🚀 Memulai sinkronisasi data Aset BMN dari SIMANTAP...');

        $totalSaved = 0;
        $processed = 0;
        $lastProcessedPage = $page;
        $lastPage = null;
        $failureMessage = null;

        while (true) {
            $this->info("⏳ Sedang mengambil Page {$page}...");

            try {
                $result = $service->sync([
                    'page' => $page,
                    'per_page' => $limit,
                ]);
            } catch (\Exception $e) {
                $failureMessage = $e->getMessage();
                $this->error("💥 Terjadi kesalahan fatal pada Page {$page}: {$failureMessage}");

                break;
            }

            if (empty($result['status'])) {
                $failureMessage = (string) ($result['message'] ?? 'Data tidak tersedia');
                $this->error("❌ Gagal di Page {$page}: {$failureMessage}");

                break;
            }

            $received = (int) ($result['received'] ?? 0);
            $totalSaved += $received;
            $processed++;
            $lastProcessedPage = $page;

            $this->info("✓ Page {$page} selesai: {$received} data berhasil di-upsert.");

            $meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];
            $lastPage = (int) ($meta['last_page'] ?? $lastPage ?? 1);

            if ($page >= $lastPage || $received === 0) {
                // Satu siklus penuh selesai: posisi direset agar penarikan berikutnya
                // mulai dari halaman 1 lagi (data tetap segar dari awal).
                Cache::forget(self::LAST_PAGE_CACHE_KEY);

                $this->info("🏁 Sudah mencapai halaman terakhir ({$lastPage}).");

                break;
            }

            Cache::put(self::LAST_PAGE_CACHE_KEY, $page, now()->addDays(30));

            if ($maxPages > 0 && $processed >= $maxPages) {
                $this->info("⏸️ Batas {$maxPages} halaman per sesi tercapai; sisanya lanjut pada sesi berikutnya.");

                break;
            }

            $page++;

            usleep(300000);
        }

        $totalDb = Aset::count();

        $summary = $failureMessage !== null
            ? 'gagal di halaman ' . $page . ': ' . $failureMessage
            : ($totalSaved > 0
                ? "{$totalSaved} baris disegarkan dari {$processed} halaman"
                : 'tidak ada baris baru pada sesi ini');

        // Status dibaca halaman aset lewat tombol "Tarik data aset".
        Cache::put($statusKey, [
            'status' => $failureMessage === null ? 'selesai' : 'gagal',
            'message' => mb_substr($summary, 0, 250),
            'has_data' => $totalSaved > 0,
            'count' => $totalSaved,
            'page' => $lastProcessedPage,
            'total_db' => $totalDb,
            'time' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        $this->newLine();
        $this->info("🎉 Sinkronisasi Aset selesai! Total data diproses pada sesi ini: {$totalSaved}");
        $this->info("📊 Total seluruh data di tabel Aset saat ini: {$totalDb}");

        return $failureMessage === null ? Command::SUCCESS : Command::FAILURE;
    }
}
