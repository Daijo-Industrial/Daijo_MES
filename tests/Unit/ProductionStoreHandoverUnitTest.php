<?php

namespace Tests\Unit;

use App\Models\ProductionStoreHandover;
use App\Services\ProductionStoreHandoverService;
use PHPUnit\Framework\TestCase;

class ProductionStoreHandoverUnitTest extends TestCase
{
    public function test_in_transit_boxes_calculates_difference_safely(): void
    {
        $handover = new ProductionStoreHandover([
            'scanned_boxes' => 25,
            'store_received_boxes' => 15,
        ]);

        $this->assertEquals(10, $handover->in_transit_boxes);

        // When received exceeds scanned, it should not be negative
        $handover->store_received_boxes = 30;
        $this->assertEquals(0, $handover->in_transit_boxes);
    }

    public function test_in_transit_qty_calculates_difference_safely(): void
    {
        $handover = new ProductionStoreHandover([
            'scanned_qty' => 500,
            'store_received_qty' => 300,
        ]);

        $this->assertEquals(200, $handover->in_transit_qty);

        // When received exceeds scanned, clamped to 0
        $handover->store_received_qty = 600;
        $this->assertEquals(0, $handover->in_transit_qty);
    }

    public function test_progress_percent_calculates_percentage_and_handles_division_by_zero(): void
    {
        $handover = new ProductionStoreHandover([
            'scanned_boxes' => 0,
            'store_received_boxes' => 0,
        ]);
        $this->assertEquals(0, $handover->progress_percent);

        $handover->scanned_boxes = 100;
        $handover->store_received_boxes = 45;
        $this->assertEquals(45, $handover->progress_percent);

        // Clamped at 100 max
        $handover->store_received_boxes = 120;
        $this->assertEquals(100, $handover->progress_percent);
    }

    public function test_is_karawang_detects_plant_or_machine_prefix(): void
    {
        $kbnHandover = new ProductionStoreHandover([
            'plant' => 'KBN',
            'machine_name' => 'MC-01',
        ]);
        $this->assertFalse($kbnHandover->is_karawang);

        $karawangPlant = new ProductionStoreHandover([
            'plant' => 'KARAWANG',
            'machine_name' => 'MC-01',
        ]);
        $this->assertTrue($karawangPlant->is_karawang);

        $karawangMachine = new ProductionStoreHandover([
            'plant' => 'KBN',
            'machine_name' => 'K1400C',
        ]);
        $this->assertTrue($karawangMachine->is_karawang);
    }

    public function test_service_delegates_current_production_date_to_wms_delivery_recap(): void
    {
        $serviceDate = ProductionStoreHandoverService::getCurrentProductionDate();
        $this->assertNotEmpty($serviceDate);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $serviceDate);
    }
}
