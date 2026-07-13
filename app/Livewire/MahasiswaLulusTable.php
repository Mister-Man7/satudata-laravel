<?php

namespace App\Livewire;

use App\Services\SiakangLulusanService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MahasiswaLulusTable extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $kode_prodi = '';

    #[Url(except: '')]
    public string $angkatan = '';

    #[Url(except: '')]
    public string $tahun_lulus = '';

    public function terapkanFilter(): void
    {
        $this->validate();

        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset('search', 'kode_prodi', 'angkatan', 'tahun_lulus');
        $this->resetPage();
        $this->resetValidation();
    }

    public function render(SiakangLulusanService $lulusanService): View
    {
        $hasilApi = $lulusanService->getData($this->parameterApi());

        return view('livewire.mahasiswa-lulus-table', [
            'result' => $hasilApi,
            'mahasiswa' => $this->buatPaginator($hasilApi),
        ]);
    }

    /**
     * @return array<string, int|string>
     */
    private function parameterApi(): array
    {
        $parameter = [
            'limit' => 25,
            'page' => $this->getPage(),
        ];

        if ($this->search !== '') {
            $parameter['search'] = $this->search;
        }

        if ($this->kode_prodi !== '') {
            $parameter['kode_prodi'] = $this->kode_prodi;
        }

        if ($this->angkatan !== '') {
            $parameter['angkatan'] = $this->angkatan;
        }

        if ($this->tahun_lulus !== '') {
            $parameter['tahun_lulus'] = $this->tahun_lulus;
        }

        return $parameter;
    }

    /**
     * @param array<string, mixed> $hasilApi
     * @return LengthAwarePaginatorContract<int, mixed>
     */
    private function buatPaginator(array $hasilApi): LengthAwarePaginatorContract
    {
        $dataMahasiswa = $hasilApi['data'] ?? [];

        if (!is_array($dataMahasiswa)) {
            $dataMahasiswa = [];
        }

        return new LengthAwarePaginator(
            items: new Collection($dataMahasiswa),
            total: (int)($hasilApi['total'] ?? 0),
            perPage: 25,
            currentPage: (int)($hasilApi['halaman_sekarang'] ?? $this->getPage()),
            options: [
                'path' => route('akademik.mahasiswa-lulus'),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'kode_prodi' => ['nullable', 'string', 'max:20'],
            'angkatan' => ['nullable', 'integer', 'between:1900,' . (now()->year + 1)],
            'tahun_lulus' => ['nullable', 'integer', 'between:1900,' . (now()->year + 1)],
        ];
    }
}
