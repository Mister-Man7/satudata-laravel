<?php

namespace App\Console\Commands;

use App\Models\Aset;
use App\Services\Sync\AsetSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:aset {--page=1 : Halaman awal sinkronisasi} {--limit=100 : Jumlah data per halaman}')]
#[Description('Sinkronisasi seluruh data BMN (Aset) dari API SIMANTAP')]
class SyncAsetCommand extends Command
{

    public function handle(AsetSyncService $service): int
    {
        $this->info('🚀 Memulai sinkronisasi data Aset BMN dari SIMANTAP...');

        $page = (int)$this->option('page');
        $limit = (int)$this->option('limit');
        $totalBerhasil = 0;

        while (true) {
            $this->info("⏳ Sedang mengambil Page {$page}...");

            try {

                $result = $service->sync([
                    'page' => $page,
                    'per_page' => $limit,
                ]);

                if (!$result['status']) {
                    $this->error("❌ Gagal di Page {$page}: " . ($result['message'] ?? 'Data tidak tersedia'));
                    break;
                }

                $received = $result['received'] ?? 0;
                $totalBerhasil += $received;

                $this->info("✓ Page {$page} selesai: {$received} data berhasil di-upsert.");

                $meta = $result['meta'] ?? [];
                $lastPage = (int)($meta['last_page'] ?? 1);

                if ($page >= $lastPage || $received === 0) {
                    $this->info("🏁 Sudah mencapai halaman terakhir ({$lastPage}).");
                    break;
                }

                $page++;

                usleep(500000);

            } catch (\Exception $e) {
                $this->error("💥 Terjadi kesalahan fatal pada Page {$page}: " . $e->getMessage());
                break;
            }
        }

        $this->newLine();
        $this->info("🎉 Sinkronisasi Aset selesai! Total data diproses pada sesi ini: {$totalBerhasil}");

        $totalDb = Aset::count();
        $this->info("📊 Total seluruh data di tabel Aset saat ini: {$totalDb}");

        return Command::SUCCESS;
    }
}
