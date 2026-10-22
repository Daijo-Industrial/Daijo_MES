<?php

namespace App\Console\Commands;

use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Models\WmsWarehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWmsGuaLayout extends Command
{
    protected $signature = 'wms:sync-gua-layout';

    protected $description = 'Sync physical layout coordinates (x_pos, y_pos, orientation) for GUA (Gudang Utama A)';

    public function handle()
    {
        $this->info('Starting WMS GUA Layout Synchronization...');

        $whse = WmsWarehouse::where('whse_code', 'GUA')->first();
        if (! $whse) {
            $whse = WmsWarehouse::create([
                'whse_code' => 'GUA',
                'whse_name' => 'Gudang Utama A',
            ]);
        }

        // Exit at Bottom-Left near Forklift #2: X = 180, Y = 730
        $exitX = 180;
        $exitY = 730;

        $rackDefinitions = [
            // Lower Block (Pairs from Left to Right: F01/F02 to F09/F10)
            'F01' => ['x' => 240, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F02' => ['x' => 276, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F03' => ['x' => 340, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F04' => ['x' => 376, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F05' => ['x' => 440, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F06' => ['x' => 476, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F07' => ['x' => 540, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F08' => ['x' => 576, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F09' => ['x' => 640, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F10' => ['x' => 676, 'y' => 560, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],

            // Upper Block (Pairs from Left to Right: F11/F12 to F21/F22)
            'F11' => ['x' => 240, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F12' => ['x' => 276, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F13' => ['x' => 340, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F14' => ['x' => 376, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F15' => ['x' => 440, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F16' => ['x' => 476, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F17' => ['x' => 540, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F18' => ['x' => 576, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F19' => ['x' => 640, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F20' => ['x' => 676, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F21' => ['x' => 740, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
            'F22' => ['x' => 776, 'y' => 300, 'w' => 36, 'h' => 180, 'levels' => 3, 'slots' => 6],
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
                $rack->orientation = 'VERTICAL';
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
            $this->info('GUA Layout sync completed successfully!');

            usort($rankings, fn ($a, $b) => $a['distance'] <=> $b['distance']);

            $this->table(
                ['Rank', 'Kode Rak', 'X Pos', 'Y Pos', 'Jarak Manhattan ke Exit Kiri Bawah', 'Total Slots', 'Status'],
                array_map(function ($idx, $r) use ($rankings) {
                    $status = $idx === 0 ? '🏆 Paling Dekat' : ($idx === count($rankings) - 1 ? '⚠️ Paling Jauh' : 'Normal');

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
