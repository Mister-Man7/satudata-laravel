<?php

namespace App\Services\Sync;

use App\Models\Aset;
use App\Services\Integrations\ChromiumFetcher;
use App\Services\Integrations\LoginChromium;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi data BMN (aset) dari API SIMANTAP ke tabel `asets`.
 *
 * API SIMANTAP menolak request PHP/cURL (Cloudflare challenge), jadi pemanggilannya dilakukan
 * Chromium lokal — pola yang sama dengan penarikan data SIPP/SIAKANG. Setiap halaman di-upsert
 * berdasarkan `id_bmn` (kolom unik di tabel `asets`).
 */
class AsetSyncService
{
    public function __construct(
        private ?LoginChromium $login = null,
        private ?ChromiumFetcher $fetcher = null,
    ) {}

    /**
     * Sinkronkan satu halaman BMN dari API SIMANTAP.
     *
     * @param  array{page?: int, per_page?: int}  $params
     * @return array{status: bool, message: string, received: int, meta: array<string, mixed>}
     */
    public function sync(array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, (int) ($params['per_page'] ?? 100));

        $token = ($this->login ?? app(LoginChromium::class))->loginSimantap();

        if ($token === null) {
            return [
                'status' => false,
                'message' => 'Login SIMANTAP gagal (butuh Edge/Chrome di host aplikasi).',
                'received' => 0,
                'meta' => [],
            ];
        }

        $result = $this->fetchPage($token, $page, $perPage);

        if ($result === null) {
            return [
                'status' => false,
                'message' => 'Gagal menjalankan Chromium untuk mengambil data BMN.',
                'received' => 0,
                'meta' => [],
            ];
        }

        if (empty($result['ok'])) {
            return [
                'status' => false,
                'message' => (string) ($result['message'] ?? 'Respons API tidak dikenali.'),
                'received' => 0,
                'meta' => is_array($result['meta'] ?? null) ? $result['meta'] : [],
            ];
        }

        $saved = $this->savePage($result['items'] ?? []);

        return [
            'status' => true,
            'message' => 'OK',
            'received' => $saved,
            'meta' => is_array($result['meta'] ?? null) ? $result['meta'] : [],
        ];
    }

    /**
     * Petakan satu item API SIMANTAP menjadi kolom tabel `asets`.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function mapItem(array $item): array
    {
        $type = is_array($item['jenis_barang'] ?? null) ? $item['jenis_barang'] : [];
        $code = is_array($item['kode_barang'] ?? null) ? $item['kode_barang'] : [];
        $campus = is_array($item['kampus'] ?? null) ? $item['kampus'] : [];
        $building = is_array($item['gedung'] ?? null) ? $item['gedung'] : [];
        $room = is_array($item['ruangan'] ?? null) ? $item['ruangan'] : [];

        return [
            'id_bmn' => (string) ($item['id_bmn'] ?? ''),
            'id_satker' => $item['id_satker'] ?? null,
            'id_kampus' => $item['id_kampus'] ?? ($campus['id_kampus'] ?? null),
            'id_gedung' => $item['id_gedung'] ?? ($building['id_gedung'] ?? null),
            'id_lantai_gedung' => $item['id_lantai_gedung'] ?? null,
            'id_ruangan' => $item['id_ruangan'] ?? ($room['id_ruangan'] ?? null),
            'id_jenis_barang' => $item['id_jenis_barang'] ?? ($type['id_jenis_barang'] ?? null),
            'nama_jenis_barang' => $type['nama_jenis_barang'] ?? ($item['nama_jenis_barang'] ?? null),
            'id_kode_barang' => $item['id_kode_barang'] ?? ($code['id_kode_barang'] ?? null),
            'nama_kode_barang' => $code['nama_kode_barang'] ?? ($item['nama_kode_barang'] ?? null),
            'nup' => $item['nup'] ?? null,
            'merk' => $item['merk'] ?? null,
            'tipe' => $item['tipe'] ?? null,
            'tgl_perolehan' => $item['tgl_perolehan'] ?? null,
            'kondisi' => $item['kondisi'] ?? null,
            'kondisi_text' => $item['kondisi_text'] ?? null,
            'intra_ekstra' => $item['intra_ekstra'] ?? null,
            'status_sewa' => (int) ($item['status_sewa'] ?? 0),
            'nilai_perolehan' => (float) ($item['nilai_perolehan'] ?? 0),
            'nilai_buku' => (float) ($item['nilai_buku'] ?? 0),
            'lokasi_lengkap' => $this->fullLocation($item, $campus, $building, $room),
            'umur_barang' => $item['umur_barang'] ?? null,
            'payload' => $item,
        ];
    }

    /**
     * Simpan satu halaman hasil API ke tabel `asets` (upsert berdasarkan id_bmn).
     *
     * @param  array<int, mixed>  $items
     */
    public function savePage(array $items): int
    {
        $count = 0;

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['id_bmn'])) {
                continue;
            }

            $row = $this->mapItem($item);

            try {
                Aset::updateOrCreate(['id_bmn' => $row['id_bmn']], $row);
                $count++;
            } catch (\Throwable $e) {
                Log::warning('Gagal upsert aset ' . $row['id_bmn'] . ': ' . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Lokasi lengkap untuk kolom `lokasi_lengkap` (format "Kampus - Gedung - Ruangan").
     * Bila API mengirim '-' namun nama relasinya tersedia, lokasi disusun dari relasi itu.
     *
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $campus
     * @param  array<string, mixed>  $building
     * @param  array<string, mixed>  $room
     */
    private function fullLocation(array $item, array $campus, array $building, array $room): string
    {
        $location = trim((string) ($item['lokasi_lengkap'] ?? ''));

        if ($location !== '' && $location !== '-') {
            return $location;
        }

        $parts = array_values(array_filter([
            $campus['nama_kampus'] ?? $campus['nama'] ?? null,
            $building['nama_gedung'] ?? null,
            $room['nama_ruangan'] ?? null,
        ], static fn ($part) => is_string($part) && trim($part) !== ''));

        return empty($parts) ? '-' : implode(' - ', array_map('trim', $parts));
    }

    /**
     * Ambil satu halaman BMN dari API SIMANTAP lewat Chromium.
     *
     * @return array{ok: bool, message: string, items: array<int, array<string, mixed>>, meta: array<string, mixed>}|null
     */
    private function fetchPage(string $token, int $page, int $perPage): ?array
    {
        $base = rtrim((string) config('services.simantap.base_url'), '/');
        $userAgent = (string) config('services.simantap.user_agent')
            ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36';

        $js = <<<'JS'
const url = __URL__;
const token = __TOKEN__;

(async () => {
    let result = { ok: false, message: '', items: [], meta: {} };

    try {
        const res = await fetch(url, { headers: { accept: 'application/json', Authorization: 'Bearer ' + token } });
        const text = await res.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { data = null; }

        const content = data?.data ?? null;
        const list = Array.isArray(content) ? content : (Array.isArray(content?.data) ? content.data : []);

        result = {
            ok: res.status === 200,
            message: String(data?.message ?? text).slice(0, 140),
            items: list,
            meta: content?.meta ?? {},
        };
    } catch (e) {
        result = { ok: false, message: e.message, items: [], meta: {} };
    }

    document.getElementById('result').textContent = JSON.stringify(result);
})();
JS;

        $js = str_replace(
            ['__URL__', '__TOKEN__'],
            [
                json_encode($base . '/bmn-all?page=' . $page . '&per_page=' . $perPage),
                json_encode($token),
            ],
            $js
        );

        $fetcher = $this->fetcher ?? app(ChromiumFetcher::class);
        $dump = $fetcher->fetch(
            '<!doctype html><html><body><pre id="result">MENUNGGU</pre><script>' . $js . '</script></body></html>',
            $userAgent,
            120000
        );

        if ($dump === null) {
            return null;
        }

        $result = json_decode($fetcher->fetchResult($dump), true);

        return is_array($result) ? $result : null;
    }
}
