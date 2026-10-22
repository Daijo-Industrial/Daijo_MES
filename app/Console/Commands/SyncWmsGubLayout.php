<?php

namespace App\Console\Commands;

use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWmsGubLayout extends Command
{
    protected $signature = 'wms:sync-gub-layout';

    protected $description = 'Sync physical layout coordinates (x_pos, y_pos) and register missing racks for GUB warehouse';

    public function handle()
    {
        $this->info('Starting WMS GUB Layout Synchronization...');

        $whse = WmsWarehouse::firstOrCreate(
            ['id' => 1],
            ['whse_code' => 'GUB', 'whse_name' => 'Gudang Utama B']
        );

        $rackDefinitions = [
            // Left Block (Top to Bottom)
            'F31' => ['x' => 80,  'y' => 90,  'w' => 360, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F30' => ['x' => 80,  'y' => 130, 'w' => 360, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F29' => ['x' => 80,  'y' => 250, 'w' => 360, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F28' => ['x' => 80,  'y' => 290, 'w' => 360, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F27' => ['x' => 80,  'y' => 420, 'w' => 360, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F26' => ['x' => 80,  'y' => 460, 'w' => 360, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F32' => ['x' => 130, 'y' => 620, 'w' => 310, 'h' => 36, 'levels' => 2, 'slots' => 14],

            // Right Block (Top to Bottom)
            'R05' => ['x' => 510, 'y' => 80,  'w' => 420, 'h' => 36, 'levels' => 3, 'slots' => 10],
            'R04' => ['x' => 510, 'y' => 135, 'w' => 420, 'h' => 36, 'levels' => 3, 'slots' => 10],
            'R03' => ['x' => 510, 'y' => 175, 'w' => 420, 'h' => 36, 'levels' => 3, 'slots' => 10],
            'R02' => ['x' => 510, 'y' => 245, 'w' => 420, 'h' => 36, 'levels' => 3, 'slots' => 10],
            'R01' => ['x' => 510, 'y' => 285, 'w' => 420, 'h' => 36, 'levels' => 3, 'slots' => 10],
            'F23' => ['x' => 510, 'y' => 420, 'w' => 420, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F24' => ['x' => 510, 'y' => 460, 'w' => 420, 'h' => 36, 'levels' => 3, 'slots' => 14],
            'F25' => ['x' => 540, 'y' => 620, 'w' => 280, 'h' => 36, 'levels' => 2, 'slots' => 8],
        ];

        $exitX = 880;
        $exitY = 730;

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
                $rack->orientation = 'HORIZONTAL';
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
                    'x' => $def['x'],
                    'y' => $def['y'],
                    'distance' => $distance,
                    'slots' => $rack->positions()->count(),
                ];
            }

            DB::commit();
            $this->info('Layout sync completed successfully!');

            usort($rankings, fn ($a, $b) => $a['distance'] <=> $b['distance']);

            $this->table(
                ['Rank', 'Kode Rak', 'X Pos', 'Y Pos', 'Jarak Manhattan ke Exit', 'Total Slots', 'Status'],
                array_map(function ($idx, $r) {
                    $status = $idx === 0 ? '🏆 Paling Dekat' : ($idx === 14 ? '⚠️ Paling Jauh' : 'Normal');

                    return [
                        $idx + 1,
                        $r['rack'],
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
