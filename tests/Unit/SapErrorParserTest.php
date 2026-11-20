<?php

namespace Tests\Unit;

use App\Services\SapErrorParser;
use PHPUnit\Framework\TestCase;

class SapErrorParserTest extends TestCase
{
    public function test_parse_empty_message_returns_unknown_category(): void
    {
        $result = SapErrorParser::parse(null);
        $this->assertEquals('unknown', $result['category']);
        $this->assertNotEmpty($result['user_message']);

        $resultEmpty = SapErrorParser::parse('');
        $this->assertEquals('unknown', $resultEmpty['category']);
        $this->assertNotEmpty($resultEmpty['user_message']);
    }

    public function test_parse_detects_stock_deficit_error_5002_and_extracts_bom_line(): void
    {
        $raw = "Error [-5002]: Quantity falls below zero at line 3 of BOM";
        $result = SapErrorParser::parse($raw, 500, 'SPK-2026-001');

        $this->assertEquals('stock_deficit', $result['category']);
        $this->assertEquals('SAP-5002', $result['error_code']);
        $this->assertEquals('SPK-2026-001', $result['spk_code']);
        $this->assertStringContainsString('3', $result['bom_line'] ?? '');
    }

    public function test_parse_detects_connection_timeout(): void
    {
        $raw = "cURL error 28: Operation timed out after 30000 milliseconds with 0 bytes received";
        $result = SapErrorParser::parse($raw, 504);

        $this->assertEquals('timeout', $result['category']);
        $this->assertEquals('TIMEOUT-28', $result['error_code']);
        $this->assertEquals(504, $result['status_code']);
    }

    public function test_parse_detects_gateway_authentication_or_com_crash(): void
    {
        $raw = "Unable to cast COM object of type System.__ComObject to interface";
        $result = SapErrorParser::parse($raw, 500);

        $this->assertEquals('gateway_auth', $result['category']);
        $this->assertEquals('GATEWAY-COM', $result['error_code']);
    }

    public function test_parse_detects_duplicate_spk_already_in_sap(): void
    {
        $raw = "Document for SPK-1234 already exists in SAP Business One";
        $result = SapErrorParser::parse($raw, 200, 'SPK-1234');

        $this->assertEquals('duplicate', $result['category']);
        $this->assertEquals('SAP-DUPLICATE', $result['error_code']);
    }
}
