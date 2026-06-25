<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AkademikController extends Controller
{
    /**
     * Menampilkan halaman akademik dengan statistik mahasiswa
     *
     * ALUR:
     * 1. Ambil data mahasiswa lulus dari API (limit 1 baris pertama aja)
     * 2. Ekstrak total mahasiswa lulus
     * 3. Siapkan array statistik untuk ditampilkan di dashboard
     * 4. Kirim ke view 'akademik' untuk ditampilkan
     */
    public function index(): View
    {
        // Panggil API untuk ambil data mahasiswa lulus (hanya 1 data pertama)
        $hasilApi = $this->getDataLulusan([
            'limit' => 1,    // Batasi hanya 1 baris (cukup untuk ambil total)
            'page' => 1,     // Halaman pertama
        ]);

        // Inisialisasi default jika API tidak tersedia
        $totalLulusan = null;
        $statusLulusan = 'API tidak tersedia';

        // Jika API berhasil, ambil total mahasiswa lulus
        if ($hasilApi['tersedia']) {
            $totalLulusan = $hasilApi['total'];
            $statusLulusan = 'Tersambung API';
        }

        // Siapkan data statistik untuk ditampilkan di dashboard
        // Setiap item merupakan satu kartu/box yang menampilkan jumlah mahasiswa
        $stats = [
            [
                'title' => 'Total Mahasiswa',
                'value' => null,                           // Nilai tidak tersedia
                'description' => 'Seluruh mahasiswa terdaftar.',
                'status' => 'Belum tersedia',
                'href' => null,                            // Tombol tidak aktif
                'iconBg' => 'bg-blue-50',                  // Background icon warna biru
                'iconColor' => 'text-blue-600',            // Warna icon biru
            ],
            [
                'title' => 'Mahasiswa Aktif',
                'value' => null,
                'description' => 'Mahasiswa dengan status aktif kuliah.',
                'status' => 'Belum tersedia',
                'href' => null,
                'iconBg' => 'bg-emerald-50',
                'iconColor' => 'text-emerald-600',
            ],
            [
                'title' => 'Mahasiswa Tidak Aktif',
                'value' => null,
                'description' => 'Mahasiswa nonaktif, cuti, atau tidak aktif.',
                'status' => 'Belum tersedia',
                'href' => null,
                'iconBg' => 'bg-amber-50',
                'iconColor' => 'text-amber-600',
            ],
            [
                'title' => 'Mahasiswa Lulus',
                'value' => $totalLulusan,                  // Ini dari API yang berhasil diambil
                'description' => 'Seluruh mahasiswa dengan status lulus.',
                'status' => $statusLulusan,                // Status koneksi API
                'href' => route('akademik.mahasiswa-lulus'), // Link ke halaman detail
                'iconBg' => 'bg-indigo-50',
                'iconColor' => 'text-indigo-600',
            ],
            [
                'title' => 'Mahasiswa Baru',
                'value' => null,
                'description' => 'Mahasiswa periode masuk terbaru.',
                'status' => 'Belum tersedia',
                'href' => null,
                'iconBg' => 'bg-purple-50',
                'iconColor' => 'text-purple-600',
            ],
        ];

        // Kirim data ke view 'akademik.blade.php' untuk ditampilkan
        return view('akademik', [
            'title' => 'Akademik',
            'stats' => $stats,
        ]);
    }

    /**
     * Menampilkan halaman list mahasiswa lulus dengan filter
     *
     * ALUR:
     * 1. Validasi filter dari request (search, prodi, angkatan, tahun lulus, page)
     * 2. Ubah filter menjadi parameter untuk API
     * 3. Panggil API untuk ambil data mahasiswa lulus dengan filter
     * 4. Kirim data ke view untuk ditampilkan dalam bentuk tabel
     */
    public function mahasiswaLulus(Request $request): View
    {
        // Validasi input dari user sebelum digunakan
        // Ini mengamankan aplikasi dari input yang tidak valid/berbahaya
        $filter = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],           // Bisa kosong, teks max 100 karakter
            'kode_prodi' => ['nullable', 'string', 'max:20'],        // Kode program studi
            'angkatan' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)], // Tahun masuk
            'tahun_lulus' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)], // Tahun lulus
            'page' => ['nullable', 'integer', 'min:1'],              // Nomor halaman (minimal 1)
        ]);

        // Siapkan parameter dasar untuk API
        $parameterApi = [
            'limit' => 25,                    // Tampilkan 25 data per halaman
            'page' => $filter['page'] ?? 1,  // Halaman default ke-1 jika tidak ada
        ];

        // Tambahkan filter ke parameter API jika filter ada nilai (tidak kosong)
        if (! empty($filter['search'])) {
            $parameterApi['search'] = $filter['search'];
        }

        if (! empty($filter['kode_prodi'])) {
            $parameterApi['kode_prodi'] = $filter['kode_prodi'];
        }

        if (! empty($filter['angkatan'])) {
            $parameterApi['angkatan'] = $filter['angkatan'];
        }

        if (! empty($filter['tahun_lulus'])) {
            $parameterApi['tahun_lulus'] = $filter['tahun_lulus'];
        }

        // Panggil API dengan parameter filter yang sudah disiapkan
        $hasilApi = $this->getDataLulusan($parameterApi);

        // Kirim data ke view 'mahasiswa-lulus.blade.php'
        return view('mahasiswa-lulus', [
            'title' => 'Mahasiswa Lulus',
            'mahasiswa' => $hasilApi['data'],      // Data mahasiswa dalam bentuk array
            'result' => $hasilApi,                  // Info pagination (total, halaman, dll)
        ]);
    }

    /**
     * FUNGSI PRIVATE: Mengambil data mahasiswa lulus dari API SIAKANG
     *
     * @param  array<string, int|string>  $parameter  - Parameter filter untuk API
     * @return array<string, mixed> - Hasil data atau array kosong jika error
     *
     * ALUR:
     * 1. Ambil konfigurasi URL dan token dari config
     * 2. Validasi URL dan token tidak kosong
     * 3. Bangun URL endpoint API
     * 4. Kirim request HTTP ke API dengan timeout
     * 5. Tangani error koneksi dan response tidak sukses
     * 6. Ekstrak dan format data dari response API
     */
    private function getDataLulusan(array $parameter): array
    {
        // Ambil konfigurasi dari config/services.php
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        // Validasi URL - harus string dan URL valid
        if (! is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return $this->hasilApiKosong();  // Kembalikan array kosong jika URL tidak valid
        }

        // Validasi token - tidak boleh kosong
        if (! is_string($token) || $token === '') {
            return $this->hasilApiKosong();  // Kembalikan array kosong jika token kosong
        }

        // Bangun URL endpoint API dengan menggabungkan base URL + endpoint
        $url = rtrim($baseUrl, '/').'/v2/mahasiswa/lulusan';

        try {
            // Kirim request HTTP ke API dengan setting timeout
            $response = Http::connectTimeout(5)      // Timeout koneksi 5 detik
                ->timeout(15)                         // Timeout total 15 detik
                ->acceptJson()                        // Terima response JSON
                ->withToken($token)                   // Kirim token sebagai Bearer token
                ->withHeaders([                       // Header tambahan untuk menghindari blokir
                    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',
                    'Referer' => rtrim($baseUrl, '/').'/',
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
                ])
                ->get($url, $parameter);  // GET request dengan parameter filter

        } catch (ConnectionException) {
            // Jika koneksi gagal (timeout, DNS error, dll), kembalikan array kosong
            return $this->hasilApiKosong();
        }

        // Cek apakah response sukses (status code 2xx)
        if (! $response->successful()) {
            return $this->hasilApiKosong();  // Kembalikan array kosong jika response error
        }

        // Parse JSON response menjadi array PHP
        $isiResponse = $response->json();

        // Ekstrak data pagination dari response (biasanya di level pertama)
        $hasilPagination = data_get($isiResponse, 'data.0');

        // Jika tidak ada data pagination, kembalikan array kosong
        if (! is_array($hasilPagination)) {
            return $this->hasilApiKosong();
        }

        // Ambil array data mahasiswa dari pagination object
        // Gunakan ?? [] untuk default ke array kosong jika tidak ada 'data' key
        $dataMahasiswa = $hasilPagination['data'] ?? [];

        // Pastikan dataMahasiswa adalah array
        if (! is_array($dataMahasiswa)) {
            $dataMahasiswa = [];
        }

        // Kembalikan data dengan struktur yang rapi dan mudah digunakan di controller/view
        return [
            'tersedia' => true,                                      // API berhasil
            'data' => $dataMahasiswa,                                // Array mahasiswa
            'total' => (int) ($hasilPagination['total'] ?? 0),        // Total records di database
            'halaman_sekarang' => (int) ($hasilPagination['current_page'] ?? 1), // Halaman saat ini
            'halaman_terakhir' => (int) ($hasilPagination['last_page'] ?? 1),    // Halaman terakhir
        ];
    }

    /**
     * FUNGSI PRIVATE: Return struktur array ketika API tidak tersedia
     *
     * Fungsi helper untuk menstandarisasi return value ketika API error,
     * sehingga tidak perlu cek isset di tempat lain
     *
     * @return array<string, mixed>
     */
    private function hasilApiKosong(): array
    {
        return [
            'tersedia' => false,           // Tandai API tidak tersedia
            'data' => [],                  // Data kosong
            'total' => 0,                  // Total 0
            'halaman_sekarang' => 1,       // Default halaman 1
            'halaman_terakhir' => 1,       // Default halaman terakhir 1
        ];
    }
}
