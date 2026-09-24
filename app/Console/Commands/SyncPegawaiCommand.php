<?php

namespace App\Console\Commands;

use App\Services\Sync\PegawaiSyncService;
use App\Services\Sync\PullStatusReporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:pegawai {--key= : Kunci cache status yang dipantau panel kesegaran data}')]
#[Description('Sinkronisasi data pegawai dari API ke SQLite')]
class SyncPegawaiCommand extends Command
{

    public function handle(PegawaiSyncService $sync): int
    {
        $kunci = (string) ($this->option('key') ?: '');

        $this->info('Syncing Pegawai...');
        
        $result = $sync->sync([
            'limit' => 100000,
            'per_page' => 100000,
            'all' => 'true',
            'page' => 1
        ]);

        if (!$result['status']) {
            PullStatusReporter::laporkan($kunci, 'gagal', (string) $result['message']);
            $this->error($result['message']);
            return self::FAILURE;
        }

        $this->info("✓ Berhasil menarik {$result['received']} data pegawai dari API!");
        $this->info("✓ Tersinkron ke database SQLite: {$result['total']} data.");

        PullStatusReporter::laporkan(
            $kunci,
            'selesai',
            "{$result['received']} data pegawai ditarik; total di database {$result['total']}.",
            (int) $result['received'],
        );

        return self::SUCCESS;
    }
}
