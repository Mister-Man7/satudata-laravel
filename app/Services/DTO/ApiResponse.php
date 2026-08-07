<?php

namespace App\Services\DTO;

use Illuminate\Http\Client\Response;

/**
 * DTO (Data Transfer Object) untuk membungkus seluruh response dari API.
 *
 * Controller HANYA boleh berinteraksi dengan object ini,
 * tidak boleh mengakses HTTP response secara langsung.
 */
final class ApiResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly int $status,
        public readonly string $message,
        public readonly mixed $data = null,
        public readonly ?array $errors = null,
        public readonly ?array $meta = null,
        public readonly ?string $rawMessage = null,
        public readonly ?array $rawBody = null,
    ) {}

    /**
     * Buat ApiResponse dari HTTP response yang berhasil (2xx).
     */
    public static function fromSuccessfulResponse(Response $httpResponse, mixed $data = null): self
    {
        $json = $httpResponse->json();

        // Deteksi field 'success' atau 'status' dari body API
        $apiSuccess = $json['success'] ?? $json['status'] ?? true;
        if (is_string($apiSuccess)) {
            $apiSuccess = in_array(strtolower($apiSuccess), ['true', '200', 'success'], true);
        }

        $apiMessage = $json['message'] ?? '';
        $apiData = $data ?? $json['data'] ?? $json['data'] ?? [];

        return new self(
            success: (bool) $apiSuccess,
            status: $httpResponse->status(),
            message: (string) $apiMessage,
            data: $apiData,
            meta: $json['meta'] ?? null,
            rawMessage: (string) $apiMessage,
            rawBody: is_array($json) ? $json : null,
        );
    }

    /**
     * Buat ApiResponse untuk error dari sisi client (config tidak valid, dll).
     */
    public static function configError(string $message = 'Konfigurasi API belum diatur dengan benar.'): self
    {
        return new self(
            success: false,
            status: 0,
            message: $message,
        );
    }

    /**
     * Buat ApiResponse untuk error koneksi/timeout.
     */
    public static function connectionError(
        string $message = 'Koneksi ke server API gagal atau timeout.',
        ?\Throwable $previous = null,
    ): self {
        return new self(
            success: false,
            status: 0,
            message: $message,
        );
    }

    /**
     * Buat ApiResponse untuk error HTTP non-2xx.
     */
    public static function fromErrorResponse(Response $httpResponse): self
    {
        $statusCode = $httpResponse->status();
        $json = $httpResponse->json();
        $rawMessage = $json['message'] ?? $httpResponse->body();

        $friendlyMessage = self::getFriendlyMessage($statusCode);

        $errors = null;
        if (isset($json['errors']) && is_array($json['errors'])) {
            $errors = $json['errors'];
        }

        return new self(
            success: false,
            status: $statusCode,
            message: $friendlyMessage,
            errors: $errors,
            rawMessage: is_string($rawMessage) ? $rawMessage : json_encode($rawMessage),
        );
    }

    /**
     * Buat ApiResponse untuk error tidak terduga (exception).
     */
    public static function unexpectedError(
        string $message = 'Terjadi kesalahan yang tidak terduga.',
        ?\Throwable $previous = null,
    ): self {
        return new self(
            success: false,
            status: 0,
            message: $message,
        );
    }

    /**
     * Mapping HTTP status code ke pesan user-friendly.
     *
     * Pesan asli API tetap disimpan di rawMessage untuk logging/debug.
     */
    private static function getFriendlyMessage(int $statusCode): string
    {
        return match (true) {
            // 4xx Client Errors
            $statusCode === 400 => 'Permintaan tidak valid. Silakan periksa data yang dikirim.',
            $statusCode === 401 => 'Sesi telah berakhir. Silakan masuk kembali.',
            $statusCode === 403 => 'Anda tidak memiliki akses ke data ini.',
            $statusCode === 404 => 'Data yang dicari tidak ditemukan.',
            $statusCode === 409 => 'Terjadi konflik data. Silakan coba lagi.',
            $statusCode === 422 => 'Data yang dikirim tidak valid.',
            $statusCode === 429 => 'Terlalu banyak permintaan. Silakan tunggu beberapa saat.',

            // 5xx Server Errors
            $statusCode === 500 => 'Server mengalami gangguan. Silakan coba lagi nanti.',
            $statusCode === 502 => 'Server API sedang dalam pemeliharaan.',
            $statusCode === 503 => 'Layanan sedang tidak tersedia. Silakan coba lagi nanti.',
            $statusCode === 504 => 'Server API tidak merespons tepat waktu.',

            // Cloudflare Errors
            $statusCode === 520 => 'Server API mengembalikan respons yang tidak diketahui.',
            $statusCode === 521 => 'Server web sedang down.',
            $statusCode === 522 => 'Koneksi ke server API timeout.',
            $statusCode === 523 => 'Server API tidak dapat dijangkau.',
            $statusCode === 524 => 'Terjadi timeout pada server API.',

            // Default
            default => "Terjadi masalah dengan server (status: {$statusCode}).",
        };
    }
}