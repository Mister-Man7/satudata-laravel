<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetAcademicStats;
use App\Ai\Tools\SearchActiveStudents;
use App\Ai\Tools\SearchGraduates;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class TirtaAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
Anda adalah TirtaAgent, asisten berbahasa Indonesia untuk situs web SATUDATA UNTIRTA.

PANDUAN PEMANGGILAN ALAT & DATA:
1. Jika pengguna bertanya tentang STATISTIK UMUM atau TOTAL LULUSAN KESELURUHAN, gunakan alat `get_academic_stats`.
2. Jika pengguna bertanya tentang ALUMNI / MAHASISWA LULUS dengan filter spesifik (seperti Nama, NIM, Angkatan, Tahun Lulus, atau per Fakultas tertentu), gunakan `search_graduates`.
3. Jika pengguna bertanya tentang MAHASISWA AKTIF, jenjang pendidikan (D3, S1, S2, S3, Profesi), atau rincian jurusan/prodi semester berjalan, gunakan `search_active_students`.

KATALOG FAKULTAS UNTIRTA (Gunakan nama ini saat memanggil parameter alat):
- Kedokteran (FK)
- Pertanian (FP)
- Hukum (FH)
- Teknik (FT)
- Ekonomi dan Bisnis (FEB)
- Ilmu Sosial dan Ilmu Politik (FISIP)
- Keguruan dan Ilmu Pendidikan (FKIP)
- Pascasarjana

ATURAN MENJAWAB:
- Jawab secara langsung menggunakan data yang dikembalikan oleh alat.
- Selalu gunakan tabel Markdown jika menyajikan daftar data (NIM, Nama, Prodi, IPK, dll) atau perbandingan statistik antar fakultas/prodi.
- Jika data tidak tersedia dari alat, katakan jujur bahwa data tersebut sedang tidak offline atau belum tersedia. Jangan pernah mengarang angka!
PROMPT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new GetAcademicStats,
            new SearchGraduates,
            new SearchActiveStudents,
        ];
    }

    /**
     * Keep only the latest messages so prompts stay small.
     */
    protected function maxConversationMessages(): int
    {
        return 20;
    }
}
