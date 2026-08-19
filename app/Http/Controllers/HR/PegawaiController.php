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
        $statusPegawai = $this->buildStatusCards();
        $datas = $this->buildWorkStatusCards();
        $levelPegawai = $this->buildLevelCards();
        $guruBesarResponse = $this->getQuickCount(['jabatan' => 44]);
        $guruBesar = $this->extractTotal($guruBesarResponse);

        // Siapkan data untuk 3 chart
        $chartStatusPegawai = $this->buildChartData($statusPegawai);
        $chartStatusKerja = $this->buildChartData($datas);
        $chartLevelPegawai = $this->buildChartData($levelPegawai);

        return view('HR.pegawai', compact('statusPegawai', 'datas', 'levelPegawai', 'guruBesar', 'chartStatusPegawai', 'chartStatusKerja', 'chartLevelPegawai'));
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

            $labels[] = $label;
            $data[] = $value;
            $total += $value;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
        ];
    }

    private function buildStatusCards(): array
    {
        $results = $this->pegawaiService->getData($this->parameterStatusPegawai(1));

        return [
            $this->statistikPegawai('Aktif', $results),
            $this->statistikPegawai('Pensiun', $this->getQuickCount(['status_pegawai' => 2])),
            $this->statistikPegawai('Meninggal', $this->getQuickCount(['status_pegawai' => 3])),
            $this->statistikPegawai('Mutasi/Resign', $this->getQuickCount(['status_pegawai' => 4])),
            $this->statistikPegawai('Alih Status', $this->getQuickCount(['status_pegawai' => 5])),
            $this->statistikPegawai('Cuti', $this->getQuickCount(['status_pegawai' => 6])),
            $this->statistikPegawai('Tugas Belajar', $this->getQuickCount(['status_pegawai' => 7])),
            $this->statistikPegawai('Penugasan', $this->getQuickCount(['status_pegawai' => 19])),
            $this->statistikPegawai('Tugas Belajar Mandiri', $this->getQuickCount(['status_pegawai' => 20])),
        ];
    }

    private function buildWorkStatusCards(): array
    {
        return [
            $this->statistikStatusKerja('CPNS', $this->getQuickCount(['status_kerja' => 8])),
            $this->statistikStatusKerja('PNS', $this->getQuickCount(['status_kerja' => 1])),
            $this->statistikStatusKerja('BLU', $this->getQuickCount(['status_kerja' => 2])),
            $this->statistikStatusKerja('Honorer', $this->getQuickCount(['status_kerja' => 3])),
            $this->statistikStatusKerja('Outsourcing', $this->getQuickCount(['status_kerja' => 4])),
            $this->statistikStatusKerja('PKWT', $this->getQuickCount(['status_kerja' => 5])),
            $this->statistikStatusKerja('PPPK', $this->getQuickCount(['status_kerja' => 7])),
            $this->statistikStatusKerja('Non BLU', $this->getQuickCount(['status_kerja' => 19])),
            $this->statistikStatusKerja('PPPK Paruh Waktu', $this->getQuickCount(['status_kerja' => 20])),
        ];
    }

    private function buildLevelCards(): array
    {
        return [
            $this->formatCard('Tendik', $this->getQuickCount(['level_pegawai' => 2])),
            $this->formatCard('Dosen', $this->getQuickCount(['level_pegawai' => 3])),
            $this->formatCard('Dosen DT', $this->getQuickCount(['level_pegawai' => 7])),
            $this->formatCard('Dosen Luar Biasa', $this->getQuickCount(['level_pegawai' => 13])),
        ];
    }

    private function getQuickCount(array $parameters): ApiResponse
    {
        return $this->pegawaiService->getData(array_merge(['count' => 1], $parameters));
    }

    private function statistikStatusKerja(string $title, ApiResponse $hasilApi): array
    {
        return [
            'label' => $title,
            'value' => $this->extractTotal($hasilApi),
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
