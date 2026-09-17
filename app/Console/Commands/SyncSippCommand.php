<?php

namespace App\Console\Commands;

use App\Models\DosenSipp;
use App\Services\Integrations\SIPPService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSippCommand extends Command
{
    protected $signature = 'sync:sipp 
                            {--semester=20252 : Kode semester (misal: 20252, 20231)}
                            {--nip= : NIP dosen spesifik}
                            {--type= : Tipe indikator spesifik (sinta12, sinta36, pengembangan, jurnal_issn, jurnal_q, jurnal_pbb, pengabdian)}
                            {--import= : Path file JSON atau JSON string hasil response SIPP untuk disimpan ke database}
                            {--import-dir= : Path folder berisi file-file JSON hasil response SIPP}';

    protected $description = 'Sinkronisasi seluruh 7 data kinerja luaran ilmiah SIPP dari API / JSON ke Database MySQL';

    public function handle(SIPPService $sippService): int
    {
        $semester = (string) ($this->option('semester') ?: '20252');
        $specificNip = $this->option('nip');
        $specificType = $this->option('type');
        $importPayload = $this->option('import');
        $importDir = $this->option('import-dir');

        // Mapping variasi nama indikator ke nama kolom di database dosen_sipps
        $indicatorMap = [
            'sinta12'                  => 'sinta12',
            'sinta1_sinta2'            => 'sinta12',
            'penelitian_sinta1_sinta2' => 'sinta12',

            'sinta36'                  => 'sinta36',
            'sinta3456'                => 'sinta36',
            'penelitian_sinta3456'     => 'sinta36',

            'pengembangan'             => 'pengembangan',

            'jurnal_issn'              => 'jurnal_nasional_issn',
            'jurnal_nasional_issn'     => 'jurnal_nasional_issn',
            'penelitian_jurnal_nasional_issn' => 'jurnal_nasional_issn',

            'jurnal_q'                 => 'jurnal_internasional_q',
            'jurnal_internasional_q'   => 'jurnal_internasional_q',
            'penelitian_jurnal_internasional_q1234' => 'jurnal_internasional_q',

            'jurnal_pbb'               => 'jurnal_internasional_pbb',
            'jurnal_internasional_pbb' => 'jurnal_internasional_pbb',
            'penelitian_jurnal_internasional_pbb' => 'jurnal_internasional_pbb',

            'pengabdian'               => 'pengabdian_masyarakat',
            'pengabdian_masyarakat'    => 'pengabdian_masyarakat',
        ];

        // 1. Mode Import Folder berisi multiple JSON
        if (!empty($importDir) && is_dir($importDir)) {
            $files = glob(rtrim($importDir, DIRECTORY_SEPARATOR) . '/*.json');
            $this->info("Ditemukan " . count($files) . " file JSON di folder $importDir.");

            foreach ($files as $file) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($file, PATHINFO_FILENAME)));
                $colName = null;
                foreach ($indicatorMap as $key => $col) {
                    $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $key));
                    if (str_contains($cleanName, $cleanKey)) {
                        $colName = $col;
                        break;
                    }
                }

                if (!$colName) {
                    // Abaikan file JSON lain yang bukan bagian dari indikator SIPP (seperti Pegawai, Mahasiswa, dsb)
                    continue;
                }

                $this->importJsonFile($file, $colName, $semester, $specificNip);
            }

            return self::SUCCESS;
        }

        // 2. Mode Import File / String JSON tunggal
        if (!empty($importPayload)) {
            $colName = $specificType ? ($indicatorMap[$specificType] ?? $specificType) : 'pengembangan';
            $this->importJsonFile($importPayload, $colName, $semester, $specificNip);
            return self::SUCCESS;
        }

        // 3. Mode Live API Sync 7 Endpoint
        $this->info("Memulai sinkronisasi seluruh 7 indikator SIPP dari Live API (semester $semester)...");

        $endpoints = [
            'penelitian_jurnal_nasional_issn' => [
                'col' => 'jurnal_nasional_issn',
                'label' => 'Jurnal Nasional ISSN',
                'fetcher' => fn() => $sippService->getPenelitianJurnalNasionalIssn(['semester' => $semester]),
            ],
            'penelitian_sinta3456' => [
                'col' => 'sinta36',
                'label' => 'Penelitian SINTA 3-6',
                'fetcher' => fn() => $sippService->getPenelitianSinta3456(['semester' => $semester]),
            ],
            'pengembangan' => [
                'col' => 'pengembangan',
                'label' => 'Pengembangan',
                'fetcher' => fn() => $sippService->getPengembangan(['semester' => $semester]),
            ],
            'penelitian_sinta1_sinta2' => [
                'col' => 'sinta12',
                'label' => 'Penelitian SINTA 1-2',
                'fetcher' => fn() => $sippService->getPenelitianSinta1Sinta2(['semester' => $semester]),
            ],
            'penelitian_jurnal_internasional_q1234' => [
                'col' => 'jurnal_internasional_q',
                'label' => 'Jurnal Internasional Q1-4',
                'fetcher' => fn() => $sippService->getPenelitianJurnalInternasionalQ1234(['semester' => $semester]),
            ],
            'penelitian_jurnal_internasional_pbb' => [
                'col' => 'jurnal_internasional_pbb',
                'label' => 'Jurnal Internasional PBB',
                'fetcher' => fn() => $sippService->getPenelitianJurnalInternasionalPbb(['semester' => $semester]),
            ],
            'pengabdian_masyarakat' => [
                'col' => 'pengabdian_masyarakat',
                'label' => 'Pengabdian Masyarakat',
                'fetcher' => fn() => $sippService->getPengabdianMasyarakat(['semester' => $semester]),
            ],
        ];

        $overallCount = 0;

        foreach ($endpoints as $key => $config) {
            if ($specificType && !str_contains($key, $specificType) && ($config['col'] !== $specificType)) {
                continue;
            }

            $this->line("→ Menghubungi endpoint: {$key} ({$config['label']})...");

            try {
                $response = $config['fetcher']();
                if ($response->success && !empty($response->data)) {
                    $items = $response->data['data'] ?? $response->data;
                    if (is_array($items)) {
                        $bebanByNip = $this->aggregateBebanByNip($items);
                        $count = 0;

                        foreach ($bebanByNip as $nip => $beban) {
                            if ($specificNip && $nip !== $specificNip) continue;

                            DosenSipp::updateOrCreate(
                                ['nip' => $nip, 'semester' => $semester],
                                [$config['col'] => $beban]
                            );
                            $count++;
                        }

                        $this->info("  ✓ Berhasil menarik $count beban dosen untuk {$config['label']}");
                        $overallCount += $count;
                        continue;
                    }
                }

                $this->warn("  ⚠ Gagal menarik {$config['label']}: Status {$response->status} - " . ($response->message ?: 'Cloudflare challenge'));
            } catch (\Throwable $e) {
                $this->error("  ✕ Error pada {$config['label']}: " . $e->getMessage());
            }
        }

        $this->info("\n✓ Proses sinkronisasi selesai! Total record terproses: $overallCount.");
        return self::SUCCESS;
    }

    private function importJsonFile(string $source, string $colName, string $semester, ?string $specificNip): void
    {
        $jsonContent = file_exists($source) ? file_get_contents($source) : $source;
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->error("Format JSON tidak valid untuk: " . substr($source, 0, 80));
            return;
        }

        $fileSemester = (string) ($data['kode_semester'] ?? $semester);

        $items = $data['data'] ?? $data;
        if (!is_array($items)) {
            $this->error("Array data tidak ditemukan dalam JSON.");
            return;
        }

        $bebanByNip = $this->aggregateBebanByNip($items);
        $count = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($bebanByNip, $specificNip, $fileSemester, $colName, &$count) {
            foreach ($bebanByNip as $nip => $beban) {
                if ($specificNip && $nip !== $specificNip) continue;

                DosenSipp::updateOrCreate(
                    ['nip' => $nip, 'semester' => $fileSemester],
                    [$colName => $beban]
                );
                $count++;
            }
        });

        $this->info("✓ Berhasil mengimpor $count dosen untuk kolom '$colName' (semester $fileSemester)");
    }

    private function aggregateBebanByNip(array $items): array
    {
        $bebanByNip = [];

        foreach ($items as $item) {
            $nip = trim((string)($item['nip'] ?? $item['nip_dosen'] ?? $item['nidn'] ?? ''));
            if (empty($nip)) continue;

            $itemBeban = null;
            foreach (['beban', 'total_beban', 'jumlah_beban', 'sks', 'bobot', 'total', 'jumlah'] as $field) {
                if (isset($item[$field]) && is_numeric($item[$field])) {
                    $itemBeban = (int)$item[$field];
                    break;
                }
            }

            $bebanByNip[$nip] = ($bebanByNip[$nip] ?? 0) + ($itemBeban !== null ? $itemBeban : 1);
        }

        return $bebanByNip;
    }
}
