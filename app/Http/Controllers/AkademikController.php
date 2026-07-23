<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Services\SiakangLulusanService;
use App\Services\SiakangMahasiswaAktifService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AkademikController extends Controller
{
    public function __construct(
        public SiakangMahasiswaAktifService $aktifService,
        public SiakangLulusanService        $lulusanService,
    )
    {
    }

    public function index(): View
    {

        $waktuSekarang = now();
        $tahunNow = now()->year;
        $tahunMulai = $tahunNow - 8;
        $tahunSelesai = $tahunNow - 1;


        $peminatPerJalur = Mahasiswa::selectRaw('angkatan, count(*) as total')
            ->whereBetween('angkatan', [$tahunMulai, $tahunSelesai])
            ->groupBy('angkatan')
            ->orderBy('angkatan', 'asc')
            ->pluck('total', 'angkatan')
            ->toArray();

        $chartPeminat = [];
        for ($tahun = $tahunMulai; $tahun <= $tahunSelesai; $tahun++) {
            $chartPeminat[$tahun] = [
                'Seleksi Nasional' => Mahasiswa::where('angkatan', $tahun)
                    ->whereIn('jalur_masuk_id', ['snbp', 'snbt', 'snmptn', 'sbmptn']) //4
                    ->count(),

                'Seleksi Mandiri' => Mahasiswa::where('angkatan', $tahun)
                    ->whereIn('jalur_masuk_id', ['sm', 'ujian-mandiri', 'smmptn-barat', 'seleksi-mandiri-berdasarkan-test', 'smptn', 'umb']) //6
                    ->count(),

                'Lainnya' => Mahasiswa::where('angkatan', $tahun)
                    ->whereNotIn('jalur_masuk_id', ['snbp', 'snbt', 'snmptn', 'sbmptn', 'smptn', 'sm', 'ujian-mandiri', 'smmptn-barat', 'seleksi-mandiri-berdasarkan-test', 'umb']) //10
                    ->count(),
            ];
        }


        $tahunAktif = $waktuSekarang->month < 7 ? $waktuSekarang->year - 1 : $waktuSekarang->year;
        $tahunLalu = $tahunAktif - 1;


        $kodeSemesterSekarang = ($tahunAktif - 1) . '2';
        $kodeSemesterLalu = ($tahunAktif - 1) . '1';

        $responseAktif = $this->aktifService->getData([]);
        $responseLulusan = $this->lulusanService->getData(['semester' => $kodeSemesterSekarang]);
        $responseLulusanLalu = $this->lulusanService->getData(['semester' => $kodeSemesterLalu]);

        $totalAktifSekarang = 0;
        $detailFakultasAktif = [];

        if (isset($responseAktif['status']) && $responseAktif['status'] === true) {
            $totalAktifSekarang = $responseAktif['total_mahasiswa'] ?? 0;
            $detailFakultasAktif = $responseAktif['detail_per_fakultas'] ?? [];
        }

        $totalLulusanSekarang = 0;
        $detailFakultasLulus = [];
        $prodiLulusList = [];

        if (isset($responseLulusan['tersedia']) && $responseLulusan['tersedia'] === true) {
            $totalLulusanSekarang = $responseLulusan['total_mahasiswa_lulus'] ?? 0;
            $detailFakultasLulus = $responseLulusan['detail_per_fakultas'] ?? [];
            $prodiLulusList = $responseLulusan['detail_per_prodi'] ?? [];
        }

        $totalLulusanLalu = 0;
        if (isset($responseLulusanLalu['tersedia']) && $responseLulusanLalu['tersedia'] === true) {
            $totalLulusanLalu = $responseLulusanLalu['total_mahasiswa_lulus'] ?? 0;
        }

        $totalBaruSekarang = Mahasiswa::where('angkatan', $tahunAktif)->count();
        $totalBaruLalu = Mahasiswa::where('angkatan', $tahunLalu)->count();

        $totalTidakAktifSekarang = 0;
        $totalTidakAktifLalu = 0;

        $trendLulusan = $this->hitungTrend($totalLulusanSekarang, $totalLulusanLalu);
        $trendBaru = $this->hitungTrend($totalBaruSekarang, $totalBaruLalu);
        $trendTidakAktif = $this->hitungTrend($totalTidakAktifSekarang, $totalTidakAktifLalu);

        $datas = [
            [
                'title' => 'TOTAL MAHASISWA',
                'value' => $totalAktifSekarang,
                'iconClass' => 'fa-regular fa-user',
                'badgeText' => null,
                'badgeColor' => null,
                'footerText' => null,
                'href' => null,
            ],
            [
                'title' => 'MAHASISWA NONAKTIF',
                'value' => $totalTidakAktifSekarang,
                'iconClass' => 'fa-regular fa-clock',
                'badgeText' => $trendTidakAktif['text'],
                'badgeColor' => $trendTidakAktif['color'],
                'footerText' => 'Dari tahun sebelumnya',
                'href' => null,
            ],
            [
                'title' => 'MAHASISWA LULUS',
                'value' => $totalLulusanSekarang,
                'iconClass' => 'fa-solid fa-arrow-up-right-from-square',
                'badgeText' => $trendLulusan['text'],
                'badgeColor' => $trendLulusan['color'],
                'footerText' => 'dari semester sebelumnya',
                'href' => route('akademik.mahasiswa-lulus'),
            ],
            [
                'title' => 'MAHASISWA BARU',
                'value' => $totalBaruSekarang,
                'iconClass' => 'fa-regular fa-heart',
                'badgeText' => $trendBaru['text'],
                'badgeColor' => $trendBaru['color'],
                'footerText' => 'dari angkatan ' . $tahunLalu,
                'href' => null,
            ],
        ];

        $fakultasAktifMap = collect($detailFakultasAktif)->keyBy('nama_fakultas');
        $fakultasLulusMap = collect($detailFakultasLulus)->pluck('jumlah_mahasiswa_lulus', 'nama_fakultas');

        $fakultas = [
            [
                'name' => 'Fakultas Kedokteran dan Ilmu Kesehatan',
                'total' => $fakultasAktifMap->get('Fakultas Kedokteran dan Ilmu Kesehatan')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Fakultas Kedokteran dan Ilmu Kesehatan')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Fakultas Kedokteran dan Ilmu Kesehatan')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Fakultas Kedokteran dan Ilmu Kesehatan', 0),
                'icon' => 'fa-solid fa-stethoscope',
                'color' => 'text-blue-600',
            ],
            [
                'name' => 'Fakultas Pertanian',
                'total' => $fakultasAktifMap->get('Fakultas Pertanian')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Fakultas Pertanian')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Fakultas Pertanian')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Fakultas Pertanian', 0),
                'icon' => 'fa-solid fa-seedling',
                'color' => 'text-green-600',
            ],
            [
                'name' => 'Fakultas Hukum',
                'total' => $fakultasAktifMap->get('Fakultas Hukum')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Fakultas Hukum')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Fakultas Hukum')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Fakultas Hukum', 0),
                'icon' => 'fa-solid fa-gavel',
                'color' => 'text-red-600',
            ],
            [
                'name' => 'Fakultas Teknik',
                'total' => $fakultasAktifMap->get('Fakultas Teknik')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Fakultas Teknik')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Fakultas Teknik')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Fakultas Teknik', 0),
                'icon' => 'fa-solid fa-gears',
                'color' => 'text-yellow-500',
            ],
            [
                'name' => 'Fakultas Ekonomi dan Bisnis',
                'total' => $fakultasAktifMap->get('Fakultas Ekonomi dan Bisnis')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Fakultas Ekonomi dan Bisnis')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Fakultas Ekonomi dan Bisnis')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Fakultas Ekonomi dan Bisnis', 0),
                'icon' => 'fa-solid fa-briefcase',
                'color' => 'text-green-500',
            ],
            [
                'name' => 'Fakultas Ilmu Sosial dan Ilmu Politik',
                'total' => $fakultasAktifMap->get('Fakultas Ilmu Sosial dan Ilmu Politik')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Fakultas Ilmu Sosial dan Ilmu Politik')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Fakultas Ilmu Sosial dan Ilmu Politik')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Fakultas Ilmu Sosial dan Ilmu Politik', 0),
                'icon' => 'fa-solid fa-handshake',
                'color' => 'text-fuchsia-500',
            ],
            [
                'name' => 'Fakultas Keguruan dan Ilmu Pendidikan',
                'total' => $fakultasAktifMap->get('Fakultas Keguruan dan Ilmu Pendidikan')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Fakultas Keguruan dan Ilmu Pendidikan')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Fakultas Keguruan dan Ilmu Pendidikan')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Fakultas Keguruan dan Ilmu Pendidikan', 0),
                'icon' => 'fa-solid fa-person-chalkboard',
                'color' => 'text-indigo-600',
            ],
            [
                'name' => 'Pascasarjana',
                'total' => $fakultasAktifMap->get('Pascasarjana')['jumlah_mahasiswa_aktif'] ?? 0,
                'laki_laki' => $fakultasAktifMap->get('Pascasarjana')['jumlah_laki_laki'] ?? 0,
                'perempuan' => $fakultasAktifMap->get('Pascasarjana')['jumlah_perempuan'] ?? 0,
                'total_lulus' => $fakultasLulusMap->get('Pascasarjana', 0),
                'icon' => 'fa-solid fa-graduation-cap',
                'color' => 'text-violet-600',
            ],
        ];

        $collectionFakultas = collect($fakultas);

        $fakultasTerbanyak = $collectionFakultas->sortByDesc('total')->first() ?? ['name' => '-', 'total' => 0];
        $fakultasSedikit = $collectionFakultas->sortBy('total')->first() ?? ['name' => '-', 'total' => 0];
        $fakultasLulusTerbanyak = $collectionFakultas->sortByDesc('total_lulus')->first() ?? ['name' => '-', 'total_lulus' => 0];

        $maxTotalMahasiswa = $collectionFakultas->max(function ($item) {
            return (int)($item['total'] ?? 0) + (int)($item['total_lulus'] ?? 0);
        });

        $prodiAktifList = $responseAktif['detail_per_prodi'] ?? [];
        $collectionProdiAktif = collect($prodiAktifList);
        $collectionProdiLulus = collect($prodiLulusList);

        $prodiTerbanyak = Mahasiswa::with('prodi')
            ->whereHas('prodi', function ($query) {
                $query->whereIn('jenjang', ['Sarjana', 'S1']);
            })
            ->selectRaw('prodi_id, count(*) as total')
            ->groupBy('prodi_id')
            ->orderByDesc('total')
            ->first();

        $jurusanTerbanyak = [
            'nama_prodi' => $prodiTerbanyak->prodi->nama_prodi ?? '-',
            'jumlah_mahasiswa_aktif' => $prodiTerbanyak->total ?? 0
        ];

        $prodiSedikit = Mahasiswa::with('prodi')
            ->whereNotNull('prodi_id')
            ->whereHas('prodi', function ($query) {
                $query->whereNotNull('nama_prodi')
                    ->where('nama_prodi', '!=', 'Tidak')
                    ->whereIn('jenjang', ['Sarjana', 'S1']);
            })
            ->selectRaw('prodi_id, count(*) as total')
            ->groupBy('prodi_id')
            ->orderBy('total', 'asc')
            ->first();

        $jurusanSedikit = [
            'nama_prodi' => $prodiSedikit->prodi->nama_prodi ?? '-',
            'jumlah_mahasiswa_aktif' => $prodiSedikit->total ?? 0
        ];

        $jurusanLulusTerbanyak = $collectionProdiLulus->sortByDesc('jumlah_mahasiswa_lulus')->first() ?? ['nama_prodi' => '-', 'jumlah_mahasiswa_lulus' => 0];

        return view('akademik', [
            'title' => 'Akademik',
            'datas' => $datas,
            'fakultas' => $fakultas,
            'fakultasTerbanyak' => $fakultasTerbanyak,
            'fakultasSedikit' => $fakultasSedikit,
            'fakultasLulusTerbanyak' => $fakultasLulusTerbanyak,
            'maxTotalMahasiswa' => $maxTotalMahasiswa > 0 ? $maxTotalMahasiswa : 1,
            'jurusanTerbanyak' => $jurusanTerbanyak,
            'jurusanSedikit' => $jurusanSedikit,
            'jurusanLulusTerbanyak' => $jurusanLulusTerbanyak,
            'jumlahPeminat' => $peminatPerJalur,
            'chartPeminat' => $chartPeminat,
        ]);
    }

    public function mahasiswaLulus(Request $request): View
    {
        return view('mahasiswa-lulus', [
            'title' => 'Mahasiswa Lulus',
        ]);
    }

    private function hitungTrend($current, $previous)
    {
        if ($previous == 0) {
            return [
                'text' => $current > 0 ? '+100%' : '0%',
                'color' => $current > 0 ? 'bg-blue-500' : 'bg-gray-500'
            ];
        }

        $persentase = round((($current - $previous) / $previous) * 100);

        if ($persentase > 0) {
            return [
                'text' => '+' . $persentase . '%',
                'color' => 'bg-blue-500'
            ];
        } elseif ($persentase < 0) {
            return [
                'text' => $persentase . '%',
                'color' => 'bg-rose-500'
            ];
        }

        return [
            'text' => '0%',
            'color' => 'bg-gray-400'
        ];
    }
}

