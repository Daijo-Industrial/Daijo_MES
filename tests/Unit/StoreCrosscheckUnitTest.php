<?php

namespace Tests\Unit;

use App\Services\StoreCrosscheckService;
use Tests\TestCase;

class StoreCrosscheckUnitTest extends TestCase
{
    public function test_normalize_date_handles_standard_ymd(): void
    {
        $normalized = StoreCrosscheckService::normalizeDate('2026-11-24');
        $this->assertEquals('2026-11-24', $normalized);
    }

    public function test_normalize_date_handles_slash_dmy(): void
    {
        $normalized = StoreCrosscheckService::normalizeDate('24/11/2026');
        $this->assertEquals('2026-11-24', $normalized);
    }

    public function test_normalize_date_handles_slash_ymd(): void
    {
        $normalized = StoreCrosscheckService::normalizeDate('2026/11/24');
        $this->assertEquals('2026-11-24', $normalized);
    }

    public function test_normalize_date_handles_dash_dmy(): void
    {
        $normalized = StoreCrosscheckService::normalizeDate('24-11-2026');
        $this->assertEquals('2026-11-24', $normalized);
    }

    public function test_normalize_date_handles_empty_and_null_gracefully(): void
    {
        $fallback = StoreCrosscheckService::getCurrentProductionDate();

        $this->assertEquals($fallback, StoreCrosscheckService::normalizeDate(''));
        $this->assertEquals($fallback, StoreCrosscheckService::normalizeDate('   '));
        $this->assertEquals($fallback, StoreCrosscheckService::normalizeDate(null));
    }

    public function test_normalize_date_handles_corrupt_string_without_exception(): void
    {
        $fallback = StoreCrosscheckService::getCurrentProductionDate();

        $this->assertEquals($fallback, StoreCrosscheckService::normalizeDate('corrupt-date'));
        $this->assertEquals($fallback, StoreCrosscheckService::normalizeDate('2026-99-99'));
        $this->assertEquals($fallback, StoreCrosscheckService::normalizeDate('12345'));
    }

    public function test_get_time_window_never_crashes_on_slash_or_corrupt_date(): void
    {
        $service = new StoreCrosscheckService();

        [$start1, $end1] = $service->getTimeWindow('24/11/2026');
        $this->assertNotNull($start1);
        $this->assertNotNull($end1);
        $this->assertTrue($end1->greaterThan($start1));

        [$start2, $end2] = $service->getTimeWindow('');
        $this->assertNotNull($start2);
        $this->assertNotNull($end2);

        [$start3, $end3] = $service->getTimeWindow('invalid-date');
        $this->assertNotNull($start3);
        $this->assertNotNull($end3);
    }
}

