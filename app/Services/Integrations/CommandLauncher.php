<?php

namespace App\Services\Integrations;

/**
 * Peluncur penarikan berbasis perintah: skrip Chromium di folder scripts atau
 * perintah artisan. Dipakai sumber data yang penariknya bukan launcher khusus
 * (mis. portofolio SIPP lewat scripts/sync-portofolio.php).
 */
class CommandLauncher extends ProcessLauncher
{
    protected function label(): string
    {
        return 'penarik perintah';
    }

    /**
     * Jalankan satu target di proses terpisah.
     *
     * @param  array<int, string>  $argumen
     */
    public function sync(string $target, array $argumen, string $key): bool
    {
        $bagian = str_contains($target, ':')
            ? array_merge([PHP_BINARY, base_path('artisan'), $target], $argumen)
            : array_merge([PHP_BINARY, base_path($target)], $argumen);

        return $this->luncurkanBagian($bagian, 'sumber-sync.log', 'sync-sumber', [
            'target' => $target,
            'arguments' => $argumen,
            'kunci' => $key,
        ]);
    }
}
