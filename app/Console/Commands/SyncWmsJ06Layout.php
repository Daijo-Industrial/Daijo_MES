<?php

namespace App\Console\Commands;

use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWmsJ06Layout extends Command
{
    protected $signature = 'wms:sync-j06-layout';

    protected $description = 'Sync physical layout coordinates and racks for Gudang 06 (J06 / Logistic & Store)';

    public function handle()
    {
        $this->info('Starting WMS Gudang 06 Layout Synchronization...');

        $whse = WmsWarehouse::firstOrCreate(
            ['whse_code' => 'J06'],
            [
                'whse_name' => 'Gudang 06 (Logistic & Store)',
                'exit_location' => 'BOTTOM_CENTER',
            ]
        );

        // Exit at Bottom-Center (Green Arrow): X = 500, Y = 730
        $exitX = 500;
        $exitY = 730;

        $rackDefinitions = [
            // Top Horizontal Racks
            'F41' => ['x' => 380, 'y' => 90, 'w' => 120, 'h' => 36, 'levels' => 3, 'slots' => 6, 'orient' => 'HORIZONTAL', 'zone' => 'LOGISTIC'],
            'W10' => ['x' => 520, 'y' => 90, 'w' => 120, 'h' => 36, 'levels' => 3, 'slots' => 6, 'orient' => 'HORIZONTAL', 'zone' => 'STORE'],

            // Vertical Racks (Columns from Left to Right)
            // Column 1 (Single) - Green
            'F33' => ['x' => 170, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],

            // Pair 2 - Green
            'F34' => ['x' => 220, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],
            'F35' => ['x' => 256, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],

            // Pair 3 - Green
            'F36' => ['x' => 310, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],
            'F37' => ['x' => 346, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],

            // Pair 4 - Green
            'F38' => ['x' => 400, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],
            'F39' => ['x' => 436, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],

            // Pair 5 - Boundary Pair (F40 Green, W09 Blue)
            'F40' => ['x' => 490, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'LOGISTIC'],
            'W09' => ['x' => 526, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],

            // Pair 6 - Blue (DIRECTLY ABOVE GREEN EXIT ARROW)
            'W08' => ['x' => 580, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],
            'W07' => ['x' => 616, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],

            // Pair 7 - Blue (DIRECTLY ABOVE GREEN EXIT ARROW)
            'W06' => ['x' => 670, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],
            'W05' => ['x' => 706, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],

            // Pair 8 - Blue
            'W04' => ['x' => 760, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],
            'W03' => ['x' => 796, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],

            // Pair 9 - Blue (Far Right)
            'W02' => ['x' => 850, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],
            'W01' => ['x' => 886, 'y' => 420, 'w' => 36, 'h' => 200, 'levels' => 3, 'slots' => 8, 'orient' => 'VERTICAL', 'zone' => 'STORE'],
        ];

        $rankings = [];

        DB::beginTransaction();
        try {
            foreach ($rackDefinitions as $code => $def) {
                $rack = WmsRack::withTrashed()->firstOrNew([
                    'whse_id' => $whse->id,
                    'rack_code' => $code,
                ]);

                if ($rack->trashed()) {
                    $rack->restore();
                }

                $rack->x_pos = $def['x'];
                $rack->y_pos = $def['y'];
                $rack->width = $def['w'];
                $rack->height = $def['h'];
                $rack->orientation = $def['orient'];
                $rack->save();

                // Create default positions if rack has none
                if ($rack->positions()->count() === 0) {
                    for ($lvl = 1; $lvl <= $def['levels']; $lvl++) {
                        for ($slot = 1; $slot <= $def['slots']; $slot++) {
                            $posCode = sprintf('%s-L%02d-S%02d', $code, $lvl, $slot);
                            WmsPosition::create([
                                'rack_id' => $rack->id,
                                'level_no' => $lvl,
                                'slot_no' => $slot,
                                'position_code' => $posCode,
                                'max_capacity' => 1,
                                'status' => 'EMPTY',
                            ]);
                        }
                    }
                }

                $distance = abs($def['x'] - $exitX) + abs($def['y'] - $exitY);
                $rankings[] = [
                    'rack' => $code,
                    'zone' => $def['zone'],
                    'x' => $def['x'],
                    'y' => $def['y'],
                    'distance' => $distance,
                    'slots' => $rack->positions()->count(),
                ];
            }

            DB::commit();
            $this->info('Gudang 06 (J06) Layout sync completed successfully!');

            usort($rankings, fn ($a, $b) => $a['distance'] <=> $b['distance']);

            $this->table(
                ['Rank', 'Kode Rak', 'Zona', 'X Pos', 'Y Pos', 'Jarak Manhattan ke Panah Hijau Exit', 'Total Slots', 'Status'],
                array_map(function ($idx, $r) use ($rankings) {
                    $status = $idx === 0 ? '🏆 Paling Dekat' : ($idx === count($rankings) - 1 ? '⚠️ Paling Jauh' : 'Normal');

                    return [
                        $idx + 1,
                        $r['rack'],
                        $r['zone'],
                        $r['x'],
                        $r['y'],
                        $r['distance'] . ' px',
                        $r['slots'],
                        $status,
                    ];
                }, array_keys($rankings), $rankings)
            );

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Sync failed: ' . $e->getMessage());

            return 1;
        }
    }
}
