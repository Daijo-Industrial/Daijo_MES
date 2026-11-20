<?php

namespace Tests\Unit;

use App\Services\ApiLogger;
use Tests\TestCase;

class ApiLoggerTest extends TestCase
{
    public function test_api_logger_instantiates_record_with_attributes(): void
    {
        $payload = [
            'api_name' => 'SAP_SYNC',
            'method' => 'POST',
            'endpoint' => '/api/receipt-production',
            'request' => json_encode(['docDate' => '2026-11-20']),
            'response' => json_encode(['status' => 'OK']),
            'status_code' => 200,
            'status' => 'success',
            'message' => 'Synced successfully',
        ];

        // Testing the helper array mapping in ApiLogger
        $log = ApiLogger::log($payload);

        $this->assertNotNull($log);
        $this->assertEquals('SAP_SYNC', $log->api_name);
        $this->assertEquals('POST', $log->method);
        $this->assertEquals('/api/receipt-production', $log->endpoint);
        $this->assertEquals(200, $log->status_code);
        $this->assertEquals('success', $log->status);
    }
}

