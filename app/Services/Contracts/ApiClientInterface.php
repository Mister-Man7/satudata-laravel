<?php

namespace App\Services\Contracts;

use App\Services\DTO\ApiResponse;

interface ApiClientInterface
{
    /**
     * Kirim request GET ke API.
     *
     * @param  string  $endpoint  Path endpoint (e.g., '/v2/mahasiswa')
     * @param  array   $queryParams  Query parameters
     * @return ApiResponse
     */
    public function get(string $endpoint, array $queryParams = []): ApiResponse;

    /**
     * Kirim request POST ke API.
     *
     * @param  string  $endpoint  Path endpoint
     * @param  array   $body  Request body
     * @return ApiResponse
     */
    public function post(string $endpoint, array $body = []): ApiResponse;
}