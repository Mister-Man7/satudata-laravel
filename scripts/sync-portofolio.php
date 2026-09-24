<?php

/**
 * Penarik portofolio SIPP massal memakai Chromium lokal (Edge/Chrome).
 *
 * Latar belakang: dari mesin ini hanya klien berbasis Chromium yang lolos Cloudflare
 * (cURL PHP, .NET, dan Node ditolak challenge). API SIPP mewajibkan parameter nip, jadi
 * data ditarik per dosen — namun satu respons sudah memuat rekan penulis di `anggota[]`,
 * sehingga satu kali tarik mengisi puluhan dosen sekaligus. Hasilnya langsung diimpor ke
 * `dosen_sipps` lewat `sync:sipp --import-portofolio` (bagi-bagi berdasarkan anggota,
 * anti duplikat).
 *
 * Jalankan:
 *   php scripts/sync-portofolio.php --nip=196803012002121002 --type=publikasi
 *   php scripts/sync-portofolio.php --type=publikasi,penelitian,pengabdian --limit=25
 *   php scripts/sync-portofolio.php --limit=100 --delay=3000
 *
 * Opsi:
 *   --nip=        daftar NIP dipisah koma (prioritas tertinggi)
 *   (tanpa --nip) ambil NIP dari dosen_sipps yang portofolionya masih kosong
 *   --limit=      jumlah dosen maksimum (default 25)
 *   --type=      publikasi,penelitian,pengabdian (default ketiganya)
 *   --batch=      jumlah dosen per sesi Chromium (default 10)
 *   --delay=       jeda antar sesi dalam ms (default 2000, naikkan bila kena HTTP 429)
 *   --force       abaikan cache 12 jam
 *   --skip-login lewati login ulang token SIPP (pakai token .env apa adanya)
 *   --skip-import hanya simpan berkas JSON, tidak menyentuh database
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DosenSipp;
use App\Services\Sync\PullStatusReporter;
use Illuminate\Support\Facades\Artisan;

$opsi = static function (string $name, ?string $default = null) use ($argv): ?string {
    foreach (array_slice($argv, 1) as $item) {
        if (str_starts_with($item, "--{$name}=")) {
            return substr($item, strlen($name) + 3);
        }
    }

    return in_array("--{$name}", $argv, true) ? '' : $default;
};

// Kunci cache status dari penarik otomatis (mis. --key=sipp_sync); kosong bila dijalankan manual.
$kunciStatus = (string) ($opsi('key', '') ?? '');

$base = rtrim((string) config('services.sipp.base_url'), '/');

// Token SIPP diambil ulang lewat login Chromium, karena endpoint login menentang request
// PHP/cURL (Cloudflare challenge). Dengan begitu token tidak perlu diperbarui manual.
if (!in_array('--skip-login', $argv, true)) {
    $loginResult = app(App\Services\Integrations\LoginChromium::class)->refreshSippToken();

    echo 'Token SIPP     : ' . $loginResult['message']
        . ($loginResult['success'] ? ' (' . $loginResult['length'] . ' karakter)' : '') . PHP_EOL;
}

$token = (string) config('services.sipp.token');
$ua = (string) (config('services.sipp.user_agent')
    ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

if ($token === '') {
    echo 'SIPP_API_TOKEN belum diisi di .env (pakai: php scripts/save-cookie.php sipp --token).' . PHP_EOL;
    PullStatusReporter::laporkan($kunciStatus, 'gagal', 'Token SIPP belum diisi di .env.');
    exit(1);
}

$requestedTypes = array_values(array_filter(array_map('trim', explode(',', (string) ($opsi('type') ?: 'publikasi,penelitian,pengabdian')))));
$validTypes = ['publikasi', 'penelitian', 'pengabdian'];
$requestedTypes = array_values(array_intersect($requestedTypes, $validTypes));

if (empty($requestedTypes)) {
    echo 'Jenis tidak valid. Pilih: ' . implode(', ', $validTypes) . PHP_EOL;
    PullStatusReporter::laporkan($kunciStatus, 'gagal', 'Jenis portofolio tidak valid.');
    exit(1);
}

$limit = max(1, (int) ($opsi('limit') ?: 25));
$batch = max(1, (int) ($opsi('batch') ?: 10));
$jeda = max(0, (int) ($opsi('jeda') ?: 2000));
$paksa = in_array('--force', $argv, true);
$tanpaImpor = in_array('--skip-import', $argv, true);
$nipOpsi = (string) ($opsi('nip') ?: '');

$workDirectory = storage_path('app/sync-sipp');
$folderCache = $workDirectory . '/cache';

foreach ([$workDirectory, $folderCache] as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
}

$nipValid = static fn ($nip): bool => preg_match('/^\d{18}$/', (string) $nip) === 1;

$nipList = [];

if ($nipOpsi !== '') {
    $nipList = array_values(array_filter(array_map('trim', explode(',', $nipOpsi))));
} else {
    $nipList = DosenSipp::query()
        ->where(function ($q) {
            $q->whereNull('publikasi')->orWhereNull('penelitian')->orWhereNull('pengabdian');
        })
        ->orderBy('nip')
        ->pluck('nip')
        ->unique()
        ->filter($nipValid)
        ->take($limit)
        ->values()
        ->all();
}

if (empty($nipList)) {
    echo 'Tidak ada dosen yang perlu ditarik (semua sudah punya portofolio).' . PHP_EOL;
    PullStatusReporter::laporkan($kunciStatus, 'selesai', 'Semua dosen sudah punya portofolio; tidak ada yang perlu ditarik.');
    exit(0);
}

echo 'Dosen diproses : ' . count($nipList) . ' (type: ' . implode(', ', $requestedTypes) . ')' . PHP_EOL;

$sisa = DosenSipp::query()
    ->where(function ($q) {
        $q->whereNull('publikasi')->orWhereNull('penelitian')->orWhereNull('pengabdian');
    })
    ->pluck('nip')
    ->unique()
    ->filter($nipValid)
    ->count();

echo 'Masih kosong   : ' . $sisa . ' dosen (NIP 18 digit) di dosen_sipps' . PHP_EOL;

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
    PullStatusReporter::laporkan($kunciStatus, 'gagal', 'Host aplikasi tidak memiliki Edge/Chrome.');
    exit(1);
}

echo 'Browser        : ' . basename($browser) . PHP_EOL . PHP_EOL;

// ---- Susun pekerjaan: kombinasi (nip, jenis) yang perlu ditarik ----
$jobs = [];
$dilewati = 0;

foreach ($nipList as $nip) {
    foreach ($requestedTypes as $type) {
        $cacheFile = $folderCache . "/{$type}-{$nip}.json";

        if (!$paksa && is_file($cacheFile) && (time() - filemtime($cacheFile)) < 12 * 3600) {
            $dilewati++;
            continue;
        }

        $jobs[] = ['nip' => $nip, 'type' => $type];
    }
}

echo 'Pekerjaan      : ' . count($jobs) . ' tarikan'
    . ($dilewati > 0 ? " ({$dilewati} dilewati karena cache masih segar)" : '') . PHP_EOL . PHP_EOL;

$itemPerJenis = [];       // jenis => [kunci item => item]
$totalRequests = 0;
$failed = [];
$batchSemua = array_chunk($jobs, $batch);

foreach ($batchSemua as $indeks => $batchPekerjaan) {
    echo '[batch ' . ($indeks + 1) . '/' . count($batchSemua) . '] ' . count($batchPekerjaan) . ' tarikan' . PHP_EOL;

    $batchResult = pullBatch($browser, $base, $token, $ua, $batchPekerjaan, $totalRequests, $failed);

    foreach ($batchResult as $nip => $perJenis) {
        foreach ($perJenis as $type => $items) {
            if (!empty($items)) {
                file_put_contents($folderCache . "/{$type}-{$nip}.json", json_encode($items));
            }
        }
    }

    if ($jeda > 0 && $indeks < count($batchSemua) - 1) {
        usleep($jeda * 1000);
    }
}

// Cache yang dilewati tetap diikutkan supaya hasil impor menyeluruh.
foreach ($nipList as $nip) {
    foreach ($requestedTypes as $type) {
        $cacheFile = $folderCache . "/{$type}-{$nip}.json";

        if (!is_file($cacheFile)) {
            continue;
        }

        foreach (json_decode((string) file_get_contents($cacheFile), true) ?: [] as $item) {
            if (is_array($item)) {
                $itemPerJenis[$type][itemKey($item)] = $item;
            }
        }
    }
}

if (empty($itemPerJenis)) {
    echo PHP_EOL . 'Tidak ada item yang berhasil ditarik.' . PHP_EOL;

    if (!empty($failed)) {
        echo 'Gagal (' . count($failed) . '): ' . implode(' | ', array_slice($failed, 0, 8)) . PHP_EOL;
    }

    PullStatusReporter::laporkan(
        $kunciStatus,
        'gagal',
        'Tidak ada item yang berhasil ditarik' . (empty($failed) ? '.' : ': ' . implode(' | ', array_slice($failed, 0, 3)))
    );

    exit(1);
}

// ---- Simpan berkas hasil per jenis, lalu impor ke dosen_sipps ----
$stempel = date('Ymd-His');
$filePerType = [];

echo PHP_EOL;

foreach ($itemPerJenis as $type => $items) {
    $file = "{$workDirectory}/{$type}-{$stempel}.json";
    $filePerType[$type] = $file;

    file_put_contents($file, json_encode([
        'type' => $type,
        'ditarik_pada' => date('c'),
        'count' => count($items),
        $type => array_values($items),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    echo '✓ ' . str_pad($type, 12) . ': ' . count($items) . ' item unik -> ' . basename($file) . PHP_EOL;
}

if ($tanpaImpor) {
    echo PHP_EOL . '(--skip-import) Berkas disimpan, database tidak diubah.' . PHP_EOL;
    PullStatusReporter::laporkan(
        $kunciStatus,
        'selesai',
        'Data ditarik, tetapi database tidak diubah (--skip-import).',
        array_sum(array_map('count', $itemPerJenis))
    );
    exit(0);
}

echo PHP_EOL;

foreach ($filePerType as $file) {
    Artisan::call('sync:sipp', ['--import-portofolio' => $file]);
    echo trim(Artisan::output()) . PHP_EOL;
}

$terisi = DosenSipp::query()
    ->where(function ($q) {
        $q->whereNotNull('publikasi')->orWhereNotNull('penelitian')->orWhereNotNull('pengabdian');
    })
    ->distinct()
    ->count('nip');

echo PHP_EOL . 'Permintaan API   : ' . $totalRequests . PHP_EOL;
echo 'Dosen berportofolio di database: ' . $terisi . PHP_EOL;

$totalItem = array_sum(array_map('count', $itemPerJenis));

if (!empty($failed)) {
    echo 'Gagal (' . count($failed) . '): ' . implode(' | ', array_slice($failed, 0, 5)) . PHP_EOL;
    echo 'Bila ada HTTP 429, ulangi dengan --delay lebih besar (mis. 5000).' . PHP_EOL;
}

PullStatusReporter::laporkan(
    $kunciStatus,
    empty($failed) ? 'selesai' : 'gagal',
    $totalItem . ' item dari ' . $totalRequests . ' permintaan API'
        . (empty($failed) ? '.' : '; ' . count($failed) . ' gagal: ' . implode(' | ', array_slice($failed, 0, 3))),
    $totalItem
);

/**
 * Tarik data satu batch pekerjaan memakai satu sesi Chromium headless.
 *
 * @param  array<int, array{nip: string, type: string}>  $jobs
 * @return array<string, array<string, array<int, array<string, mixed>>>>
 */
function pullBatch(string $browser, string $base, string $token, string $ua, array $jobs, int &$totalRequests, array &$failed): array
{
    $list = [];
    foreach ($jobs as $job) {
        $list[] = ['nip' => $job['nip'], 'type' => $job['type']];
    }

    $folder = storage_path('app/sync-sipp');
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }


    $js = <<<'JS'
const token = __TOKEN__;
const base = __BASE__;
const jobs = __JOBS__;

(async () => {
    const result = {};

    for (const job of jobs) {
        const all = [];
        let page = 1;
        let lastPage = 1;
        let requests = 0;
        let lastStatus = null;
        let lastMessage = '';

        do {
            let res = null;
            let text = '';

            try {
                res = await fetch(base + '/api/' + job.type + '?nip=' + encodeURIComponent(job.nip) + '&page=' + page + '&per_page=100', {
                    headers: { Authorization: 'Bearer ' + token, accept: 'application/json' },
                });
                text = await res.text();
            } catch (e) {
                lastMessage = 'GAGAL: ' + e.message;
                break;
            }

            requests++;
            lastStatus = res.status;

            let data = null;
            try { data = JSON.parse(text); } catch (e) { data = null; }

            if (lastStatus !== 200 || !data) {
                lastMessage = String(data?.message ?? text).slice(0, 120);
                break;
            }

            (Array.isArray(data.data) ? data.data : []).forEach((item) => all.push(item));

            lastPage = Number(data?.pagination?.last_page ?? 1);
            page++;
        } while (page <= lastPage && page <= 20);

        result[job.type + '|' + job.nip] = {
            status: lastStatus,
            requests: requests,
            message: lastMessage,
            item: all,
        };
    }

    document.getElementById('result').textContent = JSON.stringify(result);
})();
JS;

    $js = str_replace(
        ['__TOKEN__', '__BASE__', '__JOBS__'],
        [json_encode($token), json_encode($base), json_encode($list)],
        $js
    );

    $fetcher = app(App\Services\Integrations\ChromiumFetcher::class);
    $dump = (string) $fetcher->fetch(
        '<!doctype html><html><body><pre id="result">MENUNGGU</pre><script>' . $js . '</script></body></html>',
        $ua,
        180000
    );

    $raw = json_decode($fetcher->fetchResult($dump), true);

    if (!is_array($raw)) {
        foreach ($jobs as $job) {
            $failed[] = $job['type'] . ':' . $job['nip'];
        }

        echo '  ⚠ sesi Chromium gagal dibaca (dump ' . strlen($dump) . ' byte)' . PHP_EOL;
        return [];
    }

    $collected = [];

    foreach ($raw as $key => $result) {
        [$type, $nip] = array_pad(explode('|', (string) $key, 2), 2, '');
        $totalRequests += (int) ($result['requests'] ?? 0);
        $status = (int) ($result['status'] ?? 0);

        if ($status !== 200) {
            $failed[] = $type . ':' . $nip . ' (HTTP ' . $status . ' ' . mb_substr((string) ($result['message'] ?? ''), 0, 60) . ')';
            continue;
        }

        $collected[$nip][$type] = is_array($result['item'] ?? null) ? $result['item'] : [];
        echo '  · ' . $nip . ' ' . $type . ': ' . count($collected[$nip][$type]) . ' item' . PHP_EOL;
    }

    return $collected;
}

/**
 * Kunci unik item agar tidak ada duplikasi antar batch maupun impor ulang.
 */
function itemKey(array $item): string
{
    return (string) ($item['id_portofolio'] ?? $item['id'] ?? md5((string) json_encode($item)));
}
