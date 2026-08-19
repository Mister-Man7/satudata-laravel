<?php

use App\Http\Controllers\Academic\AkademikController;
use App\Http\Controllers\Academic\MonitoringPerkuliahanController;
use App\Http\Controllers\Assets\AsetController;
use App\Http\Controllers\HR\PegawaiController;
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
});

Route::get('/pegawai', [PegawaiController::class, 'index'])
    ->name('pegawai');

Route::get('/infrastruktur', function () {
    return view('Integration.infrastruktur', ['title' => 'Infrastruktur']);
});

Route::post('/chat', [TirtaAgentController::class, 'chat'])
    ->name('tirta-agent.chat');

Route::get('/sipp/publikasi-pegawai', [SIPPController::class, 'getPublikasiByNip']);
