<?php

namespace App\Console\Commands;

use App\Models\Mahasiswa;
use App\Services\Sync\MahasiswaSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:mahasiswa {--angkatan= : Filter sinkronisasi khusus angkatan tertentu}')]
#[Description('Sinkronisasi data biodata individu mahasiswa dari API SIAKANG ke database')]
class SyncMahasiswaCommand extends Command
{
    public function handle(MahasiswaSyncService $sync): int
    {
        $angkatan = $this->option('angkatan');
        $infoTeks = $angkatan ? "Angkatan {$angkatan}" : "Semua Angkatan";

        $this->info("🚀 Mulai sinkronisasi biodata mahasiswa ({$infoTeks})...");
        $this->newLine();

        $page = 1;
        $limit = 100;
        $totalTersinkron = 0;

        while (true) {
            $parameter = [
                'page' => $page,
                'limit' => $limit,
                'per_page' => $limit,
                'offset' => ($page - 1) * $limit,
            ];


            if ($angkatan) {
                $parameter['angkatan'] = $angkatan;
            }

            $result = $sync->sync($parameter);

            if (!$result['status']) {
                if ($result['message'] === 'Data mahasiswa tidak tersedia' || ($result['received'] ?? 0) === 0) {
                    break;
                }

                $this->error("❌ Error di Page {$page}: " . $result['message']);
                return self::FAILURE;
            }

            $received = $result['received'] ?? 0;
            $this->info("✓ Page {$page} : {$received} data berhasil diproses");
            $totalTersinkron += $received;


            if ($received < $limit || $received === 0) {
                break;
            }

            $page++;
        }

        $this->newLine();
        $this->info("🎉 Sinkronisasi dari API selesai! Total diproses: {$totalTersinkron}");

        $totalDB = Mahasiswa::count();
        $this->info("📊 Total seluruh data di tabel Mahasiswa saat ini: {$totalDB}");

        return self::SUCCESS;
    }
}
