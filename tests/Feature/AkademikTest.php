<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.siakang.base_url', 'https://siakang.test/api');
    config()->set('services.siakang.token', 'secret-token');
    Http::preventStrayRequests();
});

test('dashboard akademik menampilkan total mahasiswa lulus dari API', function () {
    Http::fake([
        'siakang.test/*' => Http::response([
            'data' => [
                ['total' => 42, 'data' => []],
            ],
        ]),
    ]);

    $this->get(route('akademik'))
        ->assertOk()
        ->assertSeeText('Mahasiswa Lulus')
        ->assertSeeText('42')
        ->assertSee(route('akademik.mahasiswa-lulus'));

    Http::assertSent(function (Request $request): bool {
        $tokenBenar = $request->hasHeader('Authorization', 'Bearer secret-token');
        $userAgentBenar = $request->hasHeader('User-Agent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');
        $refererBenar = $request->hasHeader('Referer', 'https://siakang.test/api/');
        $parameterBenar = $request['limit'] === 1 && $request['page'] === 1;

        return $tokenBenar && $userAgentBenar && $refererBenar && $parameterBenar;
    });
});

test('halaman detail meneruskan filter dan menampilkan data lulusan', function () {
    Http::fake([
        'siakang.test/*' => Http::response([
            'data' => [[
                'total' => 1,
                'current_page' => 1,
                'last_page' => 1,
                'data' => [[
                    'nim' => '3331234567',
                    'nama' => 'Ayu Lestari',
                    'angkatan' => 2020,
                    'tanggal_lulus' => '2024-08-20',
                    'prodi' => ['nama_prodi_lengkap' => 'Teknik Informatika'],
                ]],
            ]],
        ]),
    ]);

    $this->get(route('akademik.mahasiswa-lulus', [
        'search' => 'Ayu',
        'kode_prodi' => '333',
        'angkatan' => 2020,
        'tahun_lulus' => 2024,
    ]))
        ->assertOk()
        ->assertSeeText('Ayu Lestari')
        ->assertSeeText('Teknik Informatika')
        ->assertSeeText('3331234567');

    Http::assertSent(function (Request $request): bool {
        $pencarianBenar = $request['search'] === 'Ayu';
        $prodiBenar = $request['kode_prodi'] === '333';
        $angkatanBenar = $request['angkatan'] === '2020';
        $tahunLulusBenar = $request['tahun_lulus'] === '2024';
        $jumlahDataBenar = $request['limit'] === 25;

        return $pencarianBenar
            && $prodiBenar
            && $angkatanBenar
            && $tahunLulusBenar
            && $jumlahDataBenar;
    });
});

test('halaman akademik tetap dapat dibuka saat API tidak tersedia', function () {
    Http::fake([
        'siakang.test/*' => Http::failedConnection(),
    ]);

    $this->get(route('akademik'))
        ->assertOk()
        ->assertSeeText('API tidak tersedia')
        ->assertSeeText('—');
});

test('filter tahun akademik divalidasi', function () {
    $this->from(route('akademik.mahasiswa-lulus'))
        ->get(route('akademik.mahasiswa-lulus', ['tahun_lulus' => 'tidak-valid']))
        ->assertRedirect(route('akademik.mahasiswa-lulus'))
        ->assertSessionHasErrors('tahun_lulus');
});
