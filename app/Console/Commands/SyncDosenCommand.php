<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Penarikan data satu dosen (portofolio SIPP + penjadwalan SIAKANG) memakai Chromium lokal.
 *
 * Dipakai tombol "Tarik data" di halaman profil: halaman hanya mencatat status lalu
 * memantau, sedangkan proses penarikan yang memakan puluhan detik berjalan terpisah
 * lewat perintah ini. Token API tetap di .env server; pengguna tidak diminta apa pun.
 */
class SyncDosenCommand extends Command
{
    protected $signature = 'sync:dosen
                            {nip : NIP dosen (18 digit)}
                            {--semester=20252 : Kode semester}
                            {--key= : Kunci cache status yang dipantau halaman profil}';

    protected $description = 'Tarik portofolio SIPP dan penjadwalan SIAKANG satu dosen memakai Chromium lokal, lalu simpan ke database';

    public function handle(): int
    {
        $nip = trim((string) $this->argument('nip'));
        $semester = (string) ($this->option('semester') ?: '20252');
        $key = (string) ($this->option('key') ?: '');
        $statusKey = $key !== '' ? $key : 'dosen_sync_' . $nip . '_' . $semester;

        // Identitas dosen tidak selalu 18 digit (mis. kode DLB dari SIMPEG); API SIPP dan
        // SIAKANG menerima nilainya apa adanya. Yang dicegah hanya identitas kosong.
        if ($nip === '') {
            $this->finish($statusKey, 'gagal', 'Identitas dosen tidak tersedia.');

            return self::FAILURE;
        }

        $jobs = [
            [
                'label' => 'portofolio SIPP',
                'args' => [
                    'scripts/sync-portofolio.php',
                    '--nip=' . $nip,
                    '--type=publikasi,penelitian,pengabdian',
                    '--delay=0',
                    '--force',
                ],
            ],
            [
                'label' => 'penjadwalan SIAKANG',
                'args' => [
                    'scripts/sync-penjadwalan.php',
                    '--nip=' . $nip,
                    '--semester=' . $semester,
                    '--delay=0',
                    '--force',
                ],
            ],
        ];

        $summaries = [];
        $failed = false;
        $hasData = false;

        foreach ($jobs as $job) {
            $this->line('→ Menarik ' . $job['label'] . '...');

            $output = $this->runScript($job['args']);
            $summary = $this->summarize($output);

            // Tampilkan keluaran mentah penarik agar penyebab kegagalan mudah ditelusuri.
            $this->line(trim($output));

            $summaries[] = $job['label'] . ': ' . $summary;
            $this->line('  ' . $summary);

            if (preg_match('/(\d+) penugasan ditulis ke|(\d+) mata kuliah disimpan/', $output, $matches) === 1 && (int) ($matches[1] ?? $matches[2]) > 0) {
                $hasData = true;
            }

            if (preg_match('/tidak ditemukan|belum diisi|gagal menjalankan|Gagal \(|QueryException/i', $output) === 1) {
                $failed = true;
            }

            if (preg_match('/HTTP 401|Unauthenticated/i', $output) === 1) {
                $failed = true;
                $summaries[] = 'Token API kedaluwarsa: perbarui di .env lewat "php scripts/save-cookie.php sipp --token" (dari clipboard)';
            }
        }

        // Tandai bahwa dosen ini memang belum punya data, supaya halaman profil tidak
        // berulang kali memicu penarikan otomatis untuk dosen yang sama.
        if (!$failed && !$hasData) {
            Cache::put('dosen_sync_empty_' . $nip . '_' . $semester, true, now()->addHours(24));
        }

        $this->finish($statusKey, $failed ? 'gagal' : 'selesai', implode(' | ', $summaries), $hasData);

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Jalankan salah satu skrip penarik dan ambil seluruh keluarannya.
     *
     * @param  array<int, string>  $args
     */
    private function runScript(array $args): string
    {
        $process = @proc_open(
            array_merge([PHP_BINARY], $args),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path()
        );

        if (!is_resource($process)) {
            return 'gagal menjalankan ' . $args[0];
        }

        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        proc_close($process);

        return (string) $output;
    }

    /**
     * Ambil baris ringkasan penting dari keluaran skrip penarik.
     */
    private function summarize(string $output): string
    {
        $line = array_values(array_filter(
            preg_split('/\r?\n/', $output) ?: [],
            static fn ($content) => trim($content) !== '' && !str_starts_with(trim($content), '[batch')
        ));

        $penting = [];

        foreach ($line as $content) {
            $content = trim($content);

            if (preg_match('/penugasan ditulis|mata kuliah disimpan|Baris penjadwalan|Tidak ada|tidak ada|tidak ditemukan|belum diisi|Gagal|gagal menjalankan/i', $content) === 1) {
                $penting[] = $content;
            }
        }

        if (empty($penting)) {
            $penting = array_slice($line, -2);
        }

        return mb_substr(implode('; ', array_slice($penting, 0, 3)), 0, 250);
    }

    /**
     * Tulis status penarikan agar halaman profil bisa menampilkannya.
     */
    private function finish(string $statusKey, string $status, string $message, ?bool $hasData = null): void
    {
        Cache::put($statusKey, [
            'status' => $status,
            'message' => $message,
            'has_data' => $hasData,
            'time' => now()->toDateTimeString(),
        ], now()->addMinutes(15));

        $this->info(($status === 'selesai' ? 'Selesai: ' : 'Gagal: ') . $message);
    }
}
