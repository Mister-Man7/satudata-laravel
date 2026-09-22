<!-- antislop:start -->
## antislop
For UI, copy, people, mobile layout, or code comments work, load the antislop skill for the task:
- Core filter, always on: `antislop`
- UI / visual: `antislop-ui`
Before starting, ask the user when antislop applies: during the work, or after it is done.
<!-- antislop:end -->

## Aturan Edit Kode: Diff Presisi & Minimal
- **Hanya menyentuh baris kode yang memang benar-benar berubah fungsinya.**
- **Tidak melakukan reformat atau perubahan spasi/indentasi pada baris-baris kode sekitar yang tidak berubah fungsinya.**
- **Hindari Noisy Replacement:** Jika hanya menyisipkan satu baris baru setelah baris yang sudah ada, penargetan edit dilakukan tepat pada titik penyisipan tanpa menimpa ulang baris pertama yang isinya sudah sama persis (sehingga git diff tetap bersih dan mudah di-review).


## Aturan Rahasia (Secrets)
- Nilai rahasia (API key, token, password) **tidak pernah** ditulis di source, commit message, PR, komentar, atau chat. Sebut "kredensial SIMPEG", bukan nilainya.
- Nilai rahasia hanya dibaca dari `env()`/`config()` **tanpa default yang ditulis di kode**.
- Hook pemeriksa kredensial tersedia di `.githooks/`. Aktifkan sekali per klon: `git config core.hooksPath .githooks`
  Hook menolak commit bila perubahan staged atau pesan commit memuat nilai dari `.env`, atau memuat pola default hardcoded (`env('X', 'nilai')`, `config('x.y', 'nilai')`).
  Melewati hook hanya bila sadar risikonya: `SKIP_SECRET_CHECK=1 git commit ...`

## Aturan Sumber Data
- Angka dan label data **hanya** boleh berasal dari API atau database. Bila sumbernya tidak ada, tampilkan `-` (atau `N/A`) - jangan mengarang nilai, estimasi, rasio, atau label status.
- Daftar referensi (kode unit, kode prodi, jenjang, jalur masuk) diletakkan di `config/satudata.php`, tidak tersebar di controller.

