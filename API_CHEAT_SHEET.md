# 🚀 API Cheat Sheet untuk Pemula

Panduan cepat dan visual untuk memahami API di Laravel.

---

## 1️⃣ Anatomy of HTTP Request

```
┌────────────────────────────────────────────────┐
│   REQUEST HTTP                                  │
├────────────────────────────────────────────────┤
│                                                 │
│  GET https://api.siakang.com/v2/mahasiswa/lulusan?page=1&limit=25
│  │   │                                          │
│  │   └─ METHOD (GET, POST, PUT, DELETE)         │
│  │                                              │
│  └─ Lengkap URL                                 │
│                                                 │
│  Header:                                        │
│  - Authorization: Bearer token123              │
│  - Accept: application/json                    │
│  - Content-Type: application/json              │
│                                                 │
│  Body (untuk POST/PUT):                         │
│  {                                              │
│    "nama": "Budi Santoso",                      │
│    "email": "budi@example.com"                  │
│  }                                              │
│                                                 │
└────────────────────────────────────────────────┘

         ↓ (Dikirim ke API)

┌────────────────────────────────────────────────┐
│   RESPONSE HTTP                                 │
├────────────────────────────────────────────────┤
│                                                 │
│  Status: 200 OK                                │
│  (200 = sukses, 404 = tidak ditemukan,         │
│   500 = error server, 403 = tidak boleh)       │
│                                                 │
│  Body (JSON):                                  │
│  {                                              │
│    "data": [                                    │
│      {                                          │
│        "id": 1,                                 │
│        "nama": "Ani Suryanto",                  │
│        "npm": "2021001",                        │
│        "prodi": "Informatika"                   │
│      },                                         │
│      ...                                        │
│    ],                                           │
│    "pagination": {                              │
│      "total": 250,                              │
│      "page": 1,                                 │
│      "last_page": 10                            │
│    }                                            │
│  }                                              │
│                                                 │
└────────────────────────────────────────────────┘
```

---

## 2️⃣ Kode API Paling Sederhana

```php
// STEP 1: Kirim request
$response = Http::withToken('token123')
    ->get('https://api.example.com/mahasiswa');

// STEP 2: Ambil response
$data = $response->json();

// STEP 3: Gunakan di view
return view('list', ['mahasiswa' => $data]);
```

**Versi dengan error handling:**

```php
try {
    $response = Http::timeout(15)->withToken('token123')
        ->get('https://api.example.com/mahasiswa');
    
    if ($response->successful()) {
        $data = $response->json();
    } else {
        $data = [];
    }
} catch (Exception $e) {
    $data = [];
}

return view('list', ['mahasiswa' => $data]);
```

---

## 3️⃣ Tabel Perbandingan Method

| Method | Fungsi | Contoh |
|--------|--------|---------|
| **GET** | Ambil data | `Http::get('/mahasiswa')` |
| **POST** | Buat data baru | `Http::post('/mahasiswa', $data)` |
| **PUT** | Update data | `Http::put('/mahasiswa/1', $data)` |
| **DELETE** | Hapus data | `Http::delete('/mahasiswa/1')` |

---

## 4️⃣ Status Code HTTP

```
✅ 2xx - SUCCESS
   200: OK (Berhasil)
   201: Created (Data berhasil dibuat)
   204: No Content (Sukses tapi tidak ada data)

⚠️  3xx - REDIRECT
   301: Moved Permanently
   302: Found

❌ 4xx - CLIENT ERROR (Salah dari kita)
   400: Bad Request (Request tidak valid)
   401: Unauthorized (Tidak punya akses - token salah/hilang)
   403: Forbidden (Tidak boleh akses - Cloudflare, dll)
   404: Not Found (Data/endpoint tidak ada)

💥 5xx - SERVER ERROR (Salah dari API)
   500: Internal Server Error
   503: Service Unavailable
```

---

## 5️⃣ Working dengan Response

```php
// GET RESPONSE DALAM BERBAGAI FORMAT
$response = Http::get('...');

// ✅ Sebagai array
$data = $response->json();           // Array PHP
echo $data['id'];                    // Akses key

// ✅ Sebagai object
$obj = $response->object();          // Object
echo $obj->id;                       // Akses property

// ✅ Sebagai string
$string = (string) $response->body(); // String JSON

// ✅ Extract data aman
$id = data_get($response->json(), 'data.0.id');  // Tidak error jika key hilang
```

---

## 6️⃣ Bearer Token

```
┌─────────────────────────────────────┐
│  AUTENTIKASI DENGAN TOKEN           │
├─────────────────────────────────────┤
│                                     │
│  Token = "Kartu Identitas"          │
│  Buktikan kami adalah user yang sah │
│                                     │
│  Ada 3 cara mengirim token:         │
│                                     │
│  1. Header Authorization            │
│     Authorization: Bearer token123  │
│     → Http::withToken('token123')   │
│                                     │
│  2. Query parameter                 │
│     GET /api?token=token123         │
│     → Http::get('...?token=xyz')    │
│                                     │
│  3. Request body (POST)             │
│     POST /api                       │
│     Body: { "token": "token123" }   │
│     → Http::post('...', [...])      │
│                                     │
└─────────────────────────────────────┘

⚠️  JANGAN HARDCODE TOKEN!

❌ BURUK:
   $response = Http::withToken('abc123xyz')
   
✅ BAIK:
   $token = config('services.siakang.token');
   $response = Http::withToken($token)
```

---

## 7️⃣ Error Handling Pattern

```php
try {
    // 1. Setup timeout agar tidak hang selamanya
    $response = Http::connectTimeout(5)
        ->timeout(15)
        ->withToken($token)
        ->get($url);
        
} catch (ConnectionException $e) {
    // 2. Handle connection error (timeout, DNS error, dll)
    log('Connection error: ' . $e->getMessage());
    return $this->emptyResult();
}

// 3. Handle bad response (4xx, 5xx)
if (!$response->successful()) {
    log('API error: ' . $response->status());
    return $this->emptyResult();
}

// 4. Handle bad data format
$data = $response->json();
if (!is_array($data)) {
    log('Invalid data format');
    return $this->emptyResult();
}

// 5. Extract data safely
$mahasiswa = $data['data'] ?? [];
$total = $data['pagination']['total'] ?? 0;
```

---

## 8️⃣ Real Example dari AkademikController

```php
// ❌ SEBELUM - Kode kompleks, sulit dipahami
private function getDataLulusan(array $parameter): array {
    $baseUrl = config('services.siakang.base_url');
    $token = config('services.siakang.token');
    if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
        return $this->hasilApiKosong();
    }
    // ... 30 baris kode ...
}

// ✅ SESUDAH - Kode sudah dibuat lebih sederhana dengan komentar
// Setiap baris dijelaskan dengan komentar
private function getDataLulusan(array $parameter): array {
    // 1. Ambil konfigurasi
    $baseUrl = config('services.siakang.base_url');
    $token = config('services.siakang.token');
    
    // 2. Validasi URL dan token
    if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
        return $this->hasilApiKosong();
    }
    
    // ... kode dengan komentar lengkap ...
}
```

---

## 9️⃣ Cara Debug API

```php
// 1. Lihat request apa yang dikirim
$response = Http::withToken('token')
    ->withOptions(['debug' => true])  // Tampilkan detail request
    ->get($url);

// 2. Lihat isi response
dd($response->json());                // Dump & Die - lihat isi response
dump($response);                      // Lihat seluruh response object

// 3. Lihat status & headers
echo $response->status();              // Status code
echo $response->header('Content-Type'); // Lihat header tertentu
dd($response->headers());              // Semua headers

// 4. Lihat error
echo $response->getReasonPhrase();     // "Not Found", "Unauthorized", dll
```

---

## 🔟 Best Practices Checklist

```
SEBELUM MENGIRIM REQUEST:
☐ Timeout sudah diatur (connectTimeout + timeout)
☐ Token sudah valid dan tidak hardcoded
☐ URL sudah benar dan lengkap
☐ Input dari user sudah divalidasi

SESUDAH MENERIMA RESPONSE:
☐ Cek status code response
☐ Cek struktur data response
☐ Extract data dengan data_get() (tidak langsung array access)
☐ Handle error gracefully (return default value, jangan error)
☐ Log error untuk debugging

STRUCTURE KODE:
☐ Gunakan private function untuk reusable logic
☐ Return struktur data yang konsisten
☐ Validasi semua input dari user
☐ Tangani exception dengan try-catch
```

---

## 📚 File Pembelajaran

- **PENJELASAN_API_SEDERHANA.md** - Penjelasan detail semua kode
- **CONTOH_KODE_SEDERHANA.php** - Contoh praktis berbagai scenario
- **AkademikController.php** - Kode production dengan komentar lengkap
- **AcademicController.php** - Contoh API call sederhana

---

## 🎯 Kunci Utama

```
1. API = Komunikasi antar aplikasi via HTTP
2. Request = Pertanyaan (apa yang kita tanya ke API)
3. Response = Jawaban (apa yang API kembalikan)
4. Token = Kartu identitas (buktikan kami valid)
5. Error Handling = Jangan biarkan aplikasi crash
6. Try-Catch = Tangani exception
7. Validasi Input = Keamanan prioritas
8. Consistent Return = Mudah dihandle di controller/view
```

---

**Selamat belajar! 🎓 Mulai dari hal sederhana, perlahan-lahan naik level kompleksitas.**
