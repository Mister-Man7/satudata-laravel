<?php

/**
 * Simpan User-Agent atau Cookie Cloudflare ke .env tanpa menampilkan nilainya.
 *
 * Nilai diambil dari clipboard (lebih aman: tidak masuk riwayat shell/chat), lalu
 * atribut seperti `Path=/; HttpOnly` dibuang sehingga tinggal pasangan `nama=nilai`
 * yang memang dikirim pada header Cookie. Nilai hanya PERNAH ditulis ke .env
 * (gitignored) dan ditampilkan dalam bentuk nama cookie + panjang saja.
 *
 * Jalankan:
 *   php scripts/save-cookie.php siakang          (cookie dari clipboard)
 *   php scripts/save-cookie.php sipp
 *   php scripts/save-cookie.php siakang --ua     (User-Agent dari clipboard)
 *   php scripts/save-cookie.php sipp --token     (bearer token dari clipboard)
 *
 * Untuk --token, clipboard boleh berisi nilai token saja maupun tempelan console
 * Postman: pola "authorization: Bearer xxx" atau "2687|xxxx" akan diambil otomatis.
 *
 * Opsi tambahan (untuk uji/otomasi, hindari di riwayat shell):
 *   --value="ci_session=contoh; Path=/; HttpOnly"  --file=path/.env-lain
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

/** Nama atribut cookie yang tidak boleh dikirim sebagai pasangan. */
$atribut = [
    'path', 'domain', 'expires', 'max-age', 'samesite', 'secure', 'httponly',
    'priority', 'partitioned', 'size', 'version', 'comment',
];

$arguments = static function (string $name) use ($argv): ?string {
    foreach (array_slice($argv, 1) as $item) {
        if (str_starts_with($item, "--{$name}=")) {
            return substr($item, strlen($name) + 3);
        }
    }

    return null;
};

$service = null;
foreach (array_slice($argv, 1) as $item) {
    if (!str_starts_with($item, '--')) {
        $service = strtolower($item);
        break;
    }
}

if (!in_array($service, ['sipp', 'siakang'], true)) {
    echo 'Pakai: php scripts/save-cookie.php <sipp|siakang> [--ua|--token] [--value=...] [--file=...]' . PHP_EOL;
    exit(1);
}

$modeUa = in_array('--ua', $argv, true);
$modeToken = in_array('--token', $argv, true);
$prefix = strtoupper($service);
$key = match (true) {
    $modeUa => "{$prefix}_API_USER_AGENT",
    $modeToken => "{$prefix}_API_TOKEN",
    default => "{$prefix}_API_COOKIE",
};
$file = $arguments('file') ?: base_path('.env');

/**
 * Ambil nilai dari clipboard (Windows/macOS/Linux).
 */
$dariClipboard = static function (): ?string {
    $command = match (PHP_OS_FAMILY) {
        'Windows' => 'powershell -NoProfile -Command Get-Clipboard -Raw',
        'Darwin' => 'pbpaste',
        default => 'xclip -selection clipboard -o 2>/dev/null || wl-paste 2>/dev/null',
    };

    $result = @shell_exec($command);

    if (!is_string($result) || trim($result) === '') {
        return null;
    }

    return trim($result);
};

$nilai = $arguments('nilai') ?? $dariClipboard();

if ($nilai === null) {
    echo 'Clipboard kosong. Salin dulu cookie/User-Agent, lalu jalankan ulang.' . PHP_EOL;
    echo '(Atau kirim nilai lewat --value="..." hanya untuk keperluan uji.)' . PHP_EOL;
    exit(1);
}

if ($modeToken) {
    $nilaiBersih = null;

    if (preg_match('/\bBearer\s+([^\s"\']+)/i', $nilai, $matches) === 1) {
        $nilaiBersih = $matches[1];
    } elseif (preg_match('/\b(\d+\|[A-Za-z0-9]{20,})/', $nilai, $matches) === 1) {
        $nilaiBersih = $matches[1];
    }

    if ($nilaiBersih === null) {
        echo 'Tidak menemukan bearer token pada input. Salin nilai token (atau baris authorization) lalu jalankan ulang.' . PHP_EOL;
        exit(1);
    }

    $summary = 'token (' . strlen($nilaiBersih) . ' karakter)';
} elseif ($modeUa) {
    $nilaiBersih = trim(preg_replace('/\s+/', ' ', $nilai));
    $summary = 'User-Agent (' . strlen($nilaiBersih) . ' karakter)';
} else {
    $pasangan = [];

    foreach (explode(';', $nilai) as $job) {
        $job = trim($job);

        if ($job === '' || !str_contains($job, '=')) {
            continue;
        }

        [$name, $content] = array_map('trim', explode('=', $job, 2));

        if ($name === '' || $content === '' || in_array(strtolower($name), $atribut, true)) {
            continue;
        }

        $pasangan[] = $name . '=' . $content;
    }

    if (empty($pasangan)) {
        echo 'Tidak ada pasangan nama=nilai yang bisa dipakai dari input tersebut.' . PHP_EOL;
        exit(1);
    }

    $nilaiBersih = implode('; ', $pasangan);
    $summary = 'cookie: ' . implode(', ', array_map(fn ($item) => explode('=', $item, 2)[0], $pasangan))
        . ' (' . strlen($nilaiBersih) . ' karakter)';
}

if (!is_file($file)) {
    echo 'File tujuan tidak ditemukan: ' . $file . PHP_EOL;
    exit(1);
}

$content = (string) file_get_contents($file);
$line = $key . '=' . $nilaiBersih;

if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content) === 1) {
    $content = (string) preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $content);
    $aksi = 'diperbarui';
} else {
    $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
    $aksi = 'ditambahkan';
}

file_put_contents($file, $content);

echo "{$key} {$aksi} di " . basename($file) . ' -> ' . $summary . PHP_EOL;

if ($file === base_path('.env')) {
    Artisan::call('config:clear');
    echo 'Config cache dibersihkan.' . PHP_EOL;
    echo 'Verifikasi: php scripts/check-cloudflare-matriks.php' . PHP_EOL;
}
