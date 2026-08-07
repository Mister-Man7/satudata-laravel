<?php

namespace App\Services\Integrations;

use App\Services\DTO\ApiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base class untuk semua API client.
 *
 * Setiap service只需extends class ini dan implement method config().
 * Seluruh logic HTTP request, error handling, logging, dan retry
 * sudah ditangani di sini.
 */
abstract class AbstractApiClient
{
    /**
     * Return konfigurasi API untuk service ini.
     *
     * Harus mengembalikan array dengan key:
     * - base_url: URL dasar API
     * - auth_type: 'token' | 'api_key' | 'bearer_login' | null
     * - token: bearer token (jika auth_type = 'token')
     * - api_key_header: nama header API key (jika auth_type = 'api_key')
     * - api_key_value: nilai API key (jika auth_type = 'api_key')
     * - connect_timeout: timeout koneksi dalam detik (default: 10)
     * - timeout: timeout total request dalam detik (default: 30)
     * - retries: jumlah retry untuk error 429/5xx (default: 0)
     * - retry_delay: delay antar retry dalam milidetik (default: 1000)
     */
    abstract protected function config(): array;

    /**
     * Return nama service untuk logging (e.g., 'siakang', 'simpeg').
     */
    abstract protected function serviceName(): string;

    /**
     * Kirim request GET ke API.
     */
    public function get(string $endpoint, array $queryParams = []): ApiResponse
    {
        return $this->request('GET', $endpoint, $queryParams);
    }

    /**
     * Kirim request POST ke API.
     */
    public function post(string $endpoint, array $body = []): ApiResponse
    {
        return $this->request('POST', $endpoint, $body);
    }

    /**
     * Kirim request PUT ke API.
     */
    public function put(string $endpoint, array $body = []): ApiResponse
    {
        return $this->request('PUT', $endpoint, $body);
    }

    /**
     * Kirim request DELETE ke API.
     */
    public function delete(string $endpoint): ApiResponse
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Core method untuk mengirim HTTP request.
     */
    protected function request(string $method, string $endpoint, array $payload = []): ApiResponse
    {
        $config = $this->config();

        // Validasi config
        if (!isset($config['base_url']) || empty($config['base_url'])) {
            Log::error("{$this->serviceName()}: base_url tidak dikonfigurasi");
            return ApiResponse::configError();
        }

        if (filter_var($config['base_url'], FILTER_VALIDATE_URL) === false) {
            Log::error("{$this->serviceName()}: base_url tidak valid", [
                'base_url' => $config['base_url'],
            ]);
            return ApiResponse::configError();
        }

        $url = rtrim($config['base_url'], '/') . $endpoint;
        $connectTimeout = $config['connect_timeout'] ?? 10;
        $timeout = $config['timeout'] ?? 30;

        try {
            $pendingRequest = $this->buildRequest($config)
                ->connectTimeout($connectTimeout)
                ->timeout($timeout);

            $response = match (strtoupper($method)) {
                'GET' => $pendingRequest->get($url, $payload),
                'POST' => $pendingRequest->post($url, $payload),
                'PUT' => $pendingRequest->put($url, $payload),
                'DELETE' => $pendingRequest->delete($url),
                default => throw new \InvalidArgumentException("HTTP method '{$method}' tidak didukung."),
            };
        } catch (ConnectionException $e) {
            Log::warning("{$this->serviceName()}: Koneksi gagal", [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::connectionError(previous: $e);
        } catch (\Throwable $e) {
            Log::error("{$this->serviceName()}: Error tidak terduga", [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::unexpectedError(previous: $e);
        }

        // Log semua error HTTP
        if ($response->failed()) {
            Log::warning("{$this->serviceName()}: HTTP error", [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->status(),
                'body' => $this->safeBody($response),
            ]);

            return ApiResponse::fromErrorResponse($response);
        }

        return ApiResponse::fromSuccessfulResponse($response);
    }

    /**
     * Bangun PendingRequest dengan auth, headers, dll.
     */
    protected function buildRequest(array $config): PendingRequest
    {
        $request = Http::acceptJson()
            ->withHeaders([
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
            ]);

        // Terapkan auth berdasarkan tipe
        $authType = $config['auth_type'] ?? null;

        match ($authType) {
            'token' => $request->withToken($config['token'] ?? ''),
            'api_key' => $request->withQueryParameters([
                $config['api_key_header'] ?? '' => $config['api_key_value'] ?? '',
            ]),
            'bearer_login' => $request->withToken($this->getBearerToken($config)),
            default => null,
        };

        return $request;
    }

    /**
     * Ambil body response secara aman (untuk logging).
     * Memotong body jika terlalu panjang.
     */
    protected function safeBody($response): string
    {
        $body = $response->body();
        return mb_strlen($body) > 1000 ? mb_substr($body, 0, 1000) . '...' : $body;
    }

    /**
     * Implementasikan di subclass jika perlu login untuk ambil token
     * (seperti SIPP atau Simantap).
     */
    protected function getBearerToken(array $config): string
    {
        return '';
    }
}