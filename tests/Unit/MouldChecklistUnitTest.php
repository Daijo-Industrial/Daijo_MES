<?php

namespace Tests\Unit;

use App\Models\MouldChecklist;
use Carbon\Carbon;
use Tests\TestCase;

class MouldChecklistUnitTest extends TestCase
{
    public function test_get_point_check_options_splits_compound_point_names(): void
    {
        // Point 5: 'ANGULAR PIN / ANGULAR CAMS'
        $options = MouldChecklist::getPointCheckOptions(5);
        $this->assertContains('ANGULAR PIN / ANGULAR CAMS', $options);
        $this->assertContains('ANGULAR PIN', $options);
        $this->assertContains('ANGULAR CAMS', $options);

        // Point 1: 'COOLING CIRCULATION' (no slash)
        $simple = MouldChecklist::getPointCheckOptions(1);
        $this->assertEquals(['COOLING CIRCULATION'], $simple);

        // Non existent point
        $this->assertEquals([], MouldChecklist::getPointCheckOptions(999));
    }

    public function test_work_schedule_formatted_ongoing(): void
    {
        $checklist = new MouldChecklist([
            'is_ongoing' => true,
            'start_at' => Carbon::parse('2026-11-20 08:30:00'),
        ]);

        $this->assertStringContainsString('Ongoing', $checklist->work_schedule_formatted);
        $this->assertStringContainsString('08:30', $checklist->work_schedule_formatted);
    }

    public function test_work_schedule_formatted_same_day(): void
    {
        $checklist = new MouldChecklist([
            'is_ongoing' => false,
            'start_at' => Carbon::parse('2026-11-20 08:00:00'),
            'end_at' => Carbon::parse('2026-11-20 12:00:00'),
        ]);

        $formatted = $checklist->work_schedule_formatted;
        $this->assertStringContainsString('08:00 - 12:00', $formatted);
    }

    public function test_work_schedule_formatted_cross_days(): void
    {
        $checklist = new MouldChecklist([
            'is_ongoing' => false,
            'start_at' => Carbon::parse('2026-11-20 22:00:00'),
            'end_at' => Carbon::parse('2026-11-21 06:00:00'),
        ]);

        $formatted = $checklist->work_schedule_formatted;
        $this->assertStringContainsString('s/d', $formatted);
        $this->assertStringContainsString('+1 hari', $formatted);
    }
}
