<?php

use App\Models\Mahasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Buat data mahasiswa pada periode masuk tertentu.
 * Trend dihitung per-semester dengan pembanding semester yang sama di tahun
 * sebelumnya (mis. 20251 dibandingkan dengan 20241), bukan per-angkatan
 * maupun per-semester urutan sebelumnya.
 */
function buatMahasiswaAkademik(string $nim, string $periodeMasuk, string $angkatan): Mahasiswa
{
    return Mahasiswa::create([
        'nim' => $nim,
        'nama' => 'Mahasiswa ' . $nim,
        'prodi_id' => 'TI',
        'jalur_masuk_id' => 'snbt',
        'program_id' => 'sarjana',
        'jenjang_id' => 'S1',
        'dosen_wali_id' => 'D001',
        'angkatan' => $angkatan,
        'tanggal_masuk' => '2025-09-01',
        'kewarganegaraan' => 'ID',
        'agama' => 'Islam',
        'jenis_kelamin_string' => 'L',
        'tempat_tanggal_lahir' => 'Bandung, 2000-01-01',
        'payload' => ['periode_masuk' => $periodeMasuk],
    ]);
}

/**
 * Fake seluruh API eksternal (Siakad + SIMPEG) agar controller
 * mendapat data deterministik tanpa koneksi jaringan nyata.
 */
function fakeApiAkademik(): void
{
    config(['services.siakang.base_url' => 'https://siakang.test/api']);
    config(['services.simpeg.base_url' => 'https://simpeg.test']);

    Http::fake([
        'https://siakang.test/api/v2/mahasiswa-aktif*' => Http::response([
            'success' => true,
            'data' => [
                'total_mahasiswa_aktif' => 100,
                'detail_per_fakultas' => [],
                'detail_per_prodi' => [],
            ],
        ], 200),
        'https://siakang.test/api/v2/mahasiswa-lulus*' => Http::response([
            'success' => true,
            'data' => [
                'total_mahasiswa_lulus' => 200,
                'detail_per_fakultas' => [],
                'detail_per_prodi' => [],
            ],
        ], 200),
        'https://simpeg.test/*' => Http::response([
            'success' => true,
            'data' => [],
        ], 200),
    ]);
}

test('kartu mahasiswa baru menampilkan badge trend dan footer semester yang dipilih', function () {
    fakeApiAkademik();

    // 100 mahasiswa masuk di 20241, 107 mahasiswa masuk di 20251.
    // Trend membandingkan semester yang sama di tahun sebelumnya (20251 vs 20241),
    // sehingga kenaikannya +7% dan footer menunjukkan semester yang dipilih.
    for ($i = 1; $i <= 100; $i++) {
        buatMahasiswaAkademik('111124' . str_pad((string) $i, 4, '0', STR_PAD_LEFT), '20241', '2024');
    }
    for ($i = 1; $i <= 107; $i++) {
        buatMahasiswaAkademik('111125' . str_pad((string) $i, 4, '0', STR_PAD_LEFT), '20251', '2025');
    }

    $response = $this->get('/akademik?semester=20251');

    $response->assertOk();

    // Kartu MAHASISWA BARU: nilai semester sekarang + badge trend + footer
    $response->assertSee('MAHASISWA BARU')
        ->assertSee('+7%')
        ->assertSee('Semester Ganjil 2025');

    // Pastikan badge trend dan footer dirender berdampingan di kartu yang sama.
    // Tag <span> badge bisa terpotong baris di blade, jadi pakai \s+ agar
    // tolerant terhadap whitespace antar atribut.
    $html = $response->getContent();
    expect($html)->toMatch(
        '/<span\s+class="bg-blue-500[^"]*">\+7%<\/span>\s*<span\s+class="text-xs text-gray-400">Semester Ganjil 2025<\/span>/'
    );
});

test('footer kartu mengikuti semester yang dipilih pada filter', function () {
    fakeApiAkademik();

    $this->get('/akademik?semester=20251')
        ->assertOk()
        ->assertSee('Semester Ganjil 2025');

    $this->get('/akademik?semester=20252')
        ->assertOk()
        ->assertSee('Semester Genap 2025');
});
