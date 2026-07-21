<?php

use App\Http\Controllers\AkademikController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\SIPPController;
use App\Http\Controllers\TirtaAgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['title' => 'Dashboard']);
});

Route::get('/akademik', [AkademikController::class, 'index'])
    ->name('akademik');

Route::get('/akademik/mahasiswa-lulus', [AkademikController::class, 'mahasiswaLulus'])
    ->name('akademik.mahasiswa-lulus');

Route::prefix('aset')->name('aset.')->group(function () {
    Route::get('/', [AsetController::class, 'index'])->name('index');
    Route::get('/kampus/{kampusId}/gedung', [AsetController::class, 'gedung'])->name('gedung');
    Route::get('/gedung/{gedungId}/ruangan', [AsetController::class, 'ruangan'])->name('ruangan');
    Route::get('/ruangan/{ruanganId}/bmn', [AsetController::class, 'bmn'])->name('bmn');
});

Route::get('/pegawai', [PegawaiController::class, 'index'])
    ->name('pegawai');

Route::get('/infrastruktur', function () {
    return view('infrastruktur', ['title' => 'Infrastruktur']);
});

Route::post('/chat', [TirtaAgentController::class, 'chat'])
    ->name('tirta-agent.chat');

Route::get('/sipp/publikasi-pegawai', [SIPPController::class, 'getPublikasiByNip']);
