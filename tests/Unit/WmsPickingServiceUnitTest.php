<?php

namespace Tests\Unit;

use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Models\WmsPosition;
use App\Services\WmsPickingService;
use Carbon\Carbon;
use Tests\TestCase;

class WmsPickingServiceUnitTest extends TestCase
{
    private WmsPickingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WmsPickingService();
    }

    public function test_calculate_fifo_picking_route_skips_empty_or_zero_qty_requests(): void
    {
        $result = $this->service->calculateFifoPickingRoute([
            ['item_code' => '', 'quantity' => 10],
            ['item_code' => 'ITEM-01', 'quantity' => 0],
            ['item_code' => 'ITEM-02', 'quantity' => -5],
        ]);

        $this->assertEquals([], $result);
    }

    public function test_calculate_fifo_picking_route_flags_unallocated_when_no_stock_available(): void
    {
        $result = $this->service->calculateFifoPickingRoute([
            ['item_code' => 'NON-EXISTENT-PART', 'item_name' => 'Part Test', 'quantity' => 50],
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('NON-EXISTENT-PART', $result[0]['item_code']);
        $this->assertEquals(50, $result[0]['qty_to_pick']);
        $this->assertEquals('OUT_OF_STOCK', $result[0]['status']);
        $this->assertEquals('KOSONG', $result[0]['pallet_id']);
    }
}
