<?php

namespace Database\Seeders;

use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use Illuminate\Database\Seeder;

class WmsHighlyMarelliSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Warehouse
        $whse = WmsWarehouse::firstOrCreate([
            'whse_code' => 'J06',
            'whse_name' => 'Gudang 06 Jakarta',
        ]);

        // 2. Create 5 Racks
        for ($r = 1; $r <= 5; $r++) {
            $rackCode = str_pad($r, 3, '0', STR_PAD_LEFT);
            $rack = WmsRack::firstOrCreate([
                'whse_id' => $whse->id,
                'rack_code' => $rackCode,
            ]);

            // 3. Create 2 Levels and 4 Slots per level
            for ($l = 1; $l <= 2; $l++) {
                for ($s = 1; $s <= 4; $s++) {
                    $posCode = sprintf('%s-HM-%s-L%sS%s', $whse->whse_code, $rackCode, $l, $s);

                    WmsPosition::updateOrCreate(
                        ['position_code' => $posCode],
                        [
                            'rack_id' => $rack->id,
                            'level_no' => $l,
                            'slot_no' => $s,
                            'customer_code' => 'HM',
                            'status' => 'EMPTY',
                        ]
                    );
                }
            }
        }
    }
}
