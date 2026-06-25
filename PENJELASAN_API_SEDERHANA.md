# 📚 Penjelasan API untuk Pemula

Dokumen ini menjelaskan kode API dengan bahasa yang **sederhana, mudah dipahami, dan humanis**. 

---

## 🎯 Konsep Dasar yang Perlu Diketahui

### Apa itu API?
API (Application Programming Interface) adalah cara agar aplikasi kita bisa berbicara dengan aplikasi lain. Seperti memesan makanan:
- **Kamu**: Pelanggan (aplikasi Laravel kita)
- **Restoran**: Penyedia layanan (server API)
- **Pesanan**: Data yang dikirim dari kamu ke restoran

### Apa itu HTTP Request?
Cara kita mengirim pesan ke API:
- **GET**: Meminta data (tidak mengubah apa-apa)
- **POST**: Mengirim data baru
- **PUT**: Mengubah data yang ada
- **DELETE**: Menghapus data

### Apa itu Token?
Token adalah "kartu identitas" kita untuk membuktikan bahwa kita adalah pengguna yang sah.
Seperti menunjukkan KTP ke restoran sebelum memesan.

---

## 📁 File-File Penting

```
app/Http/Controllers/
├── AcademicController.php     ← Ambil data akademik
└── AkademikController.php     ← Ambil data mahasiswa lulus
```

---

## 🔍 Penjelasan AcademicController.php

### Tujuan File Ini
Mengambil data akademik dari API eksternal (seperti ambil info dari sistem universitas lain).

### Alur Kerjanya

```
1. Controller menerima request dari user
   ↓
2. Ambil URL dan token dari file konfigurasi (config/services.php)
   ↓
3. Kirim request ke API eksternal dengan HTTP Client
   ↓
4. API mengirimkan response (data atau error)
   ↓
5. Cek apakah response sukses atau ada error
   ↓
6. Kembalikan data ke frontend sebagai JSON
```

### Penjelasan Kode Baris Per Baris

```php
// 1. Ambil URL API dari konfigurasi
$base_url = config('services.siakang.base_url');
// Seperti mencatat alamat restoran: "Jl. Merdeka No. 123"

// 2. Ambil token (kartu identitas) dari konfigurasi
$token = config('services.siakang.token');
// Seperti membawa KTP saat memesan

// 3. Buat HTTP client untuk mengirim request
$client = new Client();

// 4. Kirim request GET ke API
$response = $client->request('GET', $base_url, [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,  // Tunjukkan token
        'Accept' => 'application/json',         // Minta format JSON
    ],
    'http_errors' => false,                     // Jangan error otomatis
]);

// 5. Ambil status code response
if ($response->getStatusCode() === 403) {
    // Artinya: "Tidak boleh masuk" (biasanya karena Cloudflare)
    return response()->json(['message' => 'Diblokir']);
}

// 6. Kembalikan data JSON ke frontend
return response()->json(json_decode($body, true));
```

### Kapan Digunakan?
Ketika kita perlu mengambil data akademik dari sistem universitas lain secara langsung.

---

## 🔍 Penjelasan AkademikController.php

### Tujuan File Ini
Menampilkan statistik mahasiswa dan list mahasiswa lulus dengan filter.

### File ini memiliki 3 metode utama:

#### 1️⃣ Metode `index()` - Halaman Dashboard

**Apa fungsinya?**
Menampilkan dashboard dengan statistik mahasiswa (Total, Aktif, Tidak Aktif, Lulus, Baru).

**Alur kerjanya:**
```
1. Panggil API untuk ambil data mahasiswa lulus
2. Ekstrak total mahasiswa lulus dari response
3. Siapkan 5 kartu statistik untuk ditampilkan
4. Kirim ke view (tampilan) untuk ditampilkan
```

**Contoh output:**
```
┌─────────────────────────────────────┐
│ Total Mahasiswa:  -                 │
│ Mahasiswa Aktif:  -                 │
│ Mahasiswa Lulus:  250 (dari API)    │
│ Mahasiswa Baru:   -                 │
└─────────────────────────────────────┘
```

---

#### 2️⃣ Metode `mahasiswaLulus()` - Halaman List dengan Filter

**Apa fungsinya?**
Menampilkan list mahasiswa lulus dengan kemampuan filter (cari nama, prodi, angkatan, dll).

**Alur kerjanya:**
```
1. Terima filter dari user (search, kode_prodi, angkatan, tahun_lulus, page)
2. Validasi filter - pastikan data valid dan aman
3. Ubah filter menjadi parameter untuk API
4. Panggil API dengan parameter filter tersebut
5. Tampilkan hasil dalam bentuk tabel
```

**Contoh filter yang diterima:**
```
GET /akademik/mahasiswa-lulus?search=Rudi&kode_prodi=IF001&page=2
```

**Validasi filter (baris 83-89):**
```php
$filter = $request->validate([
    'search' => ['nullable', 'string', 'max:100'],
    // Artinya: Boleh kosong, harus teks, maksimal 100 karakter
    
    'kode_prodi' => ['nullable', 'string', 'max:20'],
    // Artinya: Boleh kosong, harus teks, maksimal 20 karakter
    
    'angkatan' => ['nullable', 'integer', 'between:1900,' . (now()->year + 1)],
    // Artinya: Boleh kosong, harus angka, tahun 1900 sampai tahun depan
    
    'page' => ['nullable', 'integer', 'min:1'],
    // Artinya: Boleh kosong, harus angka, minimal 1
]);
```

**Mengubah filter menjadi parameter API (baris 91-110):**
```php
$parameterApi = [
    'limit' => 25,                    // Tampilkan 25 data per halaman
    'page' => $filter['page'] ?? 1,  // Default ke halaman 1 jika kosong
];

// Tambahkan filter jika ada (tidak kosong)
if (!empty($filter['search'])) {
    $parameterApi['search'] = $filter['search'];
}
```

---

#### 3️⃣ Metode `getDataLulusan()` - Fungsi Pembantu (Private)

**Apa fungsinya?**
Fungsi private yang bertugas mengambil data dari API SIAKANG. Digunakan oleh kedua metode di atas.

**Alur kerjanya:**
```
1. Ambil konfigurasi URL dan token
2. Validasi URL dan token (tidak boleh kosong)
3. Bangun URL lengkap: baseUrl + /v2/mahasiswa/lulusan
4. TRY: Kirim HTTP request dengan timeout
   - connectTimeout(5): Maksimal 5 detik koneksi
   - timeout(15): Maksimal 15 detik tunggu response
5. CATCH: Jika error koneksi, return array kosong
6. Cek status response (2xx = sukses, 4xx/5xx = error)
7. Parse JSON response menjadi array PHP
8. Ekstrak data mahasiswa dari response
9. Return array dengan struktur yang konsisten
```

**Header khusus yang dikirim (baris 147-150):**
```php
->withHeaders([
    'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8',  // Bahasa yang diminta
    'Referer' => rtrim($baseUrl, '/') . '/',             // Dari mana request datang
    'User-Agent' => 'Mozilla/5.0...',                    // Apa browser/aplikasi kami
])
```

Ini untuk menghindari blokiran dari server API eksternal.

**Output getDataLulusan():**
```php
[
    'tersedia' => true,              // API berhasil diakses?
    'data' => [...],                 // Array mahasiswa lulus
    'total' => 250,                  // Total records di database
    'halaman_sekarang' => 2,         // Halaman saat ini
    'halaman_terakhir' => 10,        // Halaman terakhir
]
```

---

#### 4️⃣ Metode `hasilApiKosong()` - Fungsi Pembantu (Private)

**Apa fungsinya?**
Return array kosong dengan struktur standar ketika API tidak tersedia.

**Mengapa penting?**
Agar di controller kita tidak perlu cek `isset()` atau `is_null()` berkali-kali.

---

## 🛠️ Konfigurasi yang Diperlukan

File `config/services.php` harus memiliki:

```php
'siakang' => [
    'base_url' => env('SIAKANG_BASE_URL', 'https://api.siakang.example.com'),
    'token' => env('SIAKANG_TOKEN', 'your-token-here'),
],
```

Dan di file `.env` tambahkan:
```
SIAKANG_BASE_URL=https://api.siakang.example.com
SIAKANG_TOKEN=your-actual-token
```

---

## 🚨 Error Handling (Penanganan Error)

### 1. Error Koneksi
```php
try {
    $response = Http::timeout(15)->get($url);
} catch (ConnectionException) {
    // Jika timeout atau network error
    return $this->hasilApiKosong();
}
```

### 2. Error Response (4xx, 5xx)
```php
if (!$response->successful()) {
    // Status code bukan 2xx
    return $this->hasilApiKosong();
}
```

### 3. Error Data Format
```php
if (!is_array($dataMahasiswa)) {
    // Data bukan array, amankan dengan default
    $dataMahasiswa = [];
}
```

### 4. Error Cloudflare
```php
if ($response->getStatusCode() === 403 && str_contains($body, 'challenges.cloudflare.com')) {
    return response()->json([
        'message' => 'Request diblokir Cloudflare',
    ], 403);
}
```

---

## 📊 Diagram Alur Request

```
┌─ User membuka browser
│  └─ GET /akademik
│     ↓
├─ AkademikController::index()
│  ├─ Panggil getDataLulusan(['limit' => 1, 'page' => 1])
│  │  ├─ Setup HTTP Client
│  │  ├─ Build URL: https://api.siakang.com/v2/mahasiswa/lulusan?limit=1&page=1
│  │  ├─ Kirim request dengan Bearer Token
│  │  ├─ Tunggu response
│  │  └─ Return array dengan data/error
│  │     ↓
│  ├─ Extract total mahasiswa: 250
│  ├─ Setup stats array dengan 5 kartu
│  └─ Return view('akademik', $stats)
│
└─ Browser menampilkan halaman akademik.blade.php
```

---

## 💡 Tips untuk Pemula

### ✅ Hal yang Perlu Dimengerti
- Request/Response adalah komunikasi 2 arah
- Token adalah "password" untuk API (jangan hardcode di kode)
- Try-Catch untuk menangani error tanpa crash
- Validasi input dari user selalu penting
- Array structure yang konsisten memudahkan di view

### ❌ Hal yang Harus Dihindari
```php
// ❌ BURUK - Token di kode (bahaya keamanan)
$token = 'abc123xyz789';
$response = Http::withToken($token)->get($url);

// ✅ BAIK - Token di konfigurasi
$token = config('services.siakang.token');
$response = Http::withToken($token)->get($url);
```

```php
// ❌ BURUK - Tidak ada error handling
$response = Http::get($url);
$data = $response->json();
// Bisa crash jika API timeout

// ✅ BAIK - Ada error handling
try {
    $response = Http::timeout(15)->get($url);
    if ($response->successful()) {
        $data = $response->json();
    }
} catch (ConnectionException) {
    // Handle error gracefully
}
```

---

## 🎓 Topik Lanjutan (Jika Sudah Mahir)

1. **Caching** - Simpan response API di cache agar lebih cepat
2. **Queue** - Ambil data API di background (jangan blocking)
3. **Repository Pattern** - Pisahkan logic API ke class terpisah
4. **API Resources** - Format response lebih rapi dengan Resource class

---

## 🤝 Hubungi Tim

Jika ada pertanyaan atau ada bagian yang masih bingung, tanyakan ke tim development!

---

**Dibuat untuk pemula yang ingin belajar Laravel API 🚀**
