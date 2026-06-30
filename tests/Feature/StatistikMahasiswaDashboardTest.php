<?php

use Illuminate\Support\Facades\Blade;

test('komponen statistik mahasiswa merender section dan chart', function () {
    $html = Blade::render('<x-dashboard.statistik-mahasiswa />');

    expect($html)
        ->toContain('Statistik Mahasiswa')
        ->toContain('Total Mahasiswa')
        ->toContain('Mahasiswa Aktif')
        ->toContain('Mahasiswa Baru')
        ->toContain('Mahasiswa Lulus')
        ->toContain('data-statistik-mahasiswa-chart')
        ->toContain('Vertical Stacked Bar Chart')
        ->toContain('Tahun Akademik')
        ->toContain('Semester')
        ->toContain('Fakultas')
        ->toContain('data-statistik-mahasiswa-year')
        ->toContain('data-statistik-mahasiswa-semester')
        ->toContain('data-statistik-mahasiswa-faculty')
        ->toContain('data-statistik-mahasiswa-root');
});
