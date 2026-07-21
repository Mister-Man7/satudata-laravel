<?php

namespace App\Http\Controllers;

use App\Services\SimpegPegawaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $statusPegawai = [
            $this->statistikPegawai(
                title: 'Aktif',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(1)),
            ),
            $this->statistikPegawai(
                title: 'Pensiun',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(2)),
            ),
            $this->statistikPegawai(
                title: 'Meninggal',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(3)),
            ),
            $this->statistikPegawai(
                title: 'Mutasi/Resign',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(4)),
            ),
            $this->statistikPegawai(
                title: 'Alih Status',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(5)),
            ),
            $this->statistikPegawai(
                title: 'Cuti',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(6)),
            ),
            $this->statistikPegawai(
                title: 'Tugas Belajar',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(7)),
            ),
            $this->statistikPegawai(
                title: 'Penugasan',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(19)),
            ),
            $this->statistikPegawai(
                title: 'Tugas Belajar Mandiri',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusPegawai(20)),
            ),
        ];
        $datas = [
            $this->statistikStatusKerja(
                title: 'CPNS',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(8)),
            ),
            $this->statistikStatusKerja(
                title: 'PNS',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(1)),
            ),
            $this->statistikStatusKerja(
                title: 'BLU',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(2)),
            ),
            $this->statistikStatusKerja(
                title: 'Honorer',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(3)),
            ),
            $this->statistikStatusKerja(
                title: 'Outsourcing',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(4)),
            ),
            $this->statistikStatusKerja(
                title: 'PKWT',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(5)),
            ),
            $this->statistikStatusKerja(
                title: 'PPPK',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(7)),
            ),
            $this->statistikStatusKerja(
                title: 'Non BLU',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(19)),
            ),
            $this->statistikStatusKerja(
                title: 'PPPK Paruh Waktu',
                hasilApi: $this->pegawaiService->getData($this->parameterStatusKerja(20)),
            ),
        ];
        $levelPegawai = [
            $this->formatCard('Tendik', $this->pegawaiService->getData(['count' => 1, 'level_pegawai' => 2])),
            $this->formatCard('Dosen', $this->pegawaiService->getData(['count' => 1, 'level_pegawai' => 3])),
            $this->formatCard('Dosen DT', $this->pegawaiService->getData(['count' => 1, 'level_pegawai' => 7])),
            $this->formatCard('Dosen Luar Biasa', $this->pegawaiService->getData(['count' => 1, 'level_pegawai' => 13])),
        ];
        $guruBesarApi = $this->pegawaiService->getData(['count' => 1, 'jabatan' => 44]);
        $guruBesar = ($guruBesarApi['status'] ?? false) ? (int)($guruBesarApi['total'] ?? 0) : 0;

        return view('pegawai', compact('statusPegawai', 'datas', 'levelPegawai', 'guruBesar'));
    }

    private function statistikStatusKerja(string $title, array $hasilApi): array
    {
        return [
            'label' => $title,
            'value' => ($hasilApi['status'] ?? false) ? (int)($hasilApi['total'] ?? 0) : 0,
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

        return response()->json($this->pegawaiService->getData($parameter));
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
                'iconBg' => $style['iconBg'] ?? 'bg-white/15',
                'iconColor' => $style['iconColor'] ?? 'text-white',
            ],

            'Alih Status' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-amber-400',
                'icon' => 'fa-solid fa-layer-group',
                'text' => 'text-black',
                'iconBg' => $style['iconBg'] ?? 'bg-white/15',
                'iconColor' => $style['iconColor'] ?? 'text-white',
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
                'iconBg' => $style['iconBg'] ?? 'bg-white/15',
                'iconColor' => $style['iconColor'] ?? 'text-white',
            ],

            'Penugasan' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-pink-500',
                'icon' => 'fa-solid fa-briefcase',
                'text' => 'text-white',
                'iconBg' => $style['iconBg'] ?? 'bg-white/15',
                'iconColor' => $style['iconColor'] ?? 'text-white',
            ],

            'Tugas Belajar Mandiri' => [
                'span' => 'lg:col-span-3',
                'bg' => 'bg-indigo-600',
                'icon' => 'fa-solid fa-book-open-reader',
                'text' => 'text-white',
                'iconBg' => $style['iconBg'] ?? 'bg-white/15',
                'iconColor' => $style['iconColor'] ?? 'text-white',
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
        array  $hasilApi
    ): array
    {
        $style = $this->styleStatusPegawai($title);

        return [
            'label' => $title,
            'value' => ($hasilApi['status'] ?? false)
                ? (int)($hasilApi['total'] ?? 0)
                : 0,

            'span' => $style['span'],
            'bg' => $style['bg'],
            'icon' => $style['icon'],
            'text' => $style['text'],

            'iconBg' => $style['iconBg'],
            'iconColor' => $style['iconColor'],
        ];
    }

    private function formatCard(string $title, array $hasilApi): array
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
            'value' => ($hasilApi['status'] ?? false) ? (int)($hasilApi['total'] ?? 0) : 0,
            'bg' => $style['bg'],
            'iconBg' => $style['iconBg'],
            'iconColor' => $style['iconColor'],
            'icon' => $style['icon'],
            'textColor' => $style['textColor'],
        ];
    }

    public function getByNip(string $nip): JsonResponse
    {
        $hasilApi = $this->pegawaiService->getData([
            'nip' => $nip
        ]);

        return response()->json($hasilApi);
    }
}
