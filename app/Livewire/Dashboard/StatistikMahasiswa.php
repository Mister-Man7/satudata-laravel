<?php

namespace App\Livewire\Dashboard;

use App\Services\Integrations\SiakangLulusanService;
use App\Services\Integrations\SiakangMahasiswaAktifService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StatistikMahasiswa extends Component
{
    public string $title = 'Total Mahasiswa';
    public string $subtitle = 'Ringkasan mahasiswa per fakultas dengan filter tahun akademik, semester, dan fakultas.';
    public array $summaryCards = [];
    public array $academicYears = [];
    public array $semesters = [];
    public array $faculties = [];
    public array $chartPayload = [];
    public string $selectedYear = '';
    public string $selectedSemester = '';
    public string $selectedFaculty = 'Semua Fakultas';
    public bool $isLoading = true;

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="shimmer-card">
                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-12 flex flex-col gap-3 lg:col-span-7 lg:justify-center">
                        <div>
                            <div class="shimmer shimmer-text-lg"></div>
                            <div class="shimmer shimmer-text-sm"></div>
                        </div>
                    </div>
                    <div class="col-span-12 grid grid-cols-1 gap-3 sm:grid-cols-3 lg:col-span-5 lg:items-end">
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tahun Akademik</span>
                            <div class="shimmer shimmer-select"></div>
                        </label>
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Semester</span>
                            <div class="shimmer shimmer-select"></div>
                        </label>
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Fakultas</span>
                            <div class="shimmer shimmer-select"></div>
                        </label>
                    </div>
                    <div class="col-span-12 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:p-6">
                        <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                            <div class="flex gap-4">
                                <div class="shimmer shimmer-summary w-32"></div>
                                <div class="shimmer shimmer-summary w-32"></div>
                                <div class="shimmer shimmer-summary w-32"></div>
                                <div class="shimmer shimmer-summary w-32"></div>
                            </div>
                        </div>
                        <div class="shimmer shimmer-chart"></div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    public function mount(): void
    {
        $this->academicYears = ['2025/2026', '2024/2025', '2023/2024', '2022/2023'];
        $this->semesters = ['Ganjil', 'Genap', 'Antara'];

        $this->selectedYear = request('tahun', '2024/2025');
        $this->selectedSemester = request('semester', 'Ganjil');
        $this->selectedFaculty = request('fakultas', 'Semua Fakultas');
    }

    public function loadData(SiakangMahasiswaAktifService $aktifService, SiakangLulusanService $lulusanService): void
    {
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

        $dataAktif = $responseAktif->success ? (array) $responseAktif->data : [];
        $dataLulusan = $responseLulusan->success ? (array) $responseLulusan->data : [];

        $daftarFakultasApi = ['Semua Fakultas'];
        $dataFakultas = $dataAktif['detail_per_fakultas'] ?? [];
        foreach ($dataFakultas as $item) {
            $namaFak = $item['nama_fakultas'] ?? '';
            if (empty($namaFak) || str_ireplace(' ', '', $namaFak) === 'Tidakadafakultas' || str_contains(strtolower($namaFak), 'tidak ada')) {
                continue;
            }
            $daftarFakultasApi[] = $namaFak;
        }
        $this->faculties = $daftarFakultasApi;

        $this->summaryCards = $this->buildSummaryCards($dataAktif, $dataLulusan);
        $this->chartPayload = [
            'filters' => [
                'years' => $this->academicYears,
                'semesters' => $this->semesters,
                'faculties' => $this->faculties,
            ],
            'defaultSelection' => [
                'year' => $this->selectedYear,
                'semester' => $this->selectedSemester,
                'faculty' => $this->selectedFaculty,
            ],
            'chartCatalog' => $this->buildChartCatalog($dataAktif),
        ];

        $this->isLoading = false;
    }

    public function render(): View
    {
        return view('livewire.dashboard.statistik-mahasiswa', [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'summaryCards' => $this->summaryCards,
            'academicYears' => $this->academicYears,
            'semesters' => $this->semesters,
            'faculties' => $this->faculties,
            'chartPayload' => $this->chartPayload,
            'selectedYear' => $this->selectedYear,
            'selectedSemester' => $this->selectedSemester,
            'selectedFaculty' => $this->selectedFaculty,
        ]);
    }

    public ?string $toastMessage = null;

    public function updatedSelectedYear(): void
    {
        $this->toastMessage = "Berhasil mengganti tahun akademik ke {$this->selectedYear}";
        $this->dispatch('statistik-mahasiswa-filter-changed');
    }

    public function updatedSelectedSemester(): void
    {
        $this->toastMessage = "Berhasil mengganti semester ke {$this->selectedSemester}";
        $this->dispatch('statistik-mahasiswa-filter-changed');
    }

    public function updatedSelectedFaculty(): void
    {
        $this->toastMessage = "Berhasil memfilter fakultas: {$this->selectedFaculty}";
        $this->dispatch('statistik-mahasiswa-filter-changed');
    }


    private function buildSummaryCards(array $dataAktif, array $dataLulusan): array
    {
        $totalAktif = (int)($dataAktif['total_mahasiswa_aktif'] ?? $dataAktif['total_mahasiswa'] ?? $dataAktif['total'] ?? 0);

        $totalLulusan = (int)($dataLulusan['total_mahasiswa_lulus'] ?? $dataLulusan['total'] ?? 0);

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