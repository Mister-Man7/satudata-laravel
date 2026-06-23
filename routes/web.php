<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ["title" => "Dashboard"]);
});

Route::get('/akademik', function () {
    return view('akademik', ["title" => "Akademik"]);
});

Route::get('/aset', function () {
    return view('aset', ["title" => "Aset"]);
});

Route::get('/pegawai', function () {
    return view('pegawai', ["title" => "Pegawai"]);
});

Route::get('/infrastruktur', function () {
    return view('infrastruktur', ["title" => "Infrastruktur"]);
});

Route::livewire('/post/create', 'pages::post.create');
