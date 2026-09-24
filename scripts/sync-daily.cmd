@echo off
REM Penarik portofolio SIPP (untuk dijalankan manual atau lewat Task Scheduler).
REM
REM Chromium lokal (Edge/Chrome) dipakai karena hanya klien berbasis Chromium yang
REM lolos Cloudflare dari mesin ini; cURL PHP/.NET/Node ditolak challenge.
REM
REM Tanpa argumen: tarik 200 dosen untuk ketiga jenis, lalu 20 halaman BMN aset,
REM semuanya dilog ke storage\logs\
REM Dengan argumen: diteruskan apa adanya ke scripts\sync-portofolio.php, contoh:
REM   scripts\sync-daily.cmd --limit=25 --type=publikasi --delay=3000
REM   scripts\sync-daily.cmd --nip=196803012002121002

cd /d "%~dp0.."

if "%~1"=="" (
    php scripts\sync-portofolio.php --limit=200 --delay=4000 --type=publikasi,penelitian,pengabdian >> storage\logs\sync-sipp.log 2>&1
    php scripts\sync-penjadwalan.php --limit=200 --delay=4000 >> storage\logs\sync-sipp.log 2>&1
    REM Aset disegarkan bertahap: lanjut dari halaman terakhir, 50 halaman (~3 menit) per hari.
    REM Data BMN saat ini 102.035 baris = 1.021 halaman (100 baris/halaman); satu siklus
    REM penuh karena itu selesai dalam sekitar 3 minggu lalu mulai lagi dari halaman 1.
    php artisan sync:aset --resume --max-page=50 --limit=100 >> storage\logs\pull-aset.log 2>&1
) else (
    php scripts\sync-portofolio.php %*
)
