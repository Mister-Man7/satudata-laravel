<?php

/**
 * Penarik penjadwalan dosen dari SIAKANG ke kolom `penjadwalan` pada tabel `dosen_sipps`.
 *
 * Penjadwalan adalah sumber angka SKS & jumlah MK di halaman profil dosen. Datanya
 * hanya tersedia lewat API SIAKANG, dan API itu menolak cURL PHP (Cloudflare challenge),
 * sehingga penarikan dilakukan oleh Chromium lokal (Edge/Chrome) — sama seperti penarik
 * portofolio SIPP. Hasilnya disimpan apa adanya supaya halaman profil terisi dari database.
 *
 * Jalankan:
 *   php scripts/sync-penjadwalan.php --nip=197812042010122001 --semester=20252
 *   php scripts/sync-penjadwalan.php --limit=25 --semester=20252
 *   php scripts/sync-penjadwalan.php --limit=50 --force
 *
 * Opsi:
 *   --nip=        daftar NIP dipisah koma (prioritas tertinggi)
 *   (tanpa --nip) ambil NIP dari dosen_sipps yang belum punya penjadwalan semester itu
 *   --semester=   kode semester (default 20252)
 *   --limit=      jumlah dosen maksimum (default 25)
 *   --batch=      jumlah dosen per sesi Chromium (default 10)
 *   --delay=       jeda antar sesi dalam ms (default 2000)
 *   --force       tarik ulang walau baris penjadwalan sudah ada
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DosenSipp;

$opsi = static function (string $name, ?string $default = null) use ($argv): ?string {
    foreach (array_slice($argv, 1) as $item) {
        if (str_starts_with($item, "--{$name}=")) {
            return substr($item, strlen($name) + 3);
        }
    }

    return in_array("--{$name}", $argv, true) ? '' : $default;
};

$base = rtrim((string) config('services.siakang.base_url'), '/');
$token = (string) config('services.siakang.token');
$ua = (string) (config('services.siakang.user_agent')
    ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

if ($token === '') {
    echo 'SIAKANG_API_TOKEN belum diisi di .env (pakai: php scripts/save-cookie.php siakang --token).' . PHP_EOL;
    exit(1);
}

$semester = (string) ($opsi('semester') ?: '20252');
$limit = max(1, (int) ($opsi('limit') ?: 25));
$batch = max(1, (int) ($opsi('batch') ?: 10));
$jeda = max(0, (int) ($opsi('jeda') ?: 2000));
$paksa = in_array('--force', $argv, true);
$nipOpsi = (string) ($opsi('nip') ?: '');

$nipValid = static fn ($nip): bool => preg_match('/^\d{18}$/', (string) $nip) === 1;

$sudahAda = \Illuminate\Support\Facades\DB::table('dosen_jadwals')
    ->where('semester', $semester)
    ->distinct()
    ->pluck('nip')
    ->all();

if ($nipOpsi !== '') {
    $nipList = array_values(array_filter(array_map('trim', explode(',', $nipOpsi))));
} else {
    $nipList = DosenSipp::query()
        ->orderBy('nip')
        ->pluck('nip')
        ->unique()
        ->filter($nipValid)
        ->reject(fn ($nip) => in_array($nip, $sudahAda, true) && !$paksa)
        ->take($limit)
        ->values()
        ->all();
}

if (empty($nipList)) {
    echo 'Tidak ada dosen yang perlu ditarik untuk semester ' . $semester . '.' . PHP_EOL;
    exit(0);
}

$browserCandidates = [
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    getenv('LOCALAPPDATA') . '\\Google\\Chrome\\Application\\chrome.exe',
];

$browser = null;
foreach ($browserCandidates as $browserPath) {
    if (is_string($browserPath) && is_file($browserPath)) {
        $browser = $browserPath;
        break;
    }
}

if ($browser === null) {
    echo 'Edge/Chrome tidak ditemukan.' . PHP_EOL;
    exit(1);
}

echo 'Semester       : ' . $semester . PHP_EOL;
echo 'Dosen diproses : ' . count($nipList) . PHP_EOL;
echo 'Browser        : ' . basename($browser) . PHP_EOL . PHP_EOL;

$berhasil = 0;
$totalItem = 0;
$failed = [];
$batchSemua = array_chunk($nipList, $batch);

foreach ($batchSemua as $indeks => $batchNip) {
    echo '[batch ' . ($indeks + 1) . '/' . count($batchSemua) . '] ' . count($batchNip) . ' dosen' . PHP_EOL;

    $result = pullScheduleBatch($browser, $base, $token, $ua, $batchNip, $semester);

    foreach ($result as $nip => $data) {
        if ((int) ($data['status'] ?? 0) !== 200) {
            $failed[] = $nip . ' (HTTP ' . ($data['status'] ?? 0) . ' ' . mb_substr((string) ($data['message'] ?? ''), 0, 50) . ')';
            continue;
        }

        $items = is_array($data['item'] ?? null) ? $data['item'] : [];

        if (empty($items)) {
            echo '  · ' . $nip . ': tidak ada mata kuliah' . PHP_EOL;
            continue;
        }

        try {
                \App\Services\Sync\LecturerScheduleWriter::store($nip, $semester, array_values($items));

            $berhasil++;
            $totalItem += count($items);
            echo '  · ' . $nip . ': ' . count($items) . ' mata kuliah disimpan' . PHP_EOL;
        } catch (\Throwable $e) {
            $failed[] = $nip . ' (gagal simpan: ' . $e->getMessage() . ')';
        }
    }

    if ($jeda > 0 && $indeks < count($batchSemua) - 1) {
        usleep($jeda * 1000);
    }
}

$totalBaris = \Illuminate\Support\Facades\DB::table('dosen_jadwals')
    ->where('semester', $semester)
    ->distinct()
    ->count('nip');

echo PHP_EOL . 'Tersimpan        : ' . $berhasil . ' dosen, ' . $totalItem . ' mata kuliah' . PHP_EOL;
echo 'Baris penjadwalan semester ' . $semester . ': ' . $totalBaris . PHP_EOL;

if (!empty($failed)) {
    echo 'Gagal (' . count($failed) . '): ' . implode(' | ', array_slice($failed, 0, 5)) . PHP_EOL;
    echo 'Bila ada HTTP 429, ulangi dengan --delay lebih besar.' . PHP_EOL;
}

/**
 * Tarik penjadwalan satu batch NIP memakai satu sesi Chromium.
 *
 * @param  array<int, string>  $nipList
 * @return array<string, array{status: int|string, message: string, item: array<int, mixed>}>
 */
function pullScheduleBatch(string $browser, string $base, string $token, string $ua, array $nipList, string $semester): array
{
    $folder = storage_path('app/sync-penjadwalan');

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }


    $js = <<<'JS'
const token = __TOKEN__;
const base = __BASE__;
const semester = __SEMESTER__;
const list = __LIST__;

(async () => {
    const result = {};

    for (const nip of list) {
        const url = base + '/rencana-studi/penjadwalan?semester=' + encodeURIComponent(semester) + '&nip=' + encodeURIComponent(nip);

        try {
            const res = await fetch(url, { headers: { Authorization: 'Bearer ' + token, accept: 'application/json' } });
            const text = await res.text();
            let data = null;
            try { data = JSON.parse(text); } catch (e) { data = null; }

            const content = data?.data ?? data;
            const item = Array.isArray(content) ? content : (Array.isArray(content?.data) ? content.data : []);

            result[nip] = { status: res.status, message: String(data?.message ?? text).slice(0, 120), item: item };
        } catch (e) {
            result[nip] = { status: 'ERROR', message: e.message, item: [] };
        }
    }

    document.getElementById('result').textContent = JSON.stringify(result);
})();
JS;

    $js = str_replace(
        ['__TOKEN__', '__BASE__', '__SEMESTER__', '__LIST__'],
        [json_encode($token), json_encode($base), json_encode($semester), json_encode($nipList)],
        $js
    );

    $fetcher = app(App\Services\Integrations\ChromiumFetcher::class);
    $dump = (string) $fetcher->fetch(
        '<!doctype html><html><body><pre id="result">MENUNGGU</pre><script>' . $js . '</script></body></html>',
        $ua,
        120000
    );

    $result = json_decode($fetcher->fetchResult($dump), true);

    if (!is_array($result)) {
        echo '  ⚠ sesi Chromium gagal dibaca (dump ' . strlen($dump) . ' byte)' . PHP_EOL;
        return [];
    }

    return $result;
}
