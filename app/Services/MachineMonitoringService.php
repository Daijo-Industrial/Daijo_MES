<?php

namespace App\Services;

use App\Models\AdjustMachineLog;
use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\MasterListItem;
use App\Models\MouldChangeLog;
use App\Models\RepairMachineLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MachineMonitoringService
{
    public function getMachineStatuses(?string $zoneId = null, ?string $search = null): Collection
    {
        $query = User::with(['zone', 'jobs'])
            ->whereNotNull('zone_id');

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $machines = $query->get();

        return $machines->map(function ($machine) {
            $statusData = $this->calculateStatusAndTime($machine);
            $productionData = $this->getProductionDetails($machine, $statusData['status']);

            return array_merge([
                'id' => $machine->id,
                'zone' => $machine->zone->zone_name ?? '-',
                'machine_code' => $machine->username,
                'name' => $machine->name,
            ], $statusData, $productionData);
        });
    }

    private function calculateStatusAndTime(User $machine): array
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. SETUP — ada adjust/mould log yang belum selesai
        $setupLog = AdjustMachineLog::where('user_id', $machine->id)
            ->whereNull('end_time')
            ->whereBetween('created_at', [$yesterday->startOfDay(), $now])
            ->orderBy('created_at', 'desc')
            ->first()
            ?? MouldChangeLog::where('user_id', $machine->id)
                ->whereNull('end_time')
                ->whereBetween('created_at', [$yesterday->startOfDay(), $now])
                ->orderBy('created_at', 'desc')
                ->first();

        if ($setupLog) {
            return [
                'status' => 'SETUP',
                'total_time_not_running' => $now->diffInMinutes($setupLog->created_at),
                'start_running' => '-',
            ];
        }

        // 2. REPAIR
        $repairLog = RepairMachineLog::where('user_id', $machine->id)
            ->whereNull('finish_repair')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($repairLog) {
            return [
                'status' => 'IDLE',
                'total_time_not_running' => $now->diffInMinutes($repairLog->created_at),
                'start_running' => '-',
            ];
        }

        // 3. RUNNING — ada job aktif
        $job = $machine->jobs;
        if ($job && ! empty($job->item_code)) {
            // Cari DIC aktif — whereNull is_done ATAU is_done = 0
            $dic = DailyItemCode::where('user_id', $machine->id)
                ->where('item_code', $job->item_code)
                ->where(fn ($q) => $q->whereNull('is_done')->orWhere('is_done', 0))
                ->whereBetween('start_date', [$yesterday->toDateString(), $today->toDateString()])
                ->orderBy('start_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->first();

            return [
                'status' => 'RUNNING',
                'total_time_not_running' => 0,
                'start_running' => $dic
                    ? Carbon::parse($dic->start_date . ' ' . $dic->start_time)->format('d/m/Y H:i')
                    : '-',
            ];
        }

        // 4. IDLE default
        $lastActivity = $this->getLastActivityTime($machine->id);
        $timeNotRunning = $lastActivity ? $now->diffInMinutes($lastActivity) : 0;

        return [
            'status' => 'IDLE',
            'total_time_not_running' => $timeNotRunning,
            'start_running' => '-',
        ];
    }

    private function getProductionDetails(User $machine, string $status): array
    {
        $job = $machine->jobs;
        $partRunning = $job->item_code ?? '-';
        $targetQty = '-';
        $targetAchieve = '-';
        $nextPart = '-';
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Ambil semua DIC pending — is_done null ATAU 0
        $pendingDics = DailyItemCode::where('user_id', $machine->id)
            ->where(fn ($q) => $q->whereNull('is_done')->orWhere('is_done', 0))
            ->whereBetween('start_date', [$yesterday->toDateString(), $today->toDateString()])
            ->orderBy('start_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $currentDicId = null;
        $currentDic = null;

        if ($job && ! empty($job->item_code)) {
            $currentDic = $pendingDics->first(
                fn ($d) => $d->item_code === $job->item_code && $d->shift == $job->shift
            ) ?? $pendingDics->first(
                fn ($d) => $d->item_code === $job->item_code
            );

            if ($currentDic) {
                $currentDicId = $currentDic->id;

                $masterItem = MasterListItem::where('item_code', $job->item_code)->first();
                $multiplier = ($masterItem && ! empty($masterItem->pair)) ? 2 : 1;
                $targetQty = $currentDic->quantity * $multiplier;

                // Achieve dari hourly_remarks
                $targetAchieve = HourlyRemark::where('dic_id', $currentDic->id)
                    ->whereNotNull('actual_production')
                    ->sum('actual_production');
            } else {
                // Job ada tapi DIC tidak ketemu di pending
                // Cari DIC yang sudah done untuk ambil achieve terakhir
                $doneDic = DailyItemCode::where('user_id', $machine->id)
                    ->where('item_code', $job->item_code)
                    ->where('is_done', 1)
                    ->orderBy('updated_at', 'desc')
                    ->first();

                if ($doneDic) {
                    $masterItem = MasterListItem::where('item_code', $job->item_code)->first();
                    $multiplier = ($masterItem && ! empty($masterItem->pair)) ? 2 : 1;
                    $targetQty = $doneDic->quantity * $multiplier;

                    $targetAchieve = HourlyRemark::where('dic_id', $doneDic->id)
                        ->whereNotNull('actual_production')
                        ->sum('actual_production');
                }
            }
        }

        // Next part
        $nextDic = null;
        if ($currentDicId) {
            $nextDic = $pendingDics
                ->filter(fn ($d) => $d->id !== $currentDicId)
                ->sortBy([['start_date', 'asc'], ['start_time', 'asc']])
                ->first();
        } else {
            $nextDic = $pendingDics->first();
        }

        if ($nextDic) {
            $nextPart = $nextDic->item_code;
        }

        return [
            'part_running' => $partRunning,
            'target_qty' => $targetQty,
            'target_achieve' => $targetAchieve,
            'next_part_running' => $nextPart,
        ];
    }

    private function getLastActivityTime(int $userId): ?Carbon
    {
        $lastDic = DailyItemCode::where('user_id', $userId)
            ->where('is_done', 1)
            ->orderBy('updated_at', 'desc')
            ->first();

        $lastAdjust = AdjustMachineLog::where('user_id', $userId)
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->first();

        $lastMould = MouldChangeLog::where('user_id', $userId)
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->first();

        $lastRepair = RepairMachineLog::where('user_id', $userId)
            ->whereNotNull('finish_repair')
            ->orderBy('finish_repair', 'desc')
            ->first();

        $repairTime = null;
        if ($lastRepair && $lastRepair->finish_repair) {
            try {
                $repairTime = Carbon::parse($lastRepair->finish_repair);
            } catch (\Exception $e) {
                $repairTime = $lastRepair->created_at;
            }
        }

        return collect([
            $lastDic?->updated_at,
            $lastAdjust?->end_time ? Carbon::parse($lastAdjust->end_time) : null,
            $lastMould?->end_time ? Carbon::parse($lastMould->end_time) : null,
            $repairTime,
        ])->filter()->sortDesc()->first();
    }

    public function getMachineDetailedHistory(int $userId): array
    {
        $recentDics = DailyItemCode::where('user_id', $userId)
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($dic) {
                $masterItem = MasterListItem::where('item_code', $dic->item_code)->first();
                $multiplier = ($masterItem && ! empty($masterItem->pair)) ? 2 : 1;

                return [
                    'id' => $dic->id,
                    'item_code' => $dic->item_code,
                    'start_date' => $dic->start_date,
                    'shift' => $dic->shift,
                    'is_done' => $dic->is_done,
                    'quantity' => $dic->quantity * $multiplier,
                    'actual_quantity' => HourlyRemark::where('dic_id', $dic->id)
                        ->whereNotNull('actual_production')
                        ->sum('actual_production'),
                ];
            });

        $adjustLogs = AdjustMachineLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')->limit(3)->get()
            ->map(fn ($l) => array_merge($l->toArray(), [
                'log_type' => 'ADJUST',
                'created_at' => Carbon::parse($l->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'end_time' => $l->end_time
                    ? Carbon::parse($l->end_time)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s')
                    : null,
            ]));

        $mouldLogs = MouldChangeLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')->limit(3)->get()
            ->map(fn ($l) => array_merge($l->toArray(), [
                'log_type' => 'MOULD',
                'created_at' => Carbon::parse($l->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'end_time' => $l->end_time
                    ? Carbon::parse($l->end_time)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s')
                    : null,
            ]));

        $repairLogs = RepairMachineLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')->limit(3)->get()
            ->map(fn ($l) => array_merge($l->toArray(), [
                'log_type' => 'REPAIR',
                'created_at' => Carbon::parse($l->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'finish_repair' => $l->finish_repair
                    ? Carbon::parse($l->finish_repair)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s')
                    : null,
            ]));

        $recentLogs = collect()
            ->concat($adjustLogs)
            ->concat($mouldLogs)
            ->concat($repairLogs)
            ->sortByDesc('created_at')
            ->values()
            ->take(10);

        return [
            'recent_jobs' => $recentDics,
            'recent_logs' => $recentLogs,
        ];
    }
}
