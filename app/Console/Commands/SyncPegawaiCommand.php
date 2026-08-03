<?php

namespace App\Console\Commands;

use App\Services\Sync\PegawaiSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:pegawai')]
#[Description('Sinkronisasi data pegawai dari API ke SQLite')]
class SyncPegawaiCommand extends Command
{

    public function handle(PegawaiSyncService $sync): int
    {
        $this->info('Syncing Pegawai...');
        
        $result = $sync->sync([
            'limit' => 100000,
            'per_page' => 100000,
            'all' => 'true',
            'page' => 1
        ]);

        if (!$result['status']) {
            $this->error($result['message']);
            return self::FAILURE;
        }

        $this->info("✓ Berhasil menarik {$result['received']} data pegawai dari API!");
        $this->info("✓ Tersinkron ke database SQLite: {$result['total']} data.");

        return self::SUCCESS;
    }
}
