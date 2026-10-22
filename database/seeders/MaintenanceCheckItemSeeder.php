<?php

namespace Database\Seeders;

use App\Models\MaintenanceCheckItem;
use Illuminate\Database\Seeder;

class MaintenanceCheckItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Daily Items
            [
                'item_name' => 'Machine condition',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Berfungsi',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 1,
            ],
            [
                'item_name' => 'Check Heater',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Berfungsi',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 2,
            ],
            [
                'item_name' => 'Check Pipe Joint',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Tidak Bocor',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 3,
            ],
            [
                'item_name' => 'Check Oil Temp',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Normal (< 60)',
                'input_type' => 'numeric',
                'unit' => '°C',
                'sort_order' => 4,
            ],
            [
                'item_name' => 'Check Pump',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Normal (100-200Kgf)',
                'input_type' => 'numeric',
                'unit' => 'Kgf',
                'sort_order' => 5,
            ],
            [
                'item_name' => 'Check Oil Hydrolic',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Sesuai Standar MC',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 6,
            ],
            [
                'item_name' => 'Check Oil Lubrication',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Sesuai Standar MC',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 7,
            ],
            [
                'item_name' => 'Scraw Barel',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Suara halus',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 8,
            ],
            [
                'item_name' => 'Mini Fun Panel',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Berfungsi',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 9,
            ],
            [
                'item_name' => 'Check Automatic of Door',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Berfungsi',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 10,
            ],
            [
                'item_name' => 'Body Mesin',
                'period' => 'Daily',
                'kriteria' => 'Predictive',
                'standard' => 'Bersih',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 11,
            ],

            // Weekly Items
            [
                'item_name' => 'Grease Kolin, Guide Bar',
                'period' => 'Weekly',
                'kriteria' => 'Predictive',
                'standard' => 'Sesuai Standar MC',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 12,
            ],
            [
                'item_name' => 'Bushing Toggle',
                'period' => 'Weekly',
                'kriteria' => 'Predictive',
                'standard' => 'Suara halus',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 13,
            ],

            // Two weeks Items
            [
                'item_name' => 'Motor Hidrolik',
                'period' => 'Two weeks',
                'kriteria' => 'Predictive',
                'standard' => 'Suara halus',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 14,
            ],
            [
                'item_name' => 'Gear Pump',
                'period' => 'Two weeks',
                'kriteria' => 'Predictive',
                'standard' => 'Suara halus',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 15,
            ],
            [
                'item_name' => 'Bearing',
                'period' => 'Two weeks',
                'kriteria' => 'Predictive',
                'standard' => 'Suara halus',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 16,
            ],
            [
                'item_name' => 'Greasing Pump',
                'period' => 'Two weeks',
                'kriteria' => 'Predictive',
                'standard' => 'Berfungsi',
                'input_type' => 'ok_ng',
                'unit' => null,
                'sort_order' => 17,
            ],
        ];

        foreach ($items as $item) {
            MaintenanceCheckItem::updateOrCreate(
                ['item_name' => $item['item_name']],
                $item
            );
        }
    }
}
