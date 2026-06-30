<?php

namespace App\Http\Controllers;

use App\Services\SiakangLulusanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AkademikController extends Controller
{
    public function __construct(public SiakangLulusanService $lulusanService) {}

    public function index(): View
    {

        $hasilApi = $this->lulusanService->ambilData([
            'limit' => 1,
            'page' => 1,
        ]);

        $totalLulusan = null;
        $statusLulusan = 'API tidak tersedia';

        if ($hasilApi['tersedia']) {
            $totalLulusan = $hasilApi['total'];
            $statusLulusan = 'Tersambung API';
        }

        $datas = [
            [
                'title' => 'Mahasiswa Aktif',
                'value' => null,
                'href' => null,
                'cardBg' => 'bg-[#30A64A]',
                'iconBg' => 'bg-green-50',
                'iconColor' => 'text-green-600',
                'iconClass' => 'fa-solid fa-user-check',
            ],
            [
                'title' => 'Mahasiswa Tidak Aktif',
                'value' => null,
                'href' => null,
                'cardBg' => 'bg-gray-500',
                'iconBg' => 'bg-gray-50',
                'iconColor' => 'text-gray-600',
                'iconClass' => 'fa-solid fa-user-clock',
            ],
            [
                'title' => 'Mahasiswa Lulus',
                'value' => $totalLulusan,
                'href' => route('akademik.mahasiswa-lulus'),
                'cardBg' => 'bg-[#25A2B7]',
                'iconBg' => 'bg-teal-50',
                'iconColor' => 'text-teal-600',
                'iconClass' => 'fa-solid fa-user-graduate',
            ],
            [
                'title' => 'Mahasiswa Baru',
                'value' => null,
                'href' => null,
                'cardBg' => 'bg-[#157FFB]',
                'iconBg' => 'bg-blue-50',
                'iconColor' => 'text-blue-600',
                'iconClass' => 'fa-solid fa-user-plus',
            ],
        ];

        $fakultas = [
            [
                'name' => 'Kedokteran',
                'total' => 288,
                'icon' => 'fa-solid fa-stethoscope',
                'color' => 'text-blue-600',
            ],
            [
                'name' => 'Pertanian',
                'total' => 5114,
                'icon' => 'fa-solid fa-seedling',
                'color' => 'text-green-600',
            ],
            [
                'name' => 'Hukum',
                'total' => 5985,
                'icon' => 'fa-solid fa-gavel',
                'color' => 'text-red-600',
            ],
            [
                'name' => 'Teknik',
                'total' => 10895,
                'icon' => 'fa-solid fa-gears',
                'color' => 'text-yellow-500',
            ],
            [
                'name' => 'Ekonomi dan Bisnis',
                'total' => 12321,
                'icon' => 'fa-solid fa-briefcase',
                'color' => 'text-green-500',
            ],
            [
                'name' => 'Ilmu Sosial & Politik',
                'total' => 6031,
                'icon' => 'fa-solid fa-handshake',
                'color' => 'text-fuchsia-500',
            ],
            [
                'name' => 'Keguruan dan Ilmu Pendidikan',
                'total' => 17229,
                'icon' => 'fa-solid fa-person-chalkboard',
                'color' => 'text-indigo-600',
            ],
            [
                'name' => 'Pascasarjana',
                'total' => 1534,
                'icon' => 'fa-solid fa-graduation-cap',
                'color' => 'text-violet-600',
            ],
        ];

        return view('akademik', [
            'title' => 'Akademik',
            'datas' => $datas,
            'fakultas' => $fakultas,
        ]);
    }

    public function mahasiswaLulus(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'kode_prodi' => ['nullable', 'string', 'max:20'],
            'angkatan' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'tahun_lulus' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('mahasiswa-lulus', [
            'title' => 'Mahasiswa Lulus',
        ]);
    }
}
