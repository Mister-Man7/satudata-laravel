<?php

/**
 * ============================================
 * CONTOH KODE API PALING SEDERHANA
 * ============================================
 * File ini menunjukkan versi paling sederhana
 * dari apa yang ada di AkademikController.php
 * 
 * Gunakan ini sebagai referensi untuk memahami
 * konsep dasar sebelum belajar kode yang lebih kompleks.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ContohSederhanaController extends Controller
{
    /**
     * CONTOH 1: GET DATA DARI API (SANGAT SEDERHANA)
     * 
     * Alur:
     * 1. Ambil URL dan token dari konfigurasi
     * 2. Kirim request GET ke API
     * 3. Return response ke view
     */
    public function contohDasarAmbilData()
    {
        // 1. Siapkan URL dan token
        $url = 'https://api.siakang.com/v2/mahasiswa/lulusan';
        $token = 'my-secret-token-123';

        // 2. Kirim request GET
        $response = Http::withToken($token)
            ->acceptJson()
            ->get($url, [
                'limit' => 25,
                'page' => 1,
            ]);

        // 3. Return data ke view
        return view('list-mahasiswa', [
            'mahasiswa' => $response->json()
        ]);
    }

    /**
     * CONTOH 2: DENGAN ERROR HANDLING (LEBIH AMAN)
     * 
     * Selalu gunakan try-catch untuk API calls!
     */
    public function contohDenganErrorHandling()
    {
        try {
            // Setup request dengan timeout
            $response = Http::timeout(15)    // Tunggu maksimal 15 detik
                ->withToken('my-token')
                ->acceptJson()
                ->get('https://api.siakang.com/v2/mahasiswa/lulusan');

            // Cek apakah request sukses (status 200-299)
            if ($response->successful()) {
                $data = $response->json();
                return view('list-mahasiswa', ['mahasiswa' => $data]);
            } else {
                // Response error (status 4xx atau 5xx)
                return view('error', ['pesan' => 'API tidak responsif']);
            }

        } catch (ConnectionException $e) {
            // Koneksi timeout atau network error
            return view('error', ['pesan' => 'Jaringan terputus']);
        }
    }

    /**
     * CONTOH 3: DENGAN VALIDASI INPUT DARI USER
     * 
     * Ketika user mengirim filter (search, page, dll)
     * kita harus validasi dulu sebelum digunakan!
     */
    public function contohDenganValidasiInput($request)
    {
        // Validasi input dari user
        $filter = $request->validate([
            'search' => 'nullable|string|max:100',  // Boleh kosong, max 100 karakter
            'page' => 'nullable|integer|min:1',     // Boleh kosong, minimal 1
        ]);

        // Siapkan parameter untuk API
        $params = [
            'limit' => 25,
            'page' => $filter['page'] ?? 1,  // Default ke 1 jika kosong
        ];

        // Tambahkan search parameter jika ada
        if (!empty($filter['search'])) {
            $params['search'] = $filter['search'];
        }

        // Ambil data dari API
        $response = Http::withToken('my-token')
            ->get('https://api.siakang.com/v2/mahasiswa/lulusan', $params);

        return $response->json();
    }

    /**
     * CONTOH 4: MEMBUAT FUNCTION PEMBANTU (PRIVATE FUNCTION)
     * 
     * Ini adalah cara profesional: pisahkan logic ke function terpisah
     * agar kode lebih rapi dan bisa digunakan berulang kali.
     */
    public function contohDenganPrivateFunction()
    {
        // Panggil private function
        $dataMahasiswa = $this->ambilDataDariAPI([
            'limit' => 25,
            'page' => 1,
        ]);

        return view('list-mahasiswa', ['mahasiswa' => $dataMahasiswa]);
    }

    /**
     * Private function: Ambil data dari API
     * (hanya bisa dipanggil dari dalam controller ini)
     */
    private function ambilDataDariAPI($parameter)
    {
        try {
            // Kirim request
            $response = Http::timeout(15)
                ->withToken('my-token')
                ->acceptJson()
                ->get('https://api.siakang.com/v2/mahasiswa/lulusan', $parameter);

            // Cek sukses atau tidak
            if (!$response->successful()) {
                return [];  // Return array kosong jika error
            }

            // Return data
            return $response->json();

        } catch (ConnectionException) {
            return [];  // Return array kosong jika timeout
        }
    }

    /**
     * CONTOH 5: EXTRACT DATA DARI RESPONSE
     * 
     * Kadang response API berstruktur nested (data dalam data).
     * Gunakan data_get() untuk extract dengan aman.
     */
    public function contohExtractData()
    {
        $response = Http::withToken('my-token')->get('...');
        $data = $response->json();

        // ❌ BAHAYA - Bisa error jika key tidak ada
        // $mahasiswa = $data['data'][0]['mahasiswa'];

        // ✅ AMAN - data_get() return null jika key tidak ada
        $mahasiswa = data_get($data, 'data.0.mahasiswa');

        // ✅ AMAN DENGAN DEFAULT VALUE
        $total = data_get($data, 'pagination.total', 0);  // Default ke 0
    }

    /**
     * CONTOH 6: KODE PALING LENGKAP (STANDAR PRODUKSI)
     * 
     * Ini adalah cara ideal untuk production code.
     */
    public function contohStandarProduksi($request)
    {
        // 1. VALIDASI INPUT
        $filter = $request->validate([
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        // 2. SIAPKAN PARAMETER
        $params = ['limit' => 25, 'page' => $filter['page'] ?? 1];
        if (!empty($filter['search'])) {
            $params['search'] = $filter['search'];
        }

        // 3. AMBIL DATA DARI API (VIA PRIVATE FUNCTION)
        $hasil = $this->ambilDataWithErrorHandling($params);

        // 4. RETURN KE VIEW
        return view('list-mahasiswa', [
            'mahasiswa' => $hasil['data'] ?? [],
            'tersedia' => $hasil['tersedia'],
            'pesan_error' => $hasil['pesan_error'] ?? null,
        ]);
    }

    /**
     * Private function dengan error handling lengkap
     */
    private function ambilDataWithErrorHandling($parameter)
    {
        // Validasi konfigurasi
        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (empty($baseUrl) || empty($token)) {
            return [
                'tersedia' => false,
                'data' => [],
                'pesan_error' => 'Konfigurasi API tidak lengkap',
            ];
        }

        try {
            // Kirim request dengan timeout
            $response = Http::connectTimeout(5)   // Timeout koneksi
                ->timeout(15)                     // Timeout total
                ->withToken($token)
                ->acceptJson()
                ->get($baseUrl . '/v2/mahasiswa/lulusan', $parameter);

            // Cek response
            if (!$response->successful()) {
                return [
                    'tersedia' => false,
                    'data' => [],
                    'pesan_error' => 'API mengembalikan error (status ' . $response->status() . ')',
                ];
            }

            // Extract data
            $data = $response->json();
            return [
                'tersedia' => true,
                'data' => $data,
                'pesan_error' => null,
            ];

        } catch (ConnectionException $e) {
            return [
                'tersedia' => false,
                'data' => [],
                'pesan_error' => 'Koneksi ke API timeout atau gagal',
            ];
        }
    }
}

/**
 * ============================================
 * RINGKASAN POIN PENTING
 * ============================================
 * 
 * 1. SELALU GUNAKAN TRY-CATCH UNTUK API CALLS
 *    - API bisa timeout
 *    - Network bisa terputus
 *    - Server bisa error
 * 
 * 2. VALIDASI INPUT DARI USER
 *    - User bisa kirim data yang berbahaya
 *    - Gunakan $request->validate()
 * 
 * 3. CEK RESPONSE SUKSES
 *    - Jangan langsung json_decode
 *    - Gunakan $response->successful()
 * 
 * 4. EXTRACT DATA DENGAN AMAN
 *    - Gunakan data_get() bukan array access langsung
 *    - Sediakan default value
 * 
 * 5. PISAHKAN LOGIC KE PRIVATE FUNCTION
 *    - Kode lebih rapi
 *    - Bisa digunakan berulang kali
 *    - Lebih mudah di-test
 * 
 * 6. RETURN STRUKTUR DATA YANG KONSISTEN
 *    - Selalu return array dengan kunci yang sama
 *    - Mudah dihandle di view/controller lain
 */
