<?php

namespace App\Http\Controllers;

use App\Services\SiakangLulusanService;
use App\Services\SiakangMahasiswaAktifService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AkademikController extends Controller
{
    public function __construct(
        public SiakangLulusanService        $lulusanService,
        public SiakangMahasiswaAktifService $aktifService
    )
    {
    }

    public function index(): View
    {
        $responseLulusan = $this->lulusanService->getData([
            'limit' => 1,
            'page' => 1,
        ]);

        $responseAktif = $this->aktifService->getData([]);

        $totalLulusan = null;
        $statusLulusan = 'API tidak tersedia';

        if ($responseLulusan['tersedia']) {
            $totalLulusan = $responseLulusan['total'];
            $statusLulusan = 'Tersambung API';
        }

        $totalAktif = null;
        $statusAktif = 'API tidak tersedia';

        if ($responseAktif['tersedia']) {
            $totalAktif = $responseAktif['total_mahasiswa_aktif'];
            $statusAktif = 'Tersambung API';
        }

        $datas = [
            [
                'title' => 'Mahasiswa Aktif',
                'value' => $totalAktif,
                'href' => null,
                'cardBg' => 'bg-[#30A64A]',
                'iconBg' => 'bg-green-50',
                'iconColor' => 'text-green-600',
                'iconClass' => 'fa-solid fa-user-check',
                'description' => 'Total mahasiswa aktif dari integrasi SIAKANG.',
                'status' => $statusAktif,
            ],
            [
                'title' => 'Mahasiswa Tidak Aktif',
                'value' => null,
                'href' => null,
                'cardBg' => 'bg-gray-500',
                'iconBg' => 'bg-gray-50',
                'iconColor' => 'text-gray-600',
                'iconClass' => 'fa-solid fa-user-clock',
                'description' => 'Ringkasan mahasiswa nonaktif akan ditampilkan saat data tersedia.',
                'status' => 'Belum tersedia',
            ],
            [
                'title' => 'Mahasiswa Lulus',
                'value' => $totalLulusan,
                'href' => route('akademik.mahasiswa-lulus'),
                'cardBg' => 'bg-[#25A2B7]',
                'iconBg' => 'bg-teal-50',
                'iconColor' => 'text-teal-600',
                'iconClass' => 'fa-solid fa-user-graduate',
                'description' => 'Total mahasiswa lulus dari integrasi SIAKANG.',
                'status' => $statusLulusan,
            ],
            [
                'title' => 'Mahasiswa Baru',
                'value' => null,
                'href' => null,
                'cardBg' => 'bg-[#157FFB]',
                'iconBg' => 'bg-blue-50',
                'iconColor' => 'text-blue-600',
                'iconClass' => 'fa-solid fa-user-plus',
                'description' => 'Data mahasiswa baru akan mengikuti integrasi akademik berikutnya.',
                'status' => 'Belum tersedia',
            ],
        ];

        // 1. Mapping diubah menggunakan 'nama_fakultas' sebagai key
        $dataFakultasApi = collect($responseAktif['detail_per_fakultas'] ?? [])
            ->pluck('jumlah_mahasiswa_aktif', 'nama_fakultas');

        // 2. Cocokkan key persis dengan string 'nama_fakultas' dari API
        $fakultas = [
            [
                'name' => 'Kedokteran dan Ilmu Kesehatan',
                'total' => $dataFakultasApi->get('Fakultas Kedokteran dan Ilmu Kesehatan', 0),
                'icon' => 'fa-solid fa-stethoscope',
                'color' => 'text-blue-600',
            ],
            [
                'name' => 'Pertanian',
                'total' => $dataFakultasApi->get('Fakultas Pertanian', 0),
                'icon' => 'fa-solid fa-seedling',
                'color' => 'text-green-600',
            ],
            [
                'name' => 'Hukum',
                'total' => $dataFakultasApi->get('Fakultas Hukum', 0),
                'icon' => 'fa-solid fa-gavel',
                'color' => 'text-red-600',
            ],
            [
                'name' => 'Teknik',
                'total' => $dataFakultasApi->get('Fakultas Teknik', 0),
                'icon' => 'fa-solid fa-gears',
                'color' => 'text-yellow-500',
            ],
            [
                'name' => 'Ekonomi dan Bisnis',
                'total' => $dataFakultasApi->get('Fakultas Ekonomi dan Bisnis', 0),
                'icon' => 'fa-solid fa-briefcase',
                'color' => 'text-green-500',
            ],
            [
                'name' => 'Ilmu Sosial dan Ilmu Politik',
                'total' => $dataFakultasApi->get('Fakultas Ilmu Sosial dan Ilmu Politik', 0),
                'icon' => 'fa-solid fa-handshake',
                'color' => 'text-fuchsia-500',
            ],
            [
                'name' => 'Keguruan dan Ilmu Pendidikan',
                'total' => $dataFakultasApi->get('Fakultas Keguruan dan Ilmu Pendidikan', 0),
                'icon' => 'fa-solid fa-person-chalkboard',
                'color' => 'text-indigo-600',
            ],
            [
                'name' => 'Pascasarjana',
                'total' => $dataFakultasApi->get('Pascasarjana', 0),
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
            'angkatan' => ['nullable', 'integer', 'between:1900,' . (now()->year + 1)],
            'tahun_lulus' => ['nullable', 'integer', 'between:1900,' . (now()->year + 1)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('mahasiswa-lulus', [
            'title' => 'Mahasiswa Lulus',
        ]);
    }
}
