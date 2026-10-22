<?php

namespace App\Services;

use App\Models\ApiLog;

class ApiLogger
{
    public static function log($data)
    {
        return ApiLog::create([
            'api_name' => $data['api_name'] ?? null,
            'method' => $data['method'] ?? null,
            'endpoint' => $data['endpoint'] ?? null,
            'request_payload' => $data['request'] ?? null,
            'response_payload' => $data['response'] ?? null,
            'status_code' => $data['status_code'] ?? null,
            'status' => $data['status'] ?? 'failed',
            'message' => $data['message'] ?? null,
        ]);
    }
}
