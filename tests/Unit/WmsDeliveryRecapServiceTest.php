<?php

namespace Tests\Unit;

use App\Services\WmsDeliveryRecapService;
use Carbon\Carbon;
use Tests\TestCase;

class WmsDeliveryRecapServiceTest extends TestCase
{
    public function test_determine_shift_from_time_shift_1(): void
    {
        // 07:30:00 to 15:29:59
        $time1 = Carbon::parse('2026-11-20 07:30:00', 'Asia/Jakarta');
        $this->assertEquals(1, WmsDeliveryRecapService::determineShiftFromTime($time1));

        $time2 = Carbon::parse('2026-11-20 12:00:00', 'Asia/Jakarta');
        $this->assertEquals(1, WmsDeliveryRecapService::determineShiftFromTime($time2));

        $time3 = Carbon::parse('2026-11-20 15:29:59', 'Asia/Jakarta');
        $this->assertEquals(1, WmsDeliveryRecapService::determineShiftFromTime($time3));
    }

    public function test_determine_shift_from_time_shift_2(): void
    {
        // 15:30:00 to 23:29:59
        $time1 = Carbon::parse('2026-11-20 15:30:00', 'Asia/Jakarta');
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime($time1));

        $time2 = Carbon::parse('2026-11-20 20:15:00', 'Asia/Jakarta');
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime($time2));

        $time3 = Carbon::parse('2026-11-20 23:29:59', 'Asia/Jakarta');
        $this->assertEquals(2, WmsDeliveryRecapService::determineShiftFromTime($time3));
    }

    public function test_determine_shift_from_time_shift_3(): void
    {
        // 23:30:00 to 07:29:59 next morning
        $time1 = Carbon::parse('2026-11-20 23:30:00', 'Asia/Jakarta');
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime($time1));

        $time2 = Carbon::parse('2026-11-21 02:00:00', 'Asia/Jakarta');
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime($time2));

        $time3 = Carbon::parse('2026-11-21 07:29:59', 'Asia/Jakarta');
        $this->assertEquals(3, WmsDeliveryRecapService::determineShiftFromTime($time3));
    }

    public function test_get_time_window_starts_at_0730_and_ends_at_072959_next_day(): void
    {
        [$start, $end] = WmsDeliveryRecapService::getTimeWindow('2026-11-20');

        $this->assertEquals('2026-11-20 07:30:00', $start->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-11-21 07:29:59', $end->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
    }

    public function test_current_production_date_before_0730_is_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 06:15:00', 'Asia/Jakarta'));

        $prodDate = WmsDeliveryRecapService::getCurrentProductionDate();
        $this->assertEquals('2026-11-19', $prodDate);

        Carbon::setTestNow(); // Reset test time
    }

    public function test_current_production_date_after_0730_is_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 08:00:00', 'Asia/Jakarta'));

        $prodDate = WmsDeliveryRecapService::getCurrentProductionDate();
        $this->assertEquals('2026-11-20', $prodDate);

        Carbon::setTestNow(); // Reset test time
    }
}
