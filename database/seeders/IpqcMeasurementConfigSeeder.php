<?php

namespace Database\Seeders;

use App\Models\IpqcMeasurementConfig;
use Illuminate\Database\Seeder;

class IpqcMeasurementConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['field_key' => 'act_oven_temp', 'label' => 'Act Oven Temp', 'unit' => '°C', 'sort_order' => 1],
            ['field_key' => 'gloss_meter', 'label' => 'Gloss Meter', 'unit' => null, 'sort_order' => 2],
            ['field_key' => 'color_reader_l', 'label' => 'Color Reader △L', 'unit' => null, 'sort_order' => 3],
            ['field_key' => 'color_reader_a', 'label' => 'Color Reader △A', 'unit' => null, 'sort_order' => 4],
            ['field_key' => 'color_reader_b', 'label' => 'Color Reader △B', 'unit' => null, 'sort_order' => 5],
            ['field_key' => 'alkohol_ethanol', 'label' => 'Alkohol/Ethanol Test', 'unit' => null, 'sort_order' => 6],
            ['field_key' => 'hb_pencil', 'label' => 'HB Pencil/Eraser Test', 'unit' => null, 'sort_order' => 7],
            ['field_key' => 'nichiban_test', 'label' => 'Nichiban Test', 'unit' => null, 'sort_order' => 8],
            ['field_key' => 'push_pull_test', 'label' => 'Push Pull Test', 'unit' => null, 'sort_order' => 9],
            ['field_key' => 'torsi_test', 'label' => 'Torsi Test', 'unit' => null, 'sort_order' => 10],
            ['field_key' => 'viscosity', 'label' => 'Viscosity / 2 Hours', 'unit' => 's', 'sort_order' => 11],
            ['field_key' => 'cycle_time', 'label' => 'Cycle Time / 2 Hours', 'unit' => 's', 'sort_order' => 12],
        ];

        foreach ($configs as $config) {
            IpqcMeasurementConfig::updateOrCreate(
                ['field_key' => $config['field_key']],
                [
                    'label' => $config['label'],
                    'unit' => $config['unit'],
                    'sort_order' => $config['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
