<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\Integrations\SIPPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SIPPController extends Controller
{
    protected SIPPService $sippService;

    public function __construct(SIPPService $sippService)
    {
        $this->sippService = $sippService;
    }

    public function testToken(): JsonResponse
    {
        try {
            $response = $this->sippService->getPublikasi(['per_page' => 1]);

            return response()->json([
                'success' => $response->success,
                'message' => $response->success ? 'Token berhasil diambil' : $response->message,
            ]);
        } catch (\Exception $e) {
            Log::error('SIPP test token error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPublikasiByNip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $params = [
            'nip' => $validated['nip'],
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['per_page'] ?? 10,
        ];

        $response = $this->sippService->getPublikasi($params);

        return response()->json([
            'success' => $response->success,
            'data' => $response->data ?? [],
            'message' => $response->message,
        ]);
    }

    public function getPenelitianByNip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $params = [
            'nip' => $validated['nip'],
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['per_page'] ?? 10,
        ];

        $response = $this->sippService->getPenelitian($params);

        return response()->json([
            'success' => $response->success,
            'data' => $response->data ?? [],
            'message' => $response->message,
        ]);
    }


}
