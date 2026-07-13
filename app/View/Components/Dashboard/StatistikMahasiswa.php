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

    public function __construct(
        SiakangMahasiswaAktifService $aktifService,
        SiakangLulusanService        $lulusanService,
        string                       $title = 'Total Mahasiswa',
        string                       $subtitle = 'Ringkasan mahasiswa per fakultas dengan filter tahun akademik, semester, dan fakultas.'
    )
    {
        $this->title = $title;
        $this->subtitle = $subtitle;

        $responseAktif = $aktifService->getData([]);
        $responseLulusan = $lulusanService->getData(['limit' => 1, 'page' => 1]);

        $this->academicYears = ['2024/2025', '2023/2024', '2022/2023'];
        $this->semesters = ['Ganjil', 'Genap', 'Antara'];
        $this->faculties = ['Semua Fakultas', 'FKIP', 'FEB', 'FT', 'FISIP', 'FH', 'FP', 'FK'];

        // 2. Lempar data response ke builder
        $this->summaryCards = $this->buildSummaryCards($responseAktif, $responseLulusan);

        $this->chartPayload = [
            'filters' => [
                'years' => $this->academicYears,
                'semesters' => $this->semesters,
                'faculties' => $this->faculties,
            ],
            'defaultSelection' => [
                'year' => '2024/2025',
                'semester' => 'Ganjil',
                'faculty' => 'Semua Fakultas',
            ],
            // 👇 PERBAIKANNYA DI SINI: Masukkan $responseAktif ke dalam kurung!
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

    /**
     * 3. Olah data cards dari API secara dinamis
     */
    private function buildSummaryCards(array $responseAktif, array $responseLulusan): array
    {
        // Ambil data aktif dari API (default 0 jika gagal)
        $totalAktif = $responseAktif['tersedia'] ? (int)$responseAktif['total_mahasiswa_aktif'] : 0;

        // Ambil data lulusan dari API (default 0 jika gagal)
        $totalLulusan = $responseLulusan['tersedia'] ? (int)$responseLulusan['total'] : 0;

        // Hitung total gabungan (atau sesuaikan dengan rumus bisnis kampusmu)
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
                'value' => '-', // Bisa diisi nanti kalau ada API mahasiswa baru
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

        // Kita susun array untuk label grafik (Nama Fakultas) dan datanya (Jumlah Mahasiswa)
        $labels = [];
        $dataAktif = [];

        foreach ($dataFakultas as $item) {
            // Singkat nama fakultas agar rapi di grafik (misal: "Fakultas Teknik" -> "Teknik")
            $namaSingkat = str_replace(['Fakultas ', ' dan Ilmu Pendidikan', ' dan Ilmu Kesehatan'], ['', 'KIP', 'K'], $item['nama_fakultas']);
            $labels[] = $namaSingkat;
            $dataAktif[] = (int)$item['jumlah_mahasiswa_aktif'];
        }

        return [
            'labels' => $labels,
            'data' => $dataAktif,
        ];
    }
}
