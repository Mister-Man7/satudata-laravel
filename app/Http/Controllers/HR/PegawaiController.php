<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\DTO\ApiResponse;
use App\Services\Integrations\SimpegPegawaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PegawaiController extends Controller
{
    public function __construct(public SimpegPegawaiService $pegawaiService)
    {
    }

    private const STATUS_KERJA = [1, 2, 3, 4, 5, 6, 7, 8, 19, 20];

    private const STATUS_PEGAWAI = [1, 2, 3, 4, 5, 6, 7, 19, 20];

    private const LEVEL_PEGAWAI = [2, 3, 7, 13];

    private const JABATAN = [44];

    public function index(Request $request): View
    {
        $response = $this->pegawaiService->getData();
        $allPegawai = collect($response->data ?? []);

        $statusPegawai = $this->buildStatusCards($allPegawai);
        $datas = $this->buildWorkStatusCards($allPegawai);
        $levelPegawai = $this->buildLevelCards($allPegawai);

        $guruBesar = $allPegawai->filter(function ($p) {
            $j = $p['jabatan'] ?? '';
            $n = $p['namaPegawai'] ?? $p['nama'] ?? '';
            $g = $p['gelarDepan'] ?? '';
            return stripos($j, 'Profesor') !== false || stripos($j, 'Guru Besar') !== false || stripos($n, 'Prof.') !== false || stripos($g, 'Prof') !== false;
        })->count();

        $countsLevel = $allPegawai->groupBy(fn($p) => trim($p['levelPegawai'] ?? 'Lainnya'))->map->count();

        $daftarStatistik = [
            [
                'title' => 'Pegawai Aktif',
                'value' => $allPegawai->count(),
                'href' => null,
                'iconClass' => 'fa-solid fa-users text-indigo-600',
                'badgeText' => 'Total SDM',
                'badgeColor' => 'bg-indigo-600',
                'footerText' => 'Seluruh Pegawai UNTIRTA',
            ],
            [
                'title' => 'Guru Besar / Profesor',
                'value' => $guruBesar,
                'href' => null,
                'iconClass' => 'fa-solid fa-award text-amber-500',
                'badgeText' => 'Akademik',
                'badgeColor' => 'bg-amber-500',
                'footerText' => 'Fakultas & Pascasarjana',
            ],
            [
                'title' => 'Dosen (Tenaga Pengajar)',
                'value' => ($countsLevel->get('Dosen', 0)) + ($countsLevel->get('Dosen DT', 0)) + ($countsLevel->get('Dosen Luar Biasa', 0)) + ($countsLevel->get('Dosen LB', 0)),
                'href' => null,
                'iconClass' => 'fa-solid fa-chalkboard-user text-blue-600',
                'badgeText' => 'Tenaga Pengajar',
                'badgeColor' => 'bg-blue-600',
                'footerText' => 'Dosen PNS, PPPK & DT',
            ],
            [
                'title' => 'Tenaga Kependidikan',
                'value' => ($countsLevel->get('Tenaga Kependidikan', 0)) + ($countsLevel->get('Tendik', 0)),
                'href' => null,
                'iconClass' => 'fa-solid fa-users-gear text-emerald-600',
                'badgeText' => 'Tendik',
                'badgeColor' => 'bg-emerald-600',
                'footerText' => 'Staf & Layanan Kampus',
            ],
        ];

        // Siapkan data untuk 3 chart
        $chartStatusPegawai = $this->buildChartData($statusPegawai);
        $chartStatusKerja = $this->buildChartData($datas);
        $chartLevelPegawai = $this->buildChartData($levelPegawai);

        return view('HR.pegawai', compact(
            'daftarStatistik',
            'statusPegawai',
            'datas',
            'levelPegawai',
            'guruBesar',
            'chartStatusPegawai',
            'chartStatusKerja',
            'chartLevelPegawai'
        ));
    }

    /**
     * Build data untuk chart dari array cards
     */
    private function buildChartData(array $cards): array
    {
        $labels = [];
        $data = [];
        $total = 0;

        foreach ($cards as $card) {
            $label = $card['label'] ?? '-';
            $value = (int) ($card['value'] ?? 0);

            if ($value > 0) {
                $labels[] = $label;
                $data[] = $value;
                $total += $value;
            }
        }

        // Fallback if all values are 0
        if (empty($data) && !empty($cards)) {
            foreach ($cards as $card) {
                $labels[] = $card['label'] ?? '-';
                $data[] = (int) ($card['value'] ?? 0);
                $total += (int) ($card['value'] ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
        ];
    }


    private function buildStatusCards($allPegawai): array
    {
        $counts = $allPegawai->groupBy(function ($p) {
            $sp = trim($p['statusPegawai'] ?? '');
            if (!empty($sp) && $sp !== 'Aktif') {
                return $sp;
            }
            return trim($p['statusKerja'] ?? 'PNS');
        })->map->count();

        $cards = [];
        foreach ($counts as $label => $count) {
            $cards[] = $this->makeStatCard($label, $count);
        }

        return $cards;
    }


    private function buildWorkStatusCards($allPegawai): array
    {
        $counts = $allPegawai->groupBy(fn($p) => trim($p['statusKerja'] ?? 'Lainnya'))->map->count();

        return [
            $this->makeWorkStatusCard('PNS', $counts->get('PNS', 0)),
            $this->makeWorkStatusCard('PPPK', $counts->get('PPPK', 0)),
            $this->makeWorkStatusCard('Honorer', $counts->get('Honorer', 0)),
            $this->makeWorkStatusCard('BLU', $counts->get('BLU', 0)),
            $this->makeWorkStatusCard('PKWT', $counts->get('PKWT', 0)),
            $this->makeWorkStatusCard('PPPK Paruh Waktu', $counts->get('PPPK Paruh Waktu', 0)),
            $this->makeWorkStatusCard('Outsourcing', $counts->get('Outsourcing', 0)),
            $this->makeWorkStatusCard('Non BLU', $counts->get('Non BLU', 0)),
            $this->makeWorkStatusCard('CPNS', $counts->get('CPNS', 0)),
        ];
    }

    private function buildLevelCards($allPegawai): array
    {
        $counts = $allPegawai->groupBy(fn($p) => trim($p['levelPegawai'] ?? 'Lainnya'))->map->count();

        $tendik = $counts->get('Tenaga Kependidikan', 0) + $counts->get('Tendik', 0);
        $dosen = $counts->get('Dosen', 0);
        $dosenDt = $counts->get('Dosen DT', 0);
        $dosenLb = $counts->get('Dosen Luar Biasa', 0) + $counts->get('Dosen LB', 0);

        return [
            $this->makeLevelCard('Tendik', $tendik),
            $this->makeLevelCard('Dosen', $dosen),
            $this->makeLevelCard('Dosen DT', $dosenDt),
            $this->makeLevelCard('Dosen Luar Biasa', $dosenLb),
        ];
    }


    private function makeStatCard(string $title, int $value): array
    {
        $style = $this->styleStatusPegawai($title);

        return [
            'label' => $title,
            'value' => $value,
            'span' => $style['span'],
            'bg' => $style['bg'],
            'icon' => $style['icon'],
            'text' => $style['text'],
            'iconBg' => $style['iconBg'],
            'iconColor' => $style['iconColor'],
        ];
    }

    private function makeWorkStatusCard(string $title, int $value): array
    {
        return [
            'label' => $title,
            'value' => $value,
            'bg' => 'bg-[#4F46E5]',
            'iconBg' => 'bg-white',
            'iconColor' => 'text-indigo-700',
            'icon' => match ($title) {
                'CPNS' => 'fa-solid fa-user-clock',
                'PNS' => 'fa-solid fa-id-card',
                'BLU' => 'fa-solid fa-building-columns',
                'Honorer' => 'fa-solid fa-user-tie',
                'Outsourcing' => 'fa-solid fa-users-gear',
                'PKWT' => 'fa-solid fa-file-signature',
                'PPPK' => 'fa-solid fa-user-check',
                'Non BLU' => 'fa-solid fa-user-slash',
                'PPPK Paruh Waktu' => 'fa-solid fa-user-pen',
                default => 'fa-solid fa-users',
            },
        ];
    }

    private function makeLevelCard(string $title, int $value): array
    {
        $styles = [
            'Tendik' => [
                'bg' => 'bg-emerald-600',
                'iconBg' => 'bg-emerald-100',
                'iconColor' => 'text-emerald-700',
                'icon' => 'fa-solid fa-users-gear',
                'textColor' => 'text-white',
            ],
            'Dosen' => [
                'bg' => 'bg-blue-600',
                'iconBg' => 'bg-blue-100',
                'iconColor' => 'text-blue-700',
                'icon' => 'fa-solid fa-chalkboard-user',
                'textColor' => 'text-white',
            ],
            'Dosen DT' => [
                'bg' => 'bg-indigo-600',
                'iconBg' => 'bg-indigo-100',
                'iconColor' => 'text-indigo-700',
                'icon' => 'fa-solid fa-user-tie',
                'textColor' => 'text-white',
            ],
            'Dosen Luar Biasa' => [
                'bg' => 'bg-purple-600',
                'iconBg' => 'bg-purple-100',
                'iconColor' => 'text-purple-700',
                'icon' => 'fa-solid fa-briefcase',
                'textColor' => 'text-white',
            ],
        ];

        $style = $styles[$title] ?? [
            'bg' => 'bg-slate-600',
            'iconBg' => 'bg-slate-100',
            'iconColor' => 'text-slate-700',
            'icon' => 'fa-solid fa-user-graduate',
            'textColor' => 'text-white',
        ];

        return [
            'label' => $title,
            'value' => $value,
            'bg' => $style['bg'],
            'iconBg' => $style['iconBg'],
            'iconColor' => $style['iconColor'],
            'icon' => $style['icon'],
            'textColor' => $style['textColor'],
        ];
    }


    public function getApiData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kodeData' => ['nullable', 'string', 'max:50'],
            'nip' => ['nullable', 'string', 'max:50'],
            'count' => ['nullable', 'integer', 'in:1'],
            'status_pegawai' => ['nullable', 'integer', 'in:' . implode(',', self::STATUS_PEGAWAI)],
            'status_kerja' => ['nullable', 'integer', 'in:' . implode(',', self::STATUS_KERJA)],
            'level_pegawai' => ['nullable', 'integer', 'in:' . implode(',', self::LEVEL_PEGAWAI)],
            'jabatan' => ['nullable', 'integer', 'in:' . implode(',', self::JABATAN)],
        ]);

        $parameter = array_filter([
            'kodeData' => $validated['kodeData'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'count' => $validated['count'] ?? 1,
            'status_pegawai' => $validated['status_pegawai'] ?? 1,
            'status_kerja' => $validated['status_kerja'] ?? null,
            'level_pegawai' => $validated['level_pegawai'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');

        $response = $this->pegawaiService->getData($parameter);

        return response()->json([
            'success' => $response->success,
            'status' => $response->success,
            'total' => $response->data['total'] ?? 0,
            'data' => $response->data['data'] ?? $response->data ?? [],
            'message' => $response->message,
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'status_pegawai' => ['nullable', 'integer', 'in:' . implode(',', self::STATUS_PEGAWAI)],
            'status_kerja' => ['nullable', 'integer', 'in:' . implode(',', self::STATUS_KERJA)],
            'level_pegawai' => ['nullable', 'integer', 'in:' . implode(',', self::LEVEL_PEGAWAI)],
            'jabatan' => ['nullable', 'integer', 'in:' . implode(',', self::JABATAN)],
        ]);
    }

    private function parameterUtama(array $validated): array
    {
        return array_filter([
            'count' => 1,
            'status_pegawai' => $validated['status_pegawai'] ?? 1,
            'status_kerja' => $validated['status_kerja'] ?? null,
            'level_pegawai' => $validated['level_pegawai'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');
    }

    private function parameterStatusKerja(int $statusKerja): array
    {
        return [
            'count' => 1,
            'status_kerja' => $statusKerja,
        ];
    }

    private function styleStatusPegawai(string $title): array
    {
        $styles = [
            'Aktif' => [
                'span' => 'lg:col-span-6 lg:row-span-2',
                'bg' => 'bg-gradient-to-br from-violet-700 via-purple-600 to-fuchsia-500',
                'icon' => 'fa-solid fa-circle-check',
                'text' => 'text-white',
                'iconBg' => 'bg-violet-100',
                'iconColor' => 'text-violet-700'
            ],

            'Pensiun' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-slate-800',
                'icon' => 'fa-solid fa-user-clock',
                'text' => 'text-white',
                'iconBg' => 'bg-slate-100',
                'iconColor' => 'text-slate-700',
            ],

            'Meninggal' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-emerald-600',
                'icon' => 'fa-solid fa-heart-crack',
                'text' => 'text-white',
                'iconBg' => 'bg-emerald-100',
                'iconColor' => 'text-emerald-700',
            ],

            'Mutasi/Resign' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-purple-600',
                'icon' => 'fa-solid fa-right-left',
                'text' => 'text-white',
                'iconBg' => 'bg-white/15',
                'iconColor' => 'text-white',
            ],

            'Alih Status' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-amber-400',
                'icon' => 'fa-solid fa-layer-group',
                'text' => 'text-black',
                'iconBg' => 'bg-white/15',
                'iconColor' => 'text-white',
            ],

            'Cuti' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-lime-400',
                'icon' => 'fa-solid fa-umbrella-beach',
                'text' => 'text-black',
                'iconBg' => 'bg-lime-100',
                'iconColor' => 'text-lime-700',

            ],

            'Tugas Belajar' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-sky-500',
                'icon' => 'fa-solid fa-graduation-cap',
                'text' => 'text-white',
                'iconBg' => 'bg-white/15',
                'iconColor' => 'text-white',
            ],

            'Penugasan' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-pink-500',
                'icon' => 'fa-solid fa-briefcase',
                'text' => 'text-white',
                'iconBg' => 'bg-white/15',
                'iconColor' => 'text-white',
            ],

            'Tugas Belajar Mandiri' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-indigo-600',
                'icon' => 'fa-solid fa-book-open-reader',
                'text' => 'text-white',
                'iconBg' => 'bg-white/15',
                'iconColor' => 'text-white',
            ],
        ];

        return $styles[$title] ?? [
            'span' => 'lg:col-span-3',
            'bg' => 'bg-violet-600',
            'icon' => 'fa-solid fa-users',
            'text' => 'text-white',
            'iconBg' => 'bg-violet-100',
            'iconColor' => 'text-violet-700',
        ];
    }

    private function parameterStatusPegawai(int $statusPegawai): array
    {
        return [
            'count' => 1,
            'status_pegawai' => $statusPegawai,
        ];
    }

    private function statistikPegawai(
        string $title,
        ApiResponse $hasilApi
    ): array
    {
        $style = $this->styleStatusPegawai($title);

        return [
            'label' => $title,
            'value' => $this->extractTotal($hasilApi),
            'span' => $style['span'],
            'bg' => $style['bg'],
            'icon' => $style['icon'],
            'text' => $style['text'],
            'iconBg' => $style['iconBg'],
            'iconColor' => $style['iconColor'],
        ];
    }

    private function formatCard(string $title, ApiResponse $hasilApi): array
    {
        $styles = [
            'Tendik' => [
                'bg' => 'bg-emerald-600',
                'iconBg' => 'bg-emerald-100',
                'iconColor' => 'text-emerald-700',
                'icon' => 'fa-solid fa-users-gear',
                'textColor' => 'text-white',
            ],
            'Dosen' => [
                'bg' => 'bg-blue-600',
                'iconBg' => 'bg-blue-100',
                'iconColor' => 'text-blue-700',
                'icon' => 'fa-solid fa-chalkboard-user',
                'textColor' => 'text-white',
            ],
            'Dosen DT' => [
                'bg' => 'bg-indigo-600',
                'iconBg' => 'bg-indigo-100',
                'iconColor' => 'text-indigo-700',
                'icon' => 'fa-solid fa-user-tie',
                'textColor' => 'text-white',
            ],
            'Dosen Luar Biasa' => [
                'bg' => 'bg-purple-600',
                'iconBg' => 'bg-purple-100',
                'iconColor' => 'text-purple-700',
                'icon' => 'fa-solid fa-briefcase',
                'textColor' => 'text-white',
            ],
        ];

        $style = $styles[$title] ?? [
            'bg' => 'bg-slate-600',
            'iconBg' => 'bg-slate-100',
            'iconColor' => 'text-slate-700',
            'icon' => 'fa-solid fa-user-graduate',
            'textColor' => 'text-white',
        ];

        return [
            'label' => $title,
            'value' => $this->extractTotal($hasilApi),
            'bg' => $style['bg'],
            'iconBg' => $style['iconBg'],
            'iconColor' => $style['iconColor'],
            'icon' => $style['icon'],
            'textColor' => $style['textColor'],
        ];
    }

    public function getByNip(string $nip): JsonResponse
    {
        $response = $this->pegawaiService->getData([
            'nip' => $nip
        ]);

        return response()->json([
            'success' => $response->success,
            'status' => $response->success,
            'total' => is_array($response->data) ? count($response->data) : 0,
            'data' => $response->data ?? [],
            'message' => $response->message,
        ]);
    }

    /**
     * Extract total from ApiResponse data
     */
    private function extractTotal(ApiResponse $response): int
    {
        $data = $response->data ?? [];
        
        if (is_array($data) && isset($data['total'])) {
            return (int) $data['total'];
        } elseif (is_object($data) && property_exists($data, 'total')) {
            return (int) $data->total;
        }
        
        return 0;
    }
}
