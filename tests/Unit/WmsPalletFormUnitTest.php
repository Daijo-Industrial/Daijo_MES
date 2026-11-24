<?php

namespace Tests\Unit;

use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use Tests\TestCase;

class WmsPalletFormUnitTest extends TestCase
{
    public function test_get_plant_attribute_returns_explicit_attribute(): void
    {
        $pallet = new WmsPalletForm;
        $pallet->plant = 'KARAWANG';
        $this->assertEquals('KARAWANG', $pallet->plant);

        $pallet->plant = 'kbn';
        $this->assertEquals('KBN', $pallet->plant);
    }

    public function test_get_plant_attribute_falls_back_to_details_warehouse_prefix(): void
    {
        $pallet = new WmsPalletForm;
        // plant attribute is empty
        $pallet->setRawAttributes([]);

        $detail1 = new WmsPalletFormDetail;
        $detail1->warehouse = 'KRFG';
        $pallet->setRelation('details', collect([$detail1]));

        $this->assertEquals('KARAWANG', $pallet->plant);

        $detail2 = new WmsPalletFormDetail;
        $detail2->warehouse = 'FG';
        $pallet->setRelation('details', collect([$detail2]));

        $this->assertEquals('KBN', $pallet->plant);
    }

    public function test_get_merged_from_list_returns_empty_when_no_records(): void
    {
        $pallet = new WmsPalletForm;
        $pallet->merged_from = [];

        $this->assertEquals([], $pallet->getMergedFromList());
    }

    public function test_get_merged_from_list_returns_existing_merged_from_array(): void
    {
        $mergedData = [
            ['source_pallet_id' => 'PLT-001', 'qty' => 50, 'box_qty' => 1],
        ];

        $pallet = new WmsPalletForm;
        $pallet->merged_from = $mergedData;

        $this->assertEquals($mergedData, $pallet->getMergedFromList());
    }
}
