<?php

use App\Http\Controllers\Academic\AkademikController;
use App\Models\Mahasiswa;

function hitungTrendNilai(int $current, int $previous): array
{
    $controller = app(AkademikController::class);
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('hitungTrend');
    $method->setAccessible(true);

    return $method->invoke($controller, $current, $previous);
}

function totalMahasiswaBaruSemester(string $kodeSemester): int
{
    $controller = app(AkademikController::class);
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('totalMahasiswaBaru');
    $method->setAccessible(true);

    return $method->invoke($controller, $kodeSemester);
}

function buatMahasiswa(string $nim, string $periodeMasuk, string $angkatan = '2025'): Mahasiswa
{
    return Mahasiswa::create([
        'nim' => $nim,
        'nama' => 'Mahasiswa Test ' . $nim,
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
test('totalMahasiswaBaru menghitung berdasarkan periode_masuk semester yang dipilih', function () {
    buatMahasiswa('1111250001', '20251');
    buatMahasiswa('1111250002', '20251');
    buatMahasiswa('1111240001', '20241');
    buatMahasiswa('1111240002', '20241');
    buatMahasiswa('1111240003', '20241');

    expect(totalMahasiswaBaruSemester('20251'))->toBe(2)
        ->and(totalMahasiswaBaruSemester('20241'))->toBe(3)
        ->and(totalMahasiswaBaruSemester('20252'))->toBe(0);
});

test('totalMahasiswaBaru memisahkan mahasiswa dengan angkatan sama tapi periode masuk berbeda', function () {
    buatMahasiswa('1111250001', '20251', '2025');
    buatMahasiswa('1111250002', '20252', '2025');

    expect(totalMahasiswaBaruSemester('20251'))->toBe(1)
        ->and(totalMahasiswaBaruSemester('20252'))->toBe(1);
});

test('hitungTrend menghitung kenaikan dengan benar', function () {
    $trend = hitungTrendNilai(120, 100);

    expect($trend['text'])->toBe('+20%')
        ->and($trend['color'])->toBe('bg-blue-500');
});

test('hitungTrend menghitung penurunan dengan benar', function () {
    $trend = hitungTrendNilai(80, 100);

    expect($trend['text'])->toBe('-20%')
        ->and($trend['color'])->toBe('bg-rose-500');
});

test('hitungTrend menampilkan 0% saat tidak ada perubahan', function () {
    $trend = hitungTrendNilai(100, 100);

    expect($trend['text'])->toBe('0%')
        ->and($trend['color'])->toBe('bg-gray-400');
});

test('hitungTrend menampilkan N/A saat pembanding bernilai nol', function () {
    $trend = hitungTrendNilai(50, 0);

    expect($trend['text'])->toBe('N/A')
        ->and($trend['color'])->toBe('bg-gray-400');
});

test('hitungTrend menampilkan 0% saat nilai sekarang dan pembanding nol', function () {
    $trend = hitungTrendNilai(0, 0);

    expect($trend['text'])->toBe('0%')
        ->and($trend['color'])->toBe('bg-gray-500');
});
