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
                            {--import-dir= : Path folder berisi file-file JSON hasil response SIPP}
                            {--import-portofolio= : Path file/folder/string JSON portofolio (mis. hasil Postman atau ekspor Collection Runner) untuk kolom JSON dosen_sipps}
                            {--portofolio : Simpan daftar publikasi, penelitian, dan pengabdian ke kolom JSON dosen_sipps}
                            {--limit=25 : Jumlah dosen maksimum pada mode --portofolio}
                            {--halaman=5 : Jumlah halaman maksimum per jenis portofolio}';

    protected $description = 'Sinkronisasi seluruh 7 data kinerja luaran ilmiah SIPP dari API / JSON ke Database MySQL';

    public function handle(SIPPService $sippService): int
    {
        $semester = (string) ($this->option('semester') ?: '20252');
        $specificNip = $this->option('nip');
        $specificType = $this->option('type');
        $importPayload = $this->option('import');
        $importDir = $this->option('import-dir');
        $importPortofolio = $this->option('import-portofolio');

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

        // 2b. Mode Import Portofolio Tri Dharma dari JSON (hasil /api/publikasi|penelitian|pengabdian)
        if (!empty($importPortofolio)) {
            $this->importPortofolioBulk($importPortofolio, $specificType, $semester, $specificNip);
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

        if ($this->option('portofolio')) {
            $overallCount += $this->syncPortofolio($sippService, $semester, $specificNip);
        }

        $this->info("\n✓ Proses sinkronisasi selesai! Total record terproses: $overallCount.");
        return self::SUCCESS;
    }

    /**
     * Simpan daftar portofolio Tri Dharma (publikasi, penelitian, pengabdian) ke kolom
     * JSON `dosen_sipps`, supaya halaman profil dosen tetap terisi walau API SIPP
     * sedang tidak dapat diakses. Jumlah dosen dan halaman dibatasi opsi --limit
     * dan --halaman karena setiap dosen memerlukan beberapa permintaan API.
     */
    private function syncPortofolio(SIPPService $sippService, string $semester, ?string $specificNip): int
    {
        $limit = (int) ($this->option('limit') ?: 25);
        $maxPages = (int) ($this->option('halaman') ?: 5);

        $nipList = $specificNip
            ? collect([$specificNip])
            : DosenSipp::query()->where('semester', $semester)->distinct()->pluck('nip');

        $processedCount = min($limit, $nipList->count());
        $this->line("→ Sinkronisasi portofolio Tri Dharma untuk {$processedCount} dosen (semester {$semester})...");

        $type = [
            'publikasi' => 'getPublikasi',
            'penelitian' => 'getPenelitian',
            'pengabdian' => 'getPengabdian',
        ];

        $saved = 0;

        foreach ($nipList->take($limit) as $nip) {
            $kolom = [];

            foreach ($type as $columnName => $method) {
                $items = $this->fetchAllPortofolio($sippService, $method, (string) $nip, $maxPages);

                if (!empty($items)) {
                    $kolom[$columnName] = $items;
                }
            }

            if (empty($kolom)) {
                $this->warn("  ⚠ {$nip}: tidak ada portofolio yang bisa diambil");
                continue;
            }

            DosenSipp::updateOrCreate(
                ['nip' => (string) $nip, 'semester' => $semester],
                $kolom
            );

            $saved++;

            $summaryan = [];
            foreach ($kolom as $columnName => $items) {
                $summaryan[] = $columnName . '=' . count($items);
            }
            $this->line('  ✓ ' . $nip . ': ' . implode(', ', $summaryan));
        }

        $this->info("  Portofolio tersimpan untuk {$saved} dosen.");

        return $saved;
    }

    /**
     * Ambil seluruh halaman portofolio satu dosen dari API SIPP.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllPortofolio(SIPPService $sippService, string $method, string $nip, int $maxPages): array
    {
        $items = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            try {
                $response = $sippService->{$method}(['nip' => $nip, 'page' => $page, 'per_page' => 25]);
            } catch (\Throwable $e) {
                Log::warning("Portofolio {$method} {$nip} halaman {$page} gagal: " . $e->getMessage());
                break;
            }

            if (!$response->success || empty($response->data)) {
                break;
            }

            $raw = is_array($response->data) ? $response->data : [];
            $list = isset($raw['data']) && is_array($raw['data']) ? $raw['data'] : $raw;

            if (empty($list)) {
                break;
            }

            $items = array_merge($items, $list);

            // Info pagination SIPP ada di root body (di luar `data`), mis.
            // {"total":26,"pagination":{"current_page":1,"per_page":25,"last_page":2},"data":[...]}
            $rawBody = is_array($response->rawBody) ? $response->rawBody : [];

            // Hentikan loop bila server menyatakan ini halaman terakhir, atau
            // halaman ini tidak penuh (berarti sudah tidak ada halaman berikutnya).
            $lastProcessedPage = (int) ($rawBody['pagination']['last_page']
                ?? $rawBody['meta']['last_page']
                ?? $rawBody['last_page']
                ?? $raw['pagination']['last_page']
                ?? 0);

            if ($lastProcessedPage > 0 && $page >= $lastProcessedPage) {
                break;
            }

            if (count($list) < 25) {
                break;
            }
        }

        return $items;
    }

    /**
     * Impor portofolio Tri Dharma (publikasi/penelitian/pengabdian) ke kolom JSON
     * `dosen_sipps` secara massal tanpa menghubungi API SIPP. Sumber bisa berupa satu file
     * JSON, satu folder berisi banyak file JSON, string JSON, atau hasil ekspor Collection
     * Runner Postman (`executions[].response.body`).
     *
     * Setiap item diberikan kepada NIP pada field `nip` payload (bila ada) DAN seluruh dosen
     * yang muncul di `anggota[]` bertipe internal atau ber-identitas NIP 18 digit. Jadi satu
     * unduhan mengisi pemilik sekaligus rekan penulisnya, sedangkan mahasiswa (identitas
     * 10 digit) tidak ikut. Item lama tidak diduplikasi (kunci id_portofolio).
     */
    private function importPortofolioBulk(string $source, ?string $type, string $semester, ?string $specificNip): void
    {
        $sourceFiles = is_dir($source)
            ? array_values(glob(rtrim($source, '/\\') . '/*.json') ?: [])
            : [$source];

        if (empty($sourceFiles)) {
            $this->error('Tidak ada file .json pada: ' . $source);
            return;
        }

        $map = [];        // nip => semester => jenis => [kunci item => item]
        $itemCount = 0;
        $itemTanpaNip = 0;

        foreach ($sourceFiles as $sourceFile) {
            foreach ($this->readPortofolioPayload($sourceFile) as $data) {
                $paket = $this->buildPortofolioItem($data, $type, $sourceFile);

                if ($paket === null) {
                    continue;
                }

                $nipPayload = trim((string) ($data['nip'] ?? $specificNip ?? ''));

                foreach ($paket as $typeName => $items) {
                    foreach ($items as $item) {
                        if (!is_array($item)) {
                            continue;
                        }

                        $itemCount++;
                        $itemSemester = (string) (($item['kode_semester'] ?? null) ?: $semester);
                        $itemKey = $this->portofolioKey($item);
                        $nipList = array_values(array_unique(array_merge(
                            $nipPayload !== '' ? [$nipPayload] : [],
                            $this->nipFromMembers($item)
                        )));

                        if (empty($nipList)) {
                            $itemTanpaNip++;
                            continue;
                        }

                        foreach ($nipList as $nip) {
                            $map[$nip][$itemSemester][$typeName][$itemKey] = $item;
                        }
                    }
                }
            }
        }

        if (empty($map)) {
            $this->error('Tidak ada item portofolio yang bisa dibaca dari: ' . $source);
            return;
        }

        $dosenLines = 0;
        $penugasan = 0;

        foreach ($map as $nip => $perSemester) {
            foreach ($perSemester as $semesterData => $perJenis) {
                $kolom = [];

                foreach ($perJenis as $typeName => $items) {
                    $lama = DosenSipp::where('nip', $nip)->where('semester', $semesterData)->value($typeName);
                    $merged = [];

                    foreach (is_array($lama) ? $lama : [] as $itemLama) {
                        if (is_array($itemLama)) {
                            $merged[$this->portofolioKey($itemLama)] = $itemLama;
                        }
                    }

                    foreach ($items as $itemKey => $item) {
                        $merged[$itemKey] = $item;
                    }

                    $kolom[$typeName] = array_values($merged);
                    $penugasan += count($items);
                }

                try {
                    DosenSipp::updateOrCreate(['nip' => $nip, 'semester' => $semesterData], $kolom);
                    $dosenLines++;
                } catch (\Throwable $e) {
                    $this->warn("  ⚠ Gagal simpan {$nip}: " . $e->getMessage());
                }
            }
        }

        $this->info('✓ Portofolio dari ' . count($sourceFiles) . ' sumber: ' . $itemCount . ' item dibaca, '
            . $penugasan . ' penugasan ditulis ke ' . $dosenLines . ' baris dosen.'
            . ($itemTanpaNip > 0 ? " ({$itemTanpaNip} item dilewati karena tanpa NIP)" : ''));
    }

    /**
     * Baca satu sumber JSON menjadi daftar payload. Mendukung ekspor Collection Runner
     * Postman, yang membungkus tiap respons pada `executions[].response.body`.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readPortofolioPayload(string $payloadSource): array
    {
        $content = file_exists($payloadSource) ? file_get_contents($payloadSource) : $payloadSource;
        $data = json_decode((string) $content, true);

        if (!is_array($data)) {
            $this->warn('  ⚠ JSON tidak valid: ' . substr($payloadSource, 0, 80));
            return [];
        }

        if (isset($data['executions']) && is_array($data['executions'])) {
            $payload = [];

            foreach ($data['executions'] as $eksekusi) {
                $body = $eksekusi['response']['body'] ?? null;
                $terurai = is_string($body) ? json_decode($body, true) : null;

                if (is_array($terurai)) {
                    $payload[] = $terurai;
                }
            }

            if (empty($payload)) {
                $this->warn('  ⚠ Ekspor Runner tanpa body respons yang bisa dibaca: ' . basename($payloadSource));
            }

            return $payload;
        }

        return [$data];
    }

    /**
     * Tentukan jenis portofolio dan daftar item dari satu payload.
     *
     * @return array<string, array<int, mixed>>|null
     */
    private function buildPortofolioItem(array $data, ?string $type, string $payloadSource): ?array
    {
        $validTypes = ['publikasi', 'penelitian', 'pengabdian'];
        $paket = [];

        foreach ($validTypes as $typeName) {
            if (isset($data[$typeName]) && is_array($data[$typeName])) {
                $paket[$typeName] = array_values($data[$typeName]);
            }
        }

        if (!empty($paket)) {
            return $paket;
        }

        $typeName = strtolower((string) ($type ?: pathinfo($payloadSource, PATHINFO_FILENAME)));

        if (!in_array($typeName, $validTypes, true)) {
            $this->warn('  ⚠ Jenis tidak dikenali pada ' . basename($payloadSource) . '; tentukan dengan --type=publikasi|penelitian|pengabdian');
            return null;
        }

        $items = $data['data'] ?? $data;

        if (!is_array($items)) {
            return null;
        }

        return [$typeName => array_values($items)];
    }

    /**
     * Kunci unik satu item portofolio untuk mencegah duplikasi saat impor diulang.
     */
    private function portofolioKey(array $item): string
    {
        $itemKey = $item['id_portofolio'] ?? $item['id'] ?? $item['judul_portofolio'] ?? null;

        return (string) ($itemKey ?? md5((string) json_encode($item)));
    }

    /**
     * Daftar NIP dosen yang tercatat sebagai anggota internal pada satu item portofolio.
     *
     * @return array<int, string>
     */
    private function nipFromMembers(array $item): array
    {
        $result = [];

        foreach (($item['anggota'] ?? []) as $anggota) {
            if (!is_array($anggota)) {
                continue;
            }

            $identitas = trim((string) ($anggota['identitas'] ?? ''));

            if ($identitas === '') {
                continue;
            }

            $tipe = strtolower((string) ($anggota['tipe'] ?? ''));

            if ($tipe === 'internal' || preg_match('/^\d{18}$/', $identitas) === 1) {
                $result[] = $identitas;
            }
        }

        return array_values(array_unique($result));
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
            foreach (['beban', 'total_beban', 'jumlah_beban', 'sks', 'bobot', 'total', 'count'] as $field) {
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
