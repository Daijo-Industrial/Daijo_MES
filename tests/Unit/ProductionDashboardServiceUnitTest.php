<?php

namespace Tests\Unit;

use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\User;
use App\Services\ProductionDashboardService;
use Tests\TestCase;

class ProductionDashboardServiceUnitTest extends TestCase
{
    private ProductionDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductionDashboardService;
    }

    public function test_get_weeks_in_month_generates_correct_week_intervals(): void
    {
        // November 2026 (starts Sunday Nov 1 or whatever day, 30 days total)
        $weeks = $this->service->getWeeksInMonth(2026, 11);

        $this->assertNotEmpty($weeks);
        $this->assertEquals(1, $weeks[0]['number']);
        $this->assertEquals('2026-11-01', $weeks[0]['start']);

        $lastWeek = end($weeks);
        $this->assertEquals('2026-11-30', $lastWeek['end']);

        // Check each week structure
        foreach ($weeks as $w) {
            $this->assertArrayHasKey('number', $w);
            $this->assertArrayHasKey('label', $w);
            $this->assertArrayHasKey('start', $w);
            $this->assertArrayHasKey('end', $w);
            $this->assertLessThanOrEqual($w['end'], $w['start']);
        }
    }

    public function test_process_machine_working_hours_deduplicates_slots_and_sorts_descending(): void
    {
        $machine1 = new User(['name' => 'MC-01']);
        $machine1->id = 101;

        $machine2 = new User(['name' => 'MC-02']);
        $machine2->id = 102;

        // Daily item code for Machine 1 with 3 hourly remarks (2 unique slots)
        $dic1 = new DailyItemCode([
            'start_date' => '2026-11-20',
        ]);
        $dic1->user_id = 101;
        $dic1->setRelation('user', $machine1);

        $remark1A = new HourlyRemark(['start_time' => '08:00:00']);
        $remark1B = new HourlyRemark(['start_time' => '08:30:00']); // Same hour 8 -> should deduplicate to 1 slot
        $remark1C = new HourlyRemark(['start_time' => '09:00:00']); // Hour 9 -> 2nd slot

        $dic1->setRelation('hourlyRemarks', collect([$remark1A, $remark1B, $remark1C]));

        // Daily item code for Machine 2 with 1 slot
        $dic2 = new DailyItemCode([
            'start_date' => '2026-11-20',
        ]);
        $dic2->user_id = 102;
        $dic2->setRelation('user', $machine2);

        $remark2A = new HourlyRemark(['start_time' => '08:00:00']);
        $dic2->setRelation('hourlyRemarks', collect([$remark2A]));

        $result = $this->service->processMachineWorkingHours(collect([$dic1, $dic2]));

        $this->assertCount(2, $result);
        // Machine 1 should have 2 hours, Machine 2 should have 1 hour
        $this->assertEquals('MC-01', $result[0]['name']);
        $this->assertEquals(2, $result[0]['hours']);

        $this->assertEquals('MC-02', $result[1]['name']);
        $this->assertEquals(1, $result[1]['hours']);
    }

    public function test_process_ng_breakdown_handles_empty_collection_gracefully(): void
    {
        $breakdown = $this->service->processNgBreakdown(collect([]));

        $this->assertIsArray($breakdown);
        $this->assertEquals(0, $breakdown['total_ng'] ?? 0);
        $this->assertEquals(0, $breakdown['total_production'] ?? 0);
        $this->assertEmpty($breakdown['ng_types'] ?? []);
    }
}
