<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    /**
     * Mengambil data akademik dari API eksternal
     *
     * CARA KERJA:
     * 1. Ambil konfigurasi URL dan token dari file config
     * 2. Kirim request ke API eksternal dengan Bearer token
     * 3. Tangani error Cloudflare jika terjadi
     * 4. Kembalikan data ke frontend
     */
    public function index()
    {
        // Inisialisasi HTTP client untuk mengirim request
        $client = new Client;

        // Ambil URL dan token dari konfigurasi (config/services.php)
        $base_url = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        // Kirim GET request ke API eksternal
        $response = $client->request('GET', $base_url, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,  // Token untuk autentikasi
                'Accept' => 'application/json',          // Minta respons dalam format JSON
            ],
            'http_errors' => false,  // Jangan throw exception jika error (agar bisa handle manual)
        ]);

        // Ambil tipe konten dan body dari response
        $contentType = $response->getHeaderLine('Content-Type');
        $body = (string) $response->getBody();

        // Cek apakah terjadi error Cloudflare (status 403)
        if ($response->getStatusCode() === 403 && str_contains($body, 'challenges.cloudflare.com')) {
            return response()->json([
                'message' => 'Request diblokir Cloudflare. Backend Laravel tidak bisa langsung mengakses endpoint ini.',
                'status' => 403,
            ], 403);
        }

        // Kembalikan response sebagai JSON
        // json_decode($body, true) mengubah JSON string menjadi array PHP
        return response()->json(json_decode($body, true));
    }

    /**
     * Method untuk membuat data baru (belum diimplementasi)
     */
    public function store(Request $request)
    {
        // TODO: Implementasi untuk membuat data baru
    }

    /**
     * Method untuk menampilkan detail satu data (belum diimplementasi)
     */
    public function show(string $id)
    {
        // TODO: Implementasi untuk menampilkan satu data berdasarkan ID
    }

    /**
     * Method untuk mengupdate data (belum diimplementasi)
     */
    public function update(Request $request, string $id)
    {
        // TODO: Implementasi untuk update data
    }

    /**
     * Method untuk menghapus data (belum diimplementasi)
     */
    public function destroy(string $id)
    {
        // TODO: Implementasi untuk delete data
    }
}
