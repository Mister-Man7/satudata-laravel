<?php

namespace App\View\Components\Dashboard;

use App\Services\SiakangLulusanService;
use App\Services\SiakangMahasiswaAktifService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatistikMahasiswa extends Component
{
    public string $title;

    public string $subtitle;
    public array $summaryCards;
    public array $academicYears;
    public array $semesters;
    public array $faculties;
    public array $chartPayload;
    public string $selectedYear;
    public string $selectedSemester;

    public function __construct(
        SiakangMahasiswaAktifService $aktifService,
        SiakangLulusanService        $lulusanService,
        string                       $title = 'Total Mahasiswa',
        string                       $subtitle = 'Ringkasan mahasiswa per fakultas dengan filter tahun akademik, semester, dan fakultas.'
    )
    {
        $this->title = $title;
        $this->subtitle = $subtitle;

        $this->academicYears = ['2025/2026', '2024/2025', '2023/2024', '2022/2023'];
        $this->semesters = ['Ganjil', 'Genap', 'Antara'];

        $this->selectedYear = request('tahun', '2024/2025');
        $this->selectedSemester = request('semester', 'Ganjil');

        $tahunAngka = substr($this->selectedYear, 0, 4);
        $angkaSemester = match ($this->selectedSemester) {
            'Ganjil' => '1',
            'Genap' => '2',
            'Antara' => '3',
            default => '1',
        };

        $kodeSemesterApi = $tahunAngka . $angkaSemester;

        $responseAktif = $aktifService->getData([
            'semester' => $kodeSemesterApi
        ]);

        $responseLulusan = $lulusanService->getData(['limit' => 1, 'page' => 1]);

        $daftarFakultasApi = ['Semua Fakultas'];
        $dataFakultas = $responseAktif['detail_per_fakultas'] ?? [];
        foreach ($dataFakultas as $item) {
            $namaFak = $item['nama_fakultas'] ?? '';
            if (empty($namaFak) || str_ireplace(' ', '', $namaFak) === 'Tidakadafakultas' || str_contains(strtolower($namaFak), 'tidak ada')) {
                continue;
            }
            $daftarFakultasApi[] = $namaFak;
        }
        $this->faculties = $daftarFakultasApi;

        $this->summaryCards = $this->buildSummaryCards($responseAktif, $responseLulusan);
        $this->chartPayload = [
            'filters' => [
                'years' => $this->academicYears,
                'semesters' => $this->semesters,
                'faculties' => $this->faculties,
            ],
            'defaultSelection' => [
                'year' => $this->selectedYear,
                'semester' => $this->selectedSemester,
                'faculty' => 'Semua Fakultas',
            ],
            'chartCatalog' => $this->buildChartCatalog($responseAktif),
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.dashboard.statistik-mahasiswa', [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'summaryCards' => $this->summaryCards,
            'academicYears' => $this->academicYears,
            'semesters' => $this->semesters,
            'faculties' => $this->faculties,
            'chartPayload' => $this->chartPayload,
        ]);
    }

    private function buildSummaryCards(array $responseAktif, array $responseLulusan): array
    {

        $totalAktif = $responseAktif['tersedia'] ? (int)$responseAktif['total_mahasiswa_aktif'] : 0;

        $totalLulusan = $responseLulusan['tersedia'] ? (int)$responseLulusan['total'] : 0;

        $totalMahasiswa = $totalAktif + $totalLulusan;

        return [
            [
                'label' => 'Total Mahasiswa',
                'value' => $totalMahasiswa > 0 ? number_format($totalMahasiswa, 0, ',', '.') : '-',
                'accentBg' => 'bg-gray-custom-400/40',
                'accentText' => 'text-gray-custom-600',
            ],
            [
                'label' => 'Mahasiswa Aktif',
                'value' => $totalAktif > 0 ? number_format($totalAktif, 0, ',', '.') : '-',
                'accentBg' => 'bg-blue-custom-400/15',
                'accentText' => 'text-blue-custom-500',
            ],
            [
                'label' => 'Mahasiswa Baru',
                'value' => '-',
                'accentBg' => 'bg-green-400/15',
                'accentText' => 'text-green-500',
            ],
            [
                'label' => 'Mahasiswa Lulus',
                'value' => $totalLulusan > 0 ? number_format($totalLulusan, 0, ',', '.') : '-',
                'accentBg' => 'bg-cyan-custom-400/15',
                'accentText' => 'text-cyan-custom-500',
            ],
        ];
    }

    private function buildChartCatalog(array $responseAktif): array
    {
        $dataFakultas = $responseAktif['detail_per_fakultas'] ?? [];
        $dataProdi = $responseAktif['detail_per_prodi'] ?? [];

        $labels = [];
        foreach ($dataFakultas as $item) {
            $namaFak = $item['nama_fakultas'] ?? '';
            if (empty($namaFak) || str_ireplace(' ', '', $namaFak) === 'Tidakadafakultas' || str_contains(strtolower($namaFak), 'tidak ada')) {
                continue;
            }
            $labels[] = $namaFak;
        }

        $daftarJenjang = ['Diploma 3', 'Sarjana', 'Profesi', 'Magister', 'Doktor'];

        $matriks = [];
        foreach ($daftarJenjang as $jenjang) {
            foreach ($labels as $fak) {
                $matriks[$jenjang][$fak] = 0;
            }
        }

        foreach ($dataProdi as $prodi) {
            $fak = $prodi['fakultas'] ?? '';
            $jenjang = $prodi['jenjang'] ?? '';
            $jumlah = (int)($prodi['jumlah_mahasiswa_aktif'] ?? 0);

            if (isset($matriks[$jenjang][$fak])) {
                $matriks[$jenjang][$fak] += $jumlah;
            }
        }

        $datasets = [];
        foreach ($daftarJenjang as $jenjang) {
            $dataValues = [];
            foreach ($labels as $fak) {
                $dataValues[] = $matriks[$jenjang][$fak];
            }
            $datasets[] = [
                'label' => 'Mahasiswa ' . $jenjang,
                'data' => $dataValues,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'raw_prodi' => $dataProdi,
        ];
    }
}
