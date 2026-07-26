<?php

namespace App\Console\Commands;

use App\Models\Prodi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

#[Signature('sync:prodi')]
#[Description('GET Prodi dari API Siakang')]
class SyncProdiCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncronizing Prodi...');

        $baseUrl = config('services.siakang.base_url');
        $token = config('services.siakang.token');

        if (empty($baseUrl) || empty($token)) {
            $this->error('Base URL atau Token API belum diset di .env!');
            return Command::FAILURE;
        }

        $url = rtrim($baseUrl, '/') . '/v2/prodi';
        $bar = null;

        while ($url) {
            try {
                $response = Http::connectTimeout(5)
                    ->timeout(15)
                    ->acceptJson()
                    ->withToken($token)
                    ->get($url);
            } catch (ConnectionException $e) {
                $this->error('Koneksi ke API SIAKANG terputus saat mengambil: ' . $url);
                return Command::FAILURE;
            }

            if (!$response->successful()) {
                $this->error('Gagal mengambil data dari API. Status: ' . $response->status());
                return Command::FAILURE;
            }

            $isiResponse = $response->json();

            $paginator = $isiResponse['data'][0] ?? [];
            $dataProdi = $paginator['data'] ?? [];

            if (empty($dataProdi)) {
                $this->warn('Data Prodi kosong atau selesai.');
                break;
            }

            if ($bar === null) {
                $totalData = $paginator['total'] ?? 0;
                $bar = $this->output->createProgressBar($totalData);
                $bar->start();
            }

            // Masukkan data ke database lokal
            foreach ($dataProdi as $item) {
                Prodi::updateOrCreate(
                    [
                        'id' => $item['id'],
                    ],
                    [
                        'kode_prodi' => $item['kode_prodi'] ?? null,
                        'nama_prodi' => $item['nama_prodi'] ?? null,
                        'jenjang' => $item['jenjang_id'] ?? null,
                    ],
                );

                $bar->advance();
            }

            $url = $paginator['next_page_url'] ?? null;
        }

        if ($bar) {
            $bar->finish();
        }

        $this->newLine(2);
        $this->info('Finished');

        return Command::SUCCESS;
    }
}
