<?php

namespace App\Http\Controllers;

use App\Services\SIPPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            $token = $this->sippService->getToken();

            return response()->json([
                'success' => true,
                'token' => $token
            ]);
        } catch (\Exception $e) {
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

        $dataPublikasi = $this->sippService->getPublikasi($params);

        return response()->json([
            'success' => true,
            'data' => $dataPublikasi
        ]);
    }

    public function getPenelitianByNip(Request $request): JsonResponse
    {
        $validated = request()->validate([
            'nip' => ['required', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $params = [
            'nip' => $validated['nip'],
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['per_page'] ?? 10,
        ];

        $dataPenelitian = $this->sippService->getPenelitian($params);

        return response()->json([
            'success' => true,
            'data' => $dataPenelitian
        ]);
    }


}
