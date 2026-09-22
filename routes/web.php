<?php

use App\Http\Controllers\Academic\AkademikController;
use App\Http\Controllers\Academic\MonitoringPerkuliahanController;
use App\Http\Controllers\Assets\AsetController;
use App\Http\Controllers\Pegawai\PegawaiController;
use App\Http\Controllers\Integration\SIPPController;
use App\Http\Controllers\Integration\TirtaAgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('Home.home', ['title' => 'Dashboard']);
});

Route::get('/akademik', [AkademikController::class, 'index'])
    ->name('akademik');

Route::get('/akademik/mahasiswa-lulus', [AkademikController::class, 'mahasiswaLulus'])
    ->name('akademik.mahasiswa-lulus');

Route::get('/akademik/perkuliahan', [MonitoringPerkuliahanController::class, 'index'])
    ->name('akademik.perkuliahan');
Route::get('/akademik/perkuliahan/detail/{unitKode}', [MonitoringPerkuliahanController::class, 'detail'])
    ->name('akademik.perkuliahan.detail');
Route::get('/akademik/perkuliahan/dosen/{nip}', [\App\Http\Controllers\Academic\DosenProfileController::class, 'show'])
    ->name('akademik.perkuliahan.dosen');

Route::prefix('aset')->name('aset.')->group(function () {
    Route::get('/', [AsetController::class, 'index'])->name('index');
    Route::get('/kampus/{kampusId}/gedung', [AsetController::class, 'gedung'])->name('gedung');
    Route::get('/gedung/{gedungId}/ruangan', [AsetController::class, 'ruangan'])->name('ruangan');
    Route::get('/ruangan/{ruanganId}/bmn', [AsetController::class, 'bmn'])->name('bmn');

    // Penyegaran data aset dari tombol di halaman aset. Peluncuran proses penarik dibatasi
    // throttle seperti pada halaman profil dosen, sedangkan statusnya dipantau terpisah.
    Route::post('/sync-data', [AsetController::class, 'syncData'])
        ->middleware('throttle:10,1')
        ->name('sync-data');
    Route::get('/sync-status', [AsetController::class, 'syncStatus'])
        ->name('sync-status');
});

Route::get('/pegawai', [PegawaiController::class, 'index'])
    ->name('pegawai');
Route::get('/pegawai/profil-dosen', [PegawaiController::class, 'profilDosenIndex'])
    ->name('pegawai.profil-dosen');
Route::get('/pegawai/profil-dosen/{nip}', [\App\Http\Controllers\Academic\DosenProfileController::class, 'show'])
    ->name('pegawai.profil-dosen.show');
Route::get('/pegawai/profil-dosen/{nip}/sipp-metrics', [\App\Http\Controllers\Academic\DosenProfileController::class, 'sippMetrics'])
    ->name('pegawai.profil-dosen.sipp-metrics');
Route::post('/pegawai/profil-dosen/{nip}/sync-data', [\App\Http\Controllers\Academic\DosenProfileController::class, 'syncData'])
    ->middleware('throttle:10,1')
    ->name('pegawai.profil-dosen.sync-data');
Route::get('/pegawai/profil-dosen/{nip}/sync-status', [\App\Http\Controllers\Academic\DosenProfileController::class, 'syncStatus'])
    ->name('pegawai.profil-dosen.sync-status');

Route::get('/infrastruktur', function () {
    return view('Integration.infrastruktur', ['title' => 'Infrastruktur']);
});

Route::post('/chat', [TirtaAgentController::class, 'chat'])
    ->name('tirta-agent.chat');

Route::get('/sipp/publikasi-pegawai', [SIPPController::class, 'getPublikasiByNip']);
