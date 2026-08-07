<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\Integrations\SiakangLulusanService;
use App\Services\Integrations\SiakangMahasiswaAktifService;
use App\Services\Integrations\SimpegPegawaiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AkademikController extends Controller
{
    public function __construct(
        public SiakangMahasiswaAktifService $aktifService,
        public SiakangLulusanService        $lulusanService,
        public SimpegPegawaiService         $pegawaiService,
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
                    ->whereIn('jalur_masuk_id', ['snbp', 'snbt', 'snmptn', 'sbmptn'])->count(),
                'Seleksi Mandiri' => Mahasiswa::where('angkatan', $tahun)
                    ->whereIn('jalur_masuk_id', ['sm', 'ujian-mandiri', 'smmptn-barat', 'seleksi-mandiri-berdasarkan-test', 'smptn', 'umb'])->count(),
                'Lainnya' => Mahasiswa::where('angkatan', $tahun)
                    ->whereNotIn('jalur_masuk_id', ['snbp', 'snbt', 'snmptn', 'sbmptn', 'smptn', 'sm', 'ujian-mandiri', 'smmptn-barat', 'seleksi-mandiri-berdasarkan-test', 'umb'])->count(),
            ];
        }

        $tahunAktif = $waktuSekarang->month < 7 ? $waktuSekarang->year - 1 : $waktuSekarang->year;
        $tahunLalu = $tahunAktif - 1;

        $kodeSemesterSekarang = ($tahunAktif - 1) . '2';
        $kodeSemesterLalu = ($tahunAktif - 1) . '1';
        $tahunLaluStr = substr($kodeSemesterLalu, 0, 4);
        $tipeSemester = substr($kodeSemesterLalu, -1) == '1' ? 'Ganjil' : 'Genap';
        $kodeSemesterLaluString = $tipeSemester . ' ' . $tahunLaluStr;

        $responseAktif = $this->aktifService->getData(['semester' => $kodeSemesterSekarang]);
        $responseLulusan = $this->lulusanService->getData(['semester' => $kodeSemesterSekarang]);
        $responseLulusanLalu = $this->lulusanService->getData(['semester' => $kodeSemesterLalu]);

        $totalAktifSekarang = 0;
        $detailFakultasAktif = [];
        $prodiAktifList = [];

        if ($responseAktif->success) {
            $dataAktif = is_array($responseAktif->data) ? $responseAktif->data : [];
            $totalAktifSekarang = $dataAktif['total_mahasiswa_aktif'] ?? $dataAktif['total'] ?? 0;
            $detailFakultasAktif = $dataAktif['detail_per_fakultas'] ?? [];
            $prodiAktifList = $dataAktif['detail_per_prodi'] ?? [];
        } else {
            Log::warning('Gagal mengambil data mahasiswa aktif', ['message' => $responseAktif->message]);
        }

        $totalLulusanSekarang = 0;
        $detailFakultasLulus = [];
        $prodiLulusList = [];

        if ($responseLulusan->success) {
            $dataLulusan = is_array($responseLulusan->data) ? $responseLulusan->data : [];
            $totalLulusanSekarang = $dataLulusan['total_mahasiswa_lulus'] ?? 0;
            $detailFakultasLulus = $dataLulusan['detail_per_fakultas'] ?? [];
            $prodiLulusList = $dataLulusan['detail_per_prodi'] ?? [];
        } else {
            Log::warning('Gagal mengambil data mahasiswa lulus', ['message' => $responseLulusan->message]);
        }

        $totalLulusanLalu = 0;
        if ($responseLulusanLalu->success) {
            $dataLulusanLalu = is_array($responseLulusanLalu->data) ? $responseLulusanLalu->data : [];
            $totalLulusanLalu = $dataLulusanLalu['total_mahasiswa_lulus'] ?? 0;
        }

        $totalBaruSekarang = Mahasiswa::where('angkatan', $tahunAktif)->count();
        $totalBaruLalu = Mahasiswa::where('angkatan', $tahunLalu)->count();

        $totalTidakAktifSekarang = 0;
        $totalTidakAktifLalu = 0;

        $trendAktif = $this->hitungTrend($totalAktifSekarang, $kodeSemesterLalu);
        $trendLulusan = $this->hitungTrend($totalLulusanSekarang, $totalLulusanLalu);
        $trendBaru = $this->hitungTrend($totalBaruSekarang, $totalBaruLalu);
        $trendTidakAktif = $this->hitungTrend($totalTidakAktifSekarang, $totalTidakAktifLalu);

        $datas = [
            [
                'title' => 'TOTAL MAHASISWA',
                'value' => $totalAktifSekarang,
                'iconClass' => 'fa-regular fa-user',
                'badgeText' => $trendAktif['text'],
                'badgeColor' => $trendAktif['color'],
                'footerText' => "Semester $kodeSemesterLaluString",
                'href' => null,
            ],
            [
                'title' => 'MAHASISWA NONAKTIF',
                'value' => $totalTidakAktifSekarang,
                'iconClass' => 'fa-regular fa-clock',
                'badgeText' => $trendTidakAktif['text'],
                'badgeColor' => $trendTidakAktif['color'],
                'footerText' => 'Tahun Sebelumnya',
                'href' => null,
            ],
            [
                'title' => 'MAHASISWA LULUS',
                'value' => $totalLulusanSekarang,
                'iconClass' => 'fa-solid fa-arrow-up-right-from-square',
                'badgeText' => $trendLulusan['text'],
                'badgeColor' => $trendLulusan['color'],
                'footerText' => 'Semester ' . $kodeSemesterLaluString,
                'href' => route('akademik.mahasiswa-lulus'),
            ],
            [
                'title' => 'MAHASISWA BARU',
                'value' => $totalBaruSekarang,
                'iconClass' => 'fa-regular fa-heart',
                'badgeText' => $trendBaru['text'],
                'badgeColor' => $trendBaru['color'],
                'footerText' => 'Angkatan ' . $tahunLalu,
                'href' => null,
            ],
        ];

        $fakultasAktifMap = collect($detailFakultasAktif)->keyBy(function ($item) {
            return strtolower(trim($item['nama_fakultas'] ?? ''));
        });
        $fakultasLulusMap = collect($detailFakultasLulus)->mapWithKeys(function ($item) {
            return [strtolower(trim($item['nama_fakultas'] ?? '')) => $item['jumlah_mahasiswa_lulus'] ?? 0];
        });

        $getStatFakultas = function ($map, $namaFakultas, $key) {
            $normalizedName = strtolower(trim($namaFakultas));
            $data = $map->get($normalizedName);
            return $data[$key] ?? 0;
        };

        $fakultas = [
            [
                'name' => 'Fakultas Kedokteran dan Ilmu Kesehatan',
                'total' => $getStatFakultas($fakultasAktifMap, 'Fakultas Kedokteran dan Ilmu Kesehatan', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Fakultas Kedokteran dan Ilmu Kesehatan', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Fakultas Kedokteran dan Ilmu Kesehatan', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Fakultas Kedokteran dan Ilmu Kesehatan'), 0),
                'icon' => 'fa-solid fa-stethoscope',
                'color' => 'text-blue-600',
            ],
            [
                'name' => 'Fakultas Pertanian',
                'total' => $getStatFakultas($fakultasAktifMap, 'Fakultas Pertanian', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Fakultas Pertanian', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Fakultas Pertanian', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Fakultas Pertanian'), 0),
                'icon' => 'fa-solid fa-seedling',
                'color' => 'text-green-600',
            ],
            [
                'name' => 'Fakultas Hukum',
                'total' => $getStatFakultas($fakultasAktifMap, 'Fakultas Hukum', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Fakultas Hukum', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Fakultas Hukum', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Fakultas Hukum'), 0),
                'icon' => 'fa-solid fa-gavel',
                'color' => 'text-red-600',
            ],
            [
                'name' => 'Fakultas Teknik',
                'total' => $getStatFakultas($fakultasAktifMap, 'Fakultas Teknik', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Fakultas Teknik', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Fakultas Teknik', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Fakultas Teknik'), 0),
                'icon' => 'fa-solid fa-gears',
                'color' => 'text-yellow-500',
            ],
            [
                'name' => 'Fakultas Ekonomi dan Bisnis',
                'total' => $getStatFakultas($fakultasAktifMap, 'Fakultas Ekonomi dan Bisnis', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Fakultas Ekonomi dan Bisnis', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Fakultas Ekonomi dan Bisnis', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Fakultas Ekonomi dan Bisnis'), 0),
                'icon' => 'fa-solid fa-briefcase',
                'color' => 'text-green-500',
            ],
            [
                'name' => 'Fakultas Ilmu Sosial dan Ilmu Politik',
                'total' => $getStatFakultas($fakultasAktifMap, 'Fakultas Ilmu Sosial dan Ilmu Politik', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Fakultas Ilmu Sosial dan Ilmu Politik', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Fakultas Ilmu Sosial dan Ilmu Politik', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Fakultas Ilmu Sosial dan Ilmu Politik'), 0),
                'icon' => 'fa-solid fa-handshake',
                'color' => 'text-fuchsia-500',
            ],
            [
                'name' => 'Fakultas Keguruan dan Ilmu Pendidikan',
                'total' => $getStatFakultas($fakultasAktifMap, 'Fakultas Keguruan dan Ilmu Pendidikan', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Fakultas Keguruan dan Ilmu Pendidikan', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Fakultas Keguruan dan Ilmu Pendidikan', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Fakultas Keguruan dan Ilmu Pendidikan'), 0),
                'icon' => 'fa-solid fa-person-chalkboard',
                'color' => 'text-indigo-600',
            ],
            [
                'name' => 'Pascasarjana',
                'total' => $getStatFakultas($fakultasAktifMap, 'Pascasarjana', 'jumlah_mahasiswa_aktif'),
                'laki_laki' => $getStatFakultas($fakultasAktifMap, 'Pascasarjana', 'jumlah_laki_laki'),
                'perempuan' => $getStatFakultas($fakultasAktifMap, 'Pascasarjana', 'jumlah_perempuan'),
                'total_lulus' => $fakultasLulusMap->get(strtolower('Pascasarjana'), 0),
                'icon' => 'fa-solid fa-graduation-cap',
                'color' => 'text-violet-600',
            ],
        ];

        $collectionFakultas = collect($fakultas);
        $maxTotalMahasiswa = $collectionFakultas->max(function ($item) {
            return (int)($item['total'] ?? 0) + (int)($item['total_lulus'] ?? 0);
        });

        // Deteksi jenjang S1 secara dinamis dari data API (bukan hardcode)
        $polaJenjangS1 = '/^((s[\s\-]?1)|(strata[\s\-]?1)|(sarjana))/iu';

        $jenjangS1Aktif = collect($prodiAktifList)
            ->pluck('jenjang')
            ->filter()
            ->map(fn($j) => strtolower(trim($j)))
            ->unique()
            ->filter(fn($j) => (bool) preg_match($polaJenjangS1, $j))
            ->values()
            ->all();

        $jenjangS1Lulus = collect($prodiLulusList)
            ->pluck('jenjang')
            ->filter()
            ->map(fn($j) => strtolower(trim($j)))
            ->unique()
            ->filter(fn($j) => (bool) preg_match($polaJenjangS1, $j))
            ->values()
            ->all();

        $prodiS1Aktif = collect($prodiAktifList)->filter(function ($item) use ($jenjangS1Aktif) {
            return in_array(strtolower(trim($item['jenjang'] ?? '')), $jenjangS1Aktif);
        });

        $prodiS1Lulus = collect($prodiLulusList)->filter(function ($item) use ($jenjangS1Lulus) {
            return in_array(strtolower(trim($item['jenjang'] ?? '')), $jenjangS1Lulus);
        });

        $prodiTerbanyakS1 = $prodiS1Aktif->sortByDesc('jumlah_mahasiswa_aktif')->first() 
            ?? ['nama_prodi' => '-', 'jumlah_mahasiswa_aktif' => 0];

        $prodiSedikitS1 = $prodiS1Aktif->where('jumlah_mahasiswa_aktif', '>', 0)
            ->sortBy('jumlah_mahasiswa_aktif')->first() 
            ?? ['nama_prodi' => '-', 'jumlah_mahasiswa_aktif' => 0];

        $prodiLulusTerbanyakS1 = $prodiS1Lulus->sortByDesc('jumlah_mahasiswa_lulus')->first() 
            ?? ['nama_prodi' => '-', 'jumlah_mahasiswa_lulus' => 0];

        $dosenResponse = $this->pegawaiService->getDataDosen();
        $dataDosen = $dosenResponse->success ? ($dosenResponse->data ?? []) : [];

        $dosenByFakultas = $this->agregasiDosenByFakultas($dataDosen);
        $dosenByStatus = $this->agregasiDosenByStatus($dataDosen);
        $totalDosen = count($dataDosen);

        return view('Academic.akademik', [
            'title' => 'Akademik',
            'datas' => $datas,
            'fakultas' => $fakultas,
            'maxTotalMahasiswa' => $maxTotalMahasiswa > 0 ? $maxTotalMahasiswa : 1,
            'jurusanTerbanyak' => [
                'nama_prodi' => $prodiTerbanyakS1['nama_prodi'],
                'jumlah_mahasiswa_aktif' => $prodiTerbanyakS1['jumlah_mahasiswa_aktif']
            ],
            'jurusanSedikit' => [
                'nama_prodi' => $prodiSedikitS1['nama_prodi'],
                'jumlah_mahasiswa_aktif' => $prodiSedikitS1['jumlah_mahasiswa_aktif']
            ],
            'jurusanLulusTerbanyak' => [
                'nama_prodi' => $prodiLulusTerbanyakS1['nama_prodi'],
                'jumlah_mahasiswa_lulus' => $prodiLulusTerbanyakS1['jumlah_mahasiswa_lulus']
            ],
            
            'jumlahPeminat' => $peminatPerJalur,
            'chartPeminat' => $chartPeminat,
            'dosenByFakultas' => $dosenByFakultas,
            'dosenByStatus' => $dosenByStatus,
            'totalDosen' => $totalDosen,
        ]);
    }

    private function agregasiDosenByFakultas(array $dataDosen): array
    {
        $unitDikecualikan = ['Biro Perencanaan Keuangan dan Umum'];

        $kelompok = collect($dataDosen)
            ->reject(function ($item) use ($unitDikecualikan) {
                $unitKerja = trim((string)($item['unitKerja'] ?? ''));
                return in_array($unitKerja, $unitDikecualikan, true);
            })
            ->groupBy(function ($item) {
                return trim((string)($item['unitKerja'] ?? 'Tidak Teridentifikasi'));
            });

        return $kelompok->map(function ($items, $namaFakultas) {
            return [
                'name' => $namaFakultas === '' ? 'Tidak Teridentifikasi' : $namaFakultas,
                'total' => $items->count(),
            ];
        })->sortByDesc('total')->values()->all();
    }

    private function agregasiDosenByStatus(array $dataDosen): array
    {
        $kelompok = collect($dataDosen)->groupBy(function ($item) {
            return trim((string)($item['statusKerja'] ?? ''));
        });

        return $kelompok->map(function ($items, $statusKerja) {
            return [
                'label' => $statusKerja === '' ? 'Tidak Diketahui' : $statusKerja,
                'total' => $items->count(),
            ];
        })->sortByDesc('total')->values()->all();
    }

    public function mahasiswaLulus(Request $request): View
    {
        return view('Academic.mahasiswa-lulus', [
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

