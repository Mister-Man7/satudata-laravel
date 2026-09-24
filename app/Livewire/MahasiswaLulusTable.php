<?php

namespace App\Livewire;

use App\Services\Integrations\SiakangLulusanService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    /**
     * Filter langsung dipakai begitu nilainya berubah (tanpa tombol "Terapkan"), jadi page
     * hasil di-reset ke 1 setiap kali filter berubah — kalau tidak, pengguna bisa mendarat
     * di page yang sudah tidak ada. Perubahan page pagination dikecualikan supaya paging
     * tetap bekerja.
     */
    public function updating(string $nama, mixed $nilai): void
    {
        if (!in_array($nama, ['search', 'kode_prodi', 'angkatan', 'tahun_lulus'], true)) {
            return;
        }

        if ($nama === 'search') {
            $this->validateOnly('search');
        }

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
        $hasilApi = $lulusanService->getListMahasiswa($this->parameterApi());

        // Ekstrak wrapper pagination dari ApiResponse.
        // Struktur API: data = [0 => {current_page, data: [...], total, per_page, ...}]
        $dataApi = [];
        if ($hasilApi->success && isset($hasilApi->data[0]) && is_array($hasilApi->data[0])) {
            $dataApi = $hasilApi->data[0];
        }

        return view('livewire.mahasiswa-lulus-table', [
            'result' => $hasilApi,
            'mahasiswa' => $this->buatPaginator($dataApi),
            'pilihanProdi' => $this->pilihanProdi(),
            'pilihanAngkatan' => $this->pilihanAngkatan(),
            'pilihanTahunLulus' => $this->pilihanTahunLulus(),
            'adaFilter' => $this->adaFilter(),
        ]);
    }

    private function adaFilter(): bool
    {
        return $this->search !== ''
            || $this->kode_prodi !== ''
            || $this->angkatan !== ''
            || $this->tahun_lulus !== '';
    }

    /**
     * Pilihan isian filter, diambil dari data nyata yang sudah ada.
     *
     * Endpoint /v2/mahasiswa/lulusan hanya menghormati search, kode_prodi, angkatan,
     * dan tahun_lulus: parameter lain (semester, jenjang, fakultas, jenis_kelamin)
     * diabaikan tanpa pesan, jadi tidak dipakai sebagai filter. Daftar pilihannya
     * pun bukan karangan — prodi dari tabel `prodis`, angkatan dan tahun lulus dari
     * kolom yang sudah tersinkron di tabel `mahasiswas`.
     */
    private function pilihanProdi(): array
    {
        return Cache::remember('filter_lulusan.prodi', now()->addHours(6), function (): array {
            return DB::table('prodis')
                ->select('kode_prodi', 'nama_prodi', 'jenjang')
                ->whereNotNull('kode_prodi')
                ->orderBy('jenjang')
                ->orderBy('nama_prodi')
                ->get()
                ->map(fn ($prodi) => [
                    'kode' => (string) $prodi->kode_prodi,
                    'label' => $prodi->nama_prodi . ' (' . $prodi->kode_prodi . ')',
                    'kelompok' => strtoupper((string) ($prodi->jenjang ?: 'lainnya')),
                ])
                ->all();
        });
    }

    private function pilihanAngkatan(): array
    {
        return Cache::remember('filter_lulusan.angkatan', now()->addHours(6), function (): array {
            return DB::table('mahasiswas')
                ->whereNotNull('angkatan')
                ->distinct()
                ->orderByDesc('angkatan')
                ->pluck('angkatan')
                ->map(fn ($angkatan) => (string) $angkatan)
                ->all();
        });
    }

    private function pilihanTahunLulus(): array
    {
        return Cache::remember('filter_lulusan.tahun_lulus', now()->addHours(6), function (): array {
            // substr() dipakai, bukan YEAR(), supaya ekspresinya jalan di MySQL maupun
            // SQLite (tes memakai SQLite).
            return DB::table('mahasiswas')
                ->distinct()
                ->selectRaw('substr(lulus_pada, 1, 4) as tahun')
                ->whereNotNull('lulus_pada')
                ->orderByDesc('tahun')
                ->pluck('tahun')
                ->map(fn ($tahun) => (string) $tahun)
                ->all();
        });
    }

    private function parameterApi(): array
    {
        $parameter = [
            'limit' => 25,
            'page' => $this->getPage(),
        ];

        if ($this->search !== '') $parameter['search'] = $this->search;
        if ($this->kode_prodi !== '') $parameter['kode_prodi'] = $this->kode_prodi;
        if ($this->angkatan !== '') $parameter['angkatan'] = $this->angkatan;
        if ($this->tahun_lulus !== '') $parameter['tahun_lulus'] = $this->tahun_lulus;

        return $parameter;
    }

    private function buatPaginator(array $hasilApi): LengthAwarePaginatorContract
    {
        $dataMahasiswa = $hasilApi['data'] ?? [];

        if (!is_array($dataMahasiswa)) {
            $dataMahasiswa = [];
        }

        return new LengthAwarePaginator(
            items: new Collection($dataMahasiswa),
            total: (int)($hasilApi['total'] ?? 0),
            perPage: (int)($hasilApi['per_page'] ?? 15),
            currentPage: (int)($hasilApi['current_page'] ?? $this->getPage()),
            options: [
                'path' => route('akademik.mahasiswa-lulus'),
                'pageName' => 'page',
            ],
        );
    }

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
