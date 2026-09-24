<?php

namespace App\Http\Controllers;

use App\Services\Integrations\SourceRevalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Status & pemicu penarikan data untuk indikator "terakhir diperbarui" dan
 * tombol "Tarik data" di halaman. Semua penarikan lewat SourceRevalidator supaya
 * jalurnya seragam (Chromium lokal, bukan HTTP PHP yang ditantang Cloudflare).
 */
class DataSourceController extends Controller
{
    public function __construct(private SourceRevalidator $sourceRevalidator)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'sources' => $this->sourceRevalidator->allStatuses(),
        ]);
    }

    public function trigger(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'source' => ['required', 'string'],
            'nip' => ['nullable', 'string'],
            'semester' => ['nullable', 'string'],
        ]);

        $result = $this->sourceRevalidator->trigger($payload['source'], [
            'nip' => (string) ($payload['nip'] ?? ''),
            'semester' => (string) ($payload['semester'] ?? ''),
        ]);

        $success = in_array($result['status'], ['jalan', 'diam'], true);

        return response()->json([
            'success' => $success,
            'status' => $result['status'],
            'message' => $result['message'],
        ], $success ? 200 : 503);
    }
}
