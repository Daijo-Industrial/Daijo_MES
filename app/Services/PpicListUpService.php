<?php

namespace App\Services;

use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MasterListItem;
use App\Models\PpicListUp;
use App\Models\ProductionOutputLog;
use App\Models\SpkMaster;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PpicListUpService
{
    /**
     * Resolve all part details automatically based on PPIC rules
     */
    public function resolvePartDetails(string $partNo): array
    {
        $partNo = trim($partNo);

        $item = MasterListItem::where('item_code', $partNo)->first();
        $description = $item?->item_name ?? '';
        $cavity = (int) ($item?->cavity ?? 1);
        $cycleTime = (int) ($item?->cycle_time ?? 0);
        $targetPerHour = $cycleTime > 0 ? (int) round(3600 / $cycleTime) : 0;

        // Matching SPKs from spk_masters for this part no
        $cleanPart = rtrim($partNo, '.');
        $matchingSpks = SpkMaster::where(function ($q) use ($partNo, $cleanPart) {
            $q->where('item_code', $partNo)
                ->orWhere('item_code', $cleanPart)
                ->orWhere('item_code', 'LIKE', "{$cleanPart}%");
        })
            ->orderBy('post_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $availableSpks = $matchingSpks->map(function ($s) {
            $rem = max(0, (float) $s->planned_quantity - (float) $s->completed_quantity);

            return [
                'spk_number' => $s->spk_number,
                'planned_quantity' => (float) $s->planned_quantity,
                'remaining_quantity' => $rem,
                'post_date' => $s->post_date ? Carbon::parse($s->post_date)->format('Y-m-d') : '-',
                'production_status' => $s->production_status ?: '-',
            ];
        })->toArray();

        // Pick oldest open SPK, or oldest SPK
        $oldestSpk = $matchingSpks->first(function ($s) {
            return empty($s->production_status) || $s->production_status !== 'C';
        }) ?: $matchingSpks->first();

        $spkNo = $oldestSpk?->spk_number ?? '';

        // Material Type logic:
        // Cari components dari master_bom_components, lalu compare ke master_list_materials
        $materialType = '';
        $bomComponents = DB::table('master_bom_components')
            ->where('parent_item', $partNo)
            ->pluck('component_item')
            ->toArray();

        if (empty($bomComponents)) {
            $bomComponents = DB::table('master_boms')
                ->where('parent_item', $partNo)
                ->pluck('component_item')
                ->toArray();
        }

        if (! empty($bomComponents)) {
            // Compare to master_list_materials
            $matched = DB::table('master_list_materials')
                ->whereIn('item_code', $bomComponents)
                ->first();

            if ($matched) {
                $materialType = $matched->item_code;
            } else {
                // Fallback: look for component with RAW_MATERIAL or starts with 40
                $rawComp = DB::table('master_bom_components')
                    ->where('parent_item', $partNo)
                    ->where(function ($q) {
                        $q->where('item_type', 'RAW_MATERIAL')
                            ->orWhere('component_item', 'LIKE', '40%');
                    })
                    ->first();
                if ($rawComp) {
                    $materialType = $rawComp->component_item;
                }
            }
        }

        return [
            'part_no' => $partNo,
            'description' => $description,
            'cavity' => $cavity,
            'cycle_time' => $cycleTime,
            'target_per_hour' => $targetPerHour,
            'spk_no' => $spkNo,
            'available_spks' => $availableSpks,
            'material_type' => $materialType,
        ];
    }

    /**
     * Search all SPK Masters across the system
     */
    public function searchSpkMasters(string $query = '', int $limit = 25): array
    {
        $db = SpkMaster::with('masterItem')->select('id', 'spk_number', 'item_code', 'planned_quantity', 'completed_quantity', 'post_date', 'production_status');

        $query = trim($query);
        if (! empty($query)) {
            $db->where(function ($q) use ($query) {
                $q->where('spk_number', 'LIKE', "%{$query}%")
                    ->orWhere('item_code', 'LIKE', "%{$query}%");
            });
        }

        return $db->orderBy('post_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($s) {
                $rem = max(0, (float) $s->planned_quantity - (float) $s->completed_quantity);

                return [
                    'id' => $s->id,
                    'spk_number' => $s->spk_number,
                    'item_code' => $s->item_code,
                    'item_name' => $s->masterItem?->item_name ?? '',
                    'planned_quantity' => (float) $s->planned_quantity,
                    'completed_quantity' => (float) $s->completed_quantity,
                    'remaining_quantity' => $rem,
                    'post_date' => $s->post_date ? Carbon::parse($s->post_date)->format('Y-m-d') : '-',
                    'production_status' => $s->production_status ?: '-',
                ];
            })
            ->toArray();
    }

    /**
     * Generate DailyItemCode records for all eligible items in the List Up
     */
    public function generateDailyItemCodes(PpicListUp $listUp): array
    {
        $scheduleDate = Carbon::parse($listUp->date)->format('Y-m-d');
        $generatedCount = 0;
        $skippedCount = 0;
        $items = $listUp->items()->get();

        $activeDicIds = [];
        $machineUserIdsInvolved = [];

        // Preload machine users for fast lookup
        $machineUsers = User::whereHas('role', function ($q) {
            $q->where('name', 'OPERATOR');
        })->get();

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $activeShifts = [];
                if ($item->operator_shift_1 > 0) {
                    $activeShifts[] = 1;
                }
                if ($item->operator_shift_2 > 0) {
                    $activeShifts[] = 2;
                }
                if ($item->operator_shift_3 > 0) {
                    $activeShifts[] = 3;
                }

                $count = count($activeShifts);
                if ($count === 0 || $item->qty_to_run <= 0) {
                    $skippedCount++;
                    continue;
                }

                // Resolve machine user
                $targetName = strtoupper(trim($item->machine_name));
                $targetClean = preg_replace('/^0+/', '', $targetName);

                $machineUser = null;
                // If item has machine_id, check if it matches machine_name
                if ($item->machine_id) {
                    $candidate = $machineUsers->firstWhere('id', $item->machine_id);
                    if ($candidate) {
                        $candClean = preg_replace('/^0+/', '', strtoupper($candidate->name));
                        if ($candClean === $targetClean || strtoupper($candidate->name) === $targetName) {
                            $machineUser = $candidate;
                        }
                    }
                }

                // If not matched, search strictly by machine name
                if (! $machineUser && ! empty($targetName)) {
                    $machineUser = $machineUsers->first(function ($u) use ($targetName, $targetClean) {
                        $uName = strtoupper($u->name);
                        $uClean = preg_replace('/^0+/', '', $uName);
                        $uUser = strtoupper($u->username ?? '');
                        $uUserClean = preg_replace('/^0+/', '', $uUser);

                        return $uClean === $targetClean
                            || $uName === $targetName
                            || $uUserClean === $targetClean
                            || $uUser === $targetName;
                    });
                }

                if (! $machineUser) {
                    $machineUserId = $item->machine_id ?: 1;
                } else {
                    $machineUserId = $machineUser->id;
                    if ($item->machine_id !== $machineUser->id) {
                        $item->update(['machine_id' => $machineUser->id]);
                    }
                }

                $machineUserIdsInvolved[] = $machineUserId;

                // Split qty evenly with remainder distribution
                $baseQty = intdiv($item->qty_to_run, $count);
                $rem = $item->qty_to_run % $count;

                foreach ($activeShifts as $idx => $shiftNum) {
                    $shiftQty = $baseQty + ($idx < $rem ? 1 : 0);

                    // Standard shift timings
                    if ($shiftNum === 1) {
                        $startTime = '07:30';
                        $endTime = '15:30';
                        $startDate = $scheduleDate;
                        $endDate = $scheduleDate;
                    } elseif ($shiftNum === 2) {
                        $startTime = '15:30';
                        $endTime = '23:30';
                        $startDate = $scheduleDate;
                        $endDate = $scheduleDate;
                    } else { // Shift 3
                        $startTime = '23:30';
                        $endTime = '07:30';
                        $startDate = $scheduleDate;
                        $endDate = Carbon::parse($scheduleDate)->addDay()->format('Y-m-d');
                    }

                    $dic = DailyItemCode::updateOrCreate(
                        [
                            'schedule_date' => $scheduleDate,
                            'user_id' => $machineUserId,
                            'shift' => $shiftNum,
                            'item_code' => $item->part_no,
                        ],
                        [
                            'quantity' => $shiftQty,
                            'final_quantity' => $shiftQty,
                            'actual_quantity' => $shiftQty,
                            'loss_package_quantity' => 0,
                            'start_date' => $startDate,
                            'start_time' => $startTime,
                            'end_date' => $endDate,
                            'end_time' => $endTime,
                            'temporal_cycle_time' => $item->cycle_time,
                            'temporal_cavity' => $item->cavity,
                            'remark' => $item->reason,
                            'is_done' => null,
                        ]
                    );

                    // Sync MachineJob active job so it immediately shows up when operator logs in
                    $mJob = \App\Models\MachineJob::firstOrCreate(['user_id' => $machineUserId]);
                    if ($shiftNum === 1 || empty($mJob->dic_id) || empty($mJob->item_code)) {
                        $mJob->update([
                            'item_code' => $item->part_no,
                            'shift' => $shiftNum,
                            'dic_id' => $dic->id,
                        ]);
                    }

                    $activeDicIds[] = $dic->id;
                    $generatedCount++;
                }

                $item->update(['is_generated' => true]);
            }

            // Clean up obsolete DICs for machines in this List Up that are no longer scheduled
            $machineUserIdsInvolved = array_values(array_unique(array_filter($machineUserIdsInvolved)));
            if (! empty($machineUserIdsInvolved)) {
                $obsoleteDics = DailyItemCode::where('schedule_date', $scheduleDate)
                    ->whereIn('user_id', $machineUserIdsInvolved)
                    ->whereNotIn('id', $activeDicIds)
                    ->whereNull('is_done')
                    ->get();

                foreach ($obsoleteDics as $obs) {
                    // Safe guard: only delete if production has NOT started yet
                    $hasOutput = ProductionOutputLog::where('dic_id', $obs->id)->exists()
                        || DB::table('production_scanned_data')->where('dic_id', $obs->id)->exists()
                        || HourlyRemark::where('dic_id', $obs->id)->exists();

                    if (! $hasOutput) {
                        // If machine_jobs was pointing to this obsolete DIC, point it to the new active DIC
                        $mJob = \App\Models\MachineJob::where('user_id', $obs->user_id)->first();
                        if ($mJob && (int) $mJob->dic_id === (int) $obs->id) {
                            $substitute = DailyItemCode::where('schedule_date', $scheduleDate)
                                ->where('user_id', $obs->user_id)
                                ->whereIn('id', $activeDicIds)
                                ->orderBy('shift')
                                ->first();

                            $mJob->update([
                                'dic_id' => $substitute?->id,
                                'item_code' => $substitute?->item_code,
                                'shift' => $substitute?->shift ?? 1,
                            ]);
                        }

                        $obs->delete();
                    }
                }
            }

            $listUp->update(['status' => 'GENERATED']);
            DB::commit();

            return [
                'success' => true,
                'generated_count' => $generatedCount,
                'skipped_count' => $skippedCount,
                'message' => "Berhasil generate {$generatedCount} jadwal harian (Daily Item Codes) untuk tanggal {$scheduleDate}!",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'generated_count' => 0,
                'skipped_count' => 0,
                'message' => 'Gagal generate Daily Item Codes: ' . $e->getMessage(),
            ];
        }
    }
}
