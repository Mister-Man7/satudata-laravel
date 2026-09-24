<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Penarikan data terjadwal (SWR: database/cache dulu, API untuk revalidate)
|--------------------------------------------------------------------------
|
| Penarik berbasis Chromium lokal (Edge/Chrome di host aplikasi) karena
| panggilan HTTP langsung dari PHP selalu ditantang Cloudflare. Dengan jadwal
| ini, data tetap segar walau tidak ada yang membuka halaman.
|
| Aktifkan sekali di host: php artisan schedule:work (atau cron tiap menit
| menjalankan: php artisan schedule:run).
|
*/

Schedule::command('sync:aset')
    ->hourly()
    ->withoutOverlapping()
    ->description('Tarik data aset BMN dari SIMANTAP');

Schedule::command('sync:dosen')
    ->everySixHours()
    ->withoutOverlapping()
    ->description('Tarik portofolio & penjadwalan dosen');

// Penjadwalan SIAKANG per NIP dijalankan lewat skrip Chromium.
Schedule::exec(PHP_BINARY . ' ' . base_path('scripts/sync-penjadwalan.php') . ' --limit=25 --delay=4000')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->description('Tarik penjadwalan dosen dari SIAKANG');

Schedule::command('sync:pegawai')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->description('Tarik data pegawai dari SIMPEG');

