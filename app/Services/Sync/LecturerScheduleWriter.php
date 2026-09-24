<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\DB;

/**
 * Penyimpan penjadwalan dosen hasil tarikan API ke tabel relasional
 * `dosen_jadwals` (satu baris per jadwal x waktu kuliah). Dipakai bersama oleh
 * halaman profil dosen dan skrip CLI supaya tidak ada dua salinan logika.
 */
class LecturerScheduleWriter
{
    /**
     * Ganti seluruh baris penjadwalan satu NIP & semester dengan hasil tarikan baru.
     *
     * @param  array<int, array<string, mixed>>  $items  respons /rencana-studi/penjadwalan
     * @return int  jumlah baris yang disimpan
     */
    public static function simpan(string $nip, string $semester, array $items): int
    {
        $waktuSekarang = now();
        $baris = [];

        foreach ($items as $mk) {
            if (!is_array($mk)) {
                continue;
            }

            $mataKuliah = is_array($mk['mata_kuliah'] ?? null) ? $mk['mata_kuliah'] : [];

            foreach ($mk['jadwal'] ?? [] as $jadwal) {
                $kelas = is_array($jadwal['kelas'] ?? null)
                    ? collect($jadwal['kelas'])->pluck('nama_kelas')->filter()->implode(', ')
                    : trim((string) ($jadwal['nama_kelas'] ?? ''));

                foreach ($jadwal['waktu_kuliah'] ?? [] as $waktu) {
                    $ruang = is_array($waktu['ruang'] ?? null) ? $waktu['ruang'] : [];

                    $baris[] = [
                        'nip' => trim($nip),
                        'semester' => $semester,
                        'jadwal_id' => $jadwal['jadwal_id'] ?? null,
                        'kode_jadwal' => $jadwal['kode_jadwal'] ?? null,
                        'mata_kuliah_kode' => $mataKuliah['kode'] ?? null,
                        'mata_kuliah_nama' => $mataKuliah['nama'] ?? null,
                        'mata_kuliah_sks' => (int) ($mataKuliah['sks'] ?? $jadwal['sks'] ?? 0),
                        'sks' => (int) ($jadwal['sks'] ?? 0),
                        'mode' => $jadwal['mode'] ?? null,
                        'tipe_jadwal' => $jadwal['tipe_jadwal'] ?? null,
                        'kelas' => $kelas !== '' ? $kelas : null,
                        'hari' => $waktu['hari'] ?? null,
                        'hari_numeric' => (int) ($waktu['hari_numeric'] ?? 0),
                        'jam_mulai' => $waktu['jam_mulai'] ?? null,
                        'jam_selesai' => $waktu['jam_selesai'] ?? null,
                        'nama_ruang' => $ruang['nama_ruang'] ?? ($waktu['nama_ruang'] ?? null),
                        'kode_ruang' => $ruang['kode_ruang'] ?? null,
                        'created_at' => $waktuSekarang,
                        'updated_at' => $waktuSekarang,
                    ];
                }
            }
        }

        DB::table('dosen_jadwals')
            ->where('nip', trim($nip))
            ->where('semester', $semester)
            ->delete();

        foreach (array_chunk($baris, 200) as $potongan) {
            DB::table('dosen_jadwals')->insert($potongan);
        }

        return count($baris);
    }
}
