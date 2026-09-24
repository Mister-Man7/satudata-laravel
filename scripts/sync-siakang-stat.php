<?php

/**
 * Penarik angka agregat SIAKANG (mahasiswa aktif / lulus) per prodi memakai
 * Chromium lokal, lalu menyimpannya ke tabel `siakang_semester_stats`.
 *
 * Dipakai penjadwal dan penarik otomatis (lihat config/satudata.php + SourceRevalidator).
 *
 * Jalankan:
 *   php scripts/sync-siakang-stat.php --jenis=aktif --semester=20252
 *   php scripts/sync-siakang-stat.php --jenis=lulus --semester=20252
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Sync\PullStatusReporter;
use Illuminate\Support\Facades\DB;

$opsi = [];
foreach (array_slice($argv, 1) as $argumen) {
    if (preg_match('/^--([a-z_]+)=(.*)$/i', $argumen, $cocok)) {
        $opsi[strtolower($cocok[1])] = $cocok[2];
    }
}

$jenis = in_array(($opsi['jenis'] ?? 'aktif'), ['aktif', 'lulus'], true) ? $opsi['jenis'] : 'aktif';
$semester = (string) ($opsi['semester'] ?? '');
$kunciStatus = (string) ($opsi['key'] ?? '');

if ($semester === '') {
    echo 'Semester wajib diisi: --semester=YYYYT' . PHP_EOL;
    PullStatusReporter::laporkan($kunciStatus, 'gagal', 'Semester belum ditentukan untuk penarikan statistik SIAKANG.');
    exit(1);
}

$base = rtrim((string) config('services.siakang.base_url'), '/');
$token = (string) config('services.siakang.token');
$ua = (string) (config('services.siakang.user_agent')
    ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

// Catatan bentuk parameter: koleksi Postman mendokumentasikan /v2/mahasiswa-lulus?jenjang&tahun,
// tetapi aplikasi memakai parameter `semester` supaya angkanya sepadan dengan tabel statistik
// per semester (dan sama polanya dengan /v2/mahasiswa-aktif). API menerima keduanya dengan
// cakupan berbeda — semester 20261: 18 prodi; jenjang s1 + tahun 2026: 42 prodi — jadi bentuk
// semester inilah yang dipertahankan.
$endpoint = $jenis === 'aktif' ? '/v2/mahasiswa-aktif' : '/v2/mahasiswa-lulus';
$kunciJumlah = $jenis === 'aktif' ? 'jumlah_mahasiswa_aktif' : 'jumlah_mahasiswa_lulus';

echo 'Jenis     : ' . $jenis . PHP_EOL;
echo 'Semester  : ' . $semester . PHP_EOL;
echo 'Endpoint  : ' . $base . $endpoint . PHP_EOL;

$js = <<<'JS'
const url = __URL__;
const bearer = __TOKEN__;

(async () => {
    let hasil = { status: 0, message: '', prodi: [] };

    try {
        const res = await fetch(url, { headers: { Authorization: 'Bearer ' + bearer, accept: 'application/json' } });
        const teks = await res.text();
        let data = null;
        try { data = JSON.parse(teks); } catch (e) { data = null; }

        const isi = data?.data ?? data ?? {};
        const daftar = Array.isArray(isi?.detail_per_prodi) ? isi.detail_per_prodi : (Array.isArray(isi) ? isi : []);

        hasil = { status: res.status, message: String(data?.message ?? teks).slice(0, 140), prodi: daftar };
    } catch (e) {
        hasil = { status: 'ERROR', message: e.message, prodi: [] };
    }

    document.getElementById('result').textContent = JSON.stringify(hasil);
})();
JS;

$js = str_replace(
    ['__URL__', '__TOKEN__'],
    [json_encode($base . $endpoint . '?semester=' . urlencode($semester)), json_encode($token)],
    $js
);

$fetcher = app(App\Services\Integrations\ChromiumFetcher::class);
$dump = (string) $fetcher->fetch(
    '<!doctype html><html><body><pre id="result">MENUNGGU</pre><script>' . $js . '</script></body></html>',
    $ua,
    120000
);
$hasil = json_decode($fetcher->fetchResult($dump), true);

if (!is_array($hasil)) {
    echo 'Gagal membaca sesi Chromium (dump ' . strlen($dump) . ' byte).' . PHP_EOL;
    PullStatusReporter::laporkan($kunciStatus, 'gagal', 'Sesi Chromium gagal dibaca.');
    exit(1);
}

echo 'Status    : ' . ($hasil['status'] ?? '-') . ' | ' . ($hasil['message'] ?? '') . PHP_EOL;

$prodi = is_array($hasil['prodi'] ?? null) ? $hasil['prodi'] : [];

if (($hasil['status'] ?? 0) !== 200 || empty($prodi)) {
    echo 'Tidak ada data per prodi untuk disimpan.' . PHP_EOL;
    PullStatusReporter::laporkan(
        $kunciStatus,
        'gagal',
        'SIAKANG membalas status ' . ($hasil['status'] ?? '-') . ': ' . ($hasil['message'] ?? 'tanpa data per prodi')
    );
    exit(1);
}

$waktuSekarang = now();
$baris = [];

foreach ($prodi as $item) {
    if (!is_array($item)) {
        continue;
    }

    $jumlah = (int) ($item[$kunciJumlah] ?? $item['jumlah_mahasiswa_aktif'] ?? $item['total'] ?? 0);

    // Rincian gender hanya dikirim endpoint mahasiswa aktif. Payload mahasiswa lulus
    // tidak memuatnya, jadi jangan diturunkan dari (jumlah - laki_laki): cara itu
    // mencatat seluruh lulusan sebagai perempuan.
    $adaGender = array_key_exists('jumlah_laki_laki', $item) || array_key_exists('jumlah_perempuan', $item);
    $laki = $adaGender ? (int) ($item['jumlah_laki_laki'] ?? 0) : 0;

    $baris[] = [
        'semester' => $semester,
        'jenis' => $jenis,
        'prodi_id' => (string) ($item['prodi_id'] ?? $item['kode_prodi'] ?? ''),
        'kode_prodi' => $item['kode_prodi'] ?? null,
        'nama_prodi' => $item['nama_prodi'] ?? null,
        'jenjang' => strtoupper((string) ($item['jenjang'] ?? '')),
        'fakultas' => $item['fakultas'] ?? null,
        'jumlah' => $jumlah,
        'laki_laki' => $laki,
        'perempuan' => $adaGender ? (int) ($item['jumlah_perempuan'] ?? max(0, $jumlah - $laki)) : 0,
        'diambil_pada' => $waktuSekarang,
        'created_at' => $waktuSekarang,
        'updated_at' => $waktuSekarang,
    ];
}

DB::table('siakang_semester_stats')
    ->where('semester', $semester)
    ->where('jenis', $jenis)
    ->delete();

foreach (array_chunk($baris, 200) as $potongan) {
    DB::table('siakang_semester_stats')->insert($potongan);
}

echo 'Tersimpan : ' . count($baris) . ' baris prodi untuk semester ' . $semester . PHP_EOL;

PullStatusReporter::laporkan(
    $kunciStatus,
    'selesai',
    'Statistik ' . $jenis . ' semester ' . $semester . ' diperbarui (' . count($baris) . ' prodi).',
    count($baris)
);
