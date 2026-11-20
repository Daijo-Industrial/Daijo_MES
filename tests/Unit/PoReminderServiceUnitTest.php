<?php

namespace Tests\Unit;

use App\Services\PoReminderService;
use Tests\TestCase;

class PoReminderServiceUnitTest extends TestCase
{
    public function test_calculate_returns_empty_structure_when_no_schedules_exist(): void
    {
        $service = new PoReminderService();

        // Query date far in the past or future where no schedule exists
        $result = $service->calculate('1990-01-01', '1990-01-02');

        $this->assertFalse($result['has_data']);
        $this->assertArrayHasKey('kpi', $result);
        $this->assertEquals(0, $result['kpi']['total_fg_items']);
        $this->assertEquals(0, $result['kpi']['total_delivery_qty']);
        $this->assertEquals([], $result['fg_items']);
        $this->assertEquals([], $result['view1_materials']);
        $this->assertEquals([], $result['view2_materials']);
    }
}

