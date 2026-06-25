<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AkademikController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['title' => 'Dashboard']);
});

Route::get('/akademik', [AkademikController::class, 'index'])
    ->name('akademik');

Route::get('/akademik/mahasiswa-lulus', [AkademikController::class, 'mahasiswaLulus'])
    ->name('akademik.mahasiswa-lulus');

Route::get('academic', [AcademicController::class, 'index'])->name('academic');

Route::get('/aset', function () {
    return view('aset', ['title' => 'Aset']);
});

Route::get('/pegawai', function () {
    return view('pegawai', ['title' => 'Pegawai']);
});

Route::get('/infrastruktur', function () {
    return view('infrastruktur', ['title' => 'Infrastruktur']);
});

Route::livewire('/post/create', 'pages::post.create');
