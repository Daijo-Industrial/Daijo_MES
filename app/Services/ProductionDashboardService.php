<?php

namespace App\Services;

use App\Models\AdjustMachineLog;
use App\Models\DailyItemCode;
use App\Models\MasterListItem;
use App\Models\MouldChangeLog;
use App\Models\RepairMachineLog;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ProductionDashboardService
{
    /**
     * Fast hour extraction from time strings like "08:00", "08:00:00", "8", or "2026-08-15 08:00:00"
     */
    private static function parseHourSlot($rawTime): int
    {
        if ($rawTime === null || $rawTime === '') return 0;
        if (is_numeric($rawTime)) {
            $h = (int)$rawTime;
            return ($h >= 0 && $h < 24) ? $h : 0;
        }

        // If format contains space (e.g. '2026-08-15 08:00:00')
        $spacePos = strpos($rawTime, ' ');
        if ($spacePos !== false) {
            $rawTime = substr($rawTime, $spacePos + 1);
        }

        // If format is '08:00' or '08:00:00'
        $colonPos = strpos($rawTime, ':');
        if ($colonPos !== false) {
            return (int)substr($rawTime, 0, $colonPos);
        }

        return (int)$rawTime;
    }

    /**
     * Parse raw UTC timestamp into Asia/Jakarta (WIB) Carbon instance
     */
    public static function getLocalCarbon($rawTime): Carbon
    {
        if ($rawTime instanceof Carbon) {
            return $rawTime->copy()->setTimezone('Asia/Jakarta');
        }
        return Carbon::parse($rawTime, 'UTC')->setTimezone('Asia/Jakarta');
    }

    /**
     * Check if a specific date operates under half-day schedule.
     * Checks dates marked half_day = 1 in HolidaySchedule.
     */
    public static function isDateHalfDay(Carbon|string $date, ?array $preloadedHalfDayDates = null): bool
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateStr = $carbonDate->format('Y-m-d');

        if ($preloadedHalfDayDates !== null) {
            return isset($preloadedHalfDayDates[$dateStr]);
        }

        try {
            return \App\Models\Setting\HolidaySchedule::whereDate('date', $dateStr)
                ->where('half_day', 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Determination of shift (1, 2, 3) and production date from local (WIB) timestamp
     * Normal schedule:
     * Shift 1: 07:30 - 15:30
     * Shift 2: 15:30 - 23:30
     * Shift 3: 23:30 - 07:30 (next day)
     *
     * Half-day schedule (when enabled, or half_day dates):
     * Shift 1: 07:30 - 12:30
     * Shift 2: 12:30 - 17:30
     * Shift 3: 17:30 - 22:30 (rollover until 07:30 next day)
     */
    public static function getProductionDateAndShift(
        Carbon $localTime,
        bool|array|null $isHalfDay = null,
        ?array $preloadedHalfDayDates = null
    ): array {
        $timePart = $localTime->format('H:i:s');
        $targetDate = ($timePart < '07:30:00') ? $localTime->copy()->subDay() : $localTime->copy();
        $prodDate = $targetDate->format('Y-m-d');

        $halfDayActive = false;
        if ($isHalfDay === true) {
            $halfDayActive = true;
        } elseif ($isHalfDay === false) {
            $halfDayActive = false;
        } elseif (is_array($isHalfDay)) {
            $halfDayActive = ($targetDate->isSaturday() && !empty($isHalfDay['saturday']))
                || ($targetDate->isSunday() && !empty($isHalfDay['sunday']))
                || (!empty($isHalfDay[$prodDate]))
                || self::isDateHalfDay($targetDate, $preloadedHalfDayDates);
        } else {
            $halfDayActive = self::isDateHalfDay($targetDate, $preloadedHalfDayDates);
        }

        if ($halfDayActive) {
            if ($timePart >= '07:30:00' && $timePart < '12:30:00') {
                return ['date' => $prodDate, 'shift' => 1];
            } elseif ($timePart >= '12:30:00' && $timePart < '17:30:00') {
                return ['date' => $prodDate, 'shift' => 2];
            } else {
                return ['date' => $prodDate, 'shift' => 3];
            }
        }

        if ($timePart >= '07:30:00' && $timePart < '15:30:00') {
            return ['date' => $prodDate, 'shift' => 1];
        } elseif ($timePart >= '15:30:00' && $timePart < '23:30:00') {
            return ['date' => $prodDate, 'shift' => 2];
        } else {
            return ['date' => $prodDate, 'shift' => 3];
        }
    }

    /**
     * Backward-compatible determination of shift (1, 2, 3)
     */
    public static function getShiftFromTimeStr(string $timeStr): int
    {
        $localTime = self::getLocalCarbon($timeStr);
        return self::getProductionDateAndShift($localTime)['shift'];
    }

    /**
     * Get all dashboard data in a single unified, ultra-fast pass.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $itemCode
     * @param string|null $machineUserId
     * @param string|null $plant
     * @return array
     */
    public function getAllDashboardData(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null,
        bool|array|null $isHalfDay = null
    ): array {
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        // Preload holiday schedule half-day dates in window for ultra-fast in-memory check
        $preloadedHalfDayDates = [];
        try {
            $preloadedHalfDayDates = \App\Models\Setting\HolidaySchedule::whereBetween('date', [$startDateStr, $endDateStr])
                ->where('half_day', 1)
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                ->flip()
                ->toArray();
        } catch (\Throwable $e) {
            $preloadedHalfDayDates = [];
        }

        // Pre-fetch machine IDs for the selected plant for instant indexed queries (no slow whereHas subqueries)
        $plantMachineIds = null;
        if ($plant === 'karawang') {
            $plantMachineIds = User::where(function($q) {
                $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%');
            })->pluck('id')->toArray();
        } elseif ($plant === 'kbn') {
            $plantMachineIds = User::where(function($q) {
                $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%');
            })->pluck('id')->toArray();
        }

        // 1. Single database query for DailyItemCodes
        $dicQuery = DailyItemCode::query()
            ->with([
                'hourlyRemarks.ngDetails.ngType',
                'user:id,name'
            ])
            ->whereBetween('start_date', [$startDateStr, $endDateStr]);

        if ($itemCode) {
            $dicQuery->where('item_code', $itemCode);
        }

        if ($machineUserId) {
            $dicQuery->where('user_id', $machineUserId);
        } elseif ($plantMachineIds !== null) {
            $dicQuery->whereIn('user_id', $plantMachineIds);
        }

        $dailyData = $dicQuery->get();

        // 2. Fetch Master Items mapped by item_code
        $neededItemCodes = $dailyData->pluck('item_code')->filter()->unique()->values()->toArray();
        $masterItems = !empty($neededItemCodes)
            ? MasterListItem::whereIn('item_code', $neededItemCodes)->get()->keyBy('item_code')
            : collect();

        // 3. Fetch Adjust Logs & Mould Change Logs in precise local timestamp window
        // Note: logs are created in UTC or local server time, query by window
        $windowStartUtc = Carbon::parse($startDateStr . ' 07:30:00', 'Asia/Jakarta')->setTimezone('UTC');
        $windowEndUtc = Carbon::parse($endDateStr . ' 07:30:00', 'Asia/Jakarta')->addDay()->setTimezone('UTC');

        // Strict plant filtering for Adjust and Mould Change logs
        $effectivePlant = $plant;
        if (!$effectivePlant && $machineUserId) {
            $targetUser = User::find($machineUserId);
            if ($targetUser) {
                $effectivePlant = str_starts_with(strtoupper($targetUser->name), 'K') ? 'karawang' : 'kbn';
            }
        }

        $adjustQuery = AdjustMachineLog::query()
            ->with(['user:id,name'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        $mouldQuery = MouldChangeLog::query()
            ->with(['user:id,name'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        $repairQuery = RepairMachineLog::query()
            ->with(['user:id,name'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        if ($itemCode) {
            $adjustQuery->where('item_code', $itemCode);
            $mouldQuery->where('item_code', $itemCode);
            $repairQuery->where('item_code', $itemCode);
        }

        if ($machineUserId) {
            $adjustQuery->where('user_id', $machineUserId);
            $mouldQuery->where('user_id', $machineUserId);
            $repairQuery->where('user_id', $machineUserId);
        } elseif ($effectivePlant === 'karawang') {
            $krwMachineIds = ($plant === 'karawang' && $plantMachineIds !== null) ? $plantMachineIds : User::where(function ($q) {
                $q->where('name', 'LIKE', 'K%')->orWhereHas('branch', fn($b) => $b->where('code', 'KRW'));
            })->pluck('id')->toArray();
            $adjustQuery->whereIn('user_id', $krwMachineIds);
            $mouldQuery->whereIn('user_id', $krwMachineIds);
            $repairQuery->whereIn('user_id', $krwMachineIds);
        } elseif ($effectivePlant === 'kbn') {
            $kbnMachineIds = ($plant === 'kbn' && $plantMachineIds !== null) ? $plantMachineIds : User::where(function ($q) {
                $q->where('name', 'NOT LIKE', 'K%')->whereDoesntHave('branch', fn($b) => $b->where('code', 'KRW'));
            })->pluck('id')->toArray();
            $adjustQuery->whereIn('user_id', $kbnMachineIds);
            $mouldQuery->whereIn('user_id', $kbnMachineIds);
            $repairQuery->whereIn('user_id', $kbnMachineIds);
        }
        $adjustLogsRaw = $adjustQuery->get();
        $mouldLogsRaw = $mouldQuery->get();
        $repairLogsRaw = $repairQuery->get();

        // 4. In-Memory Process All Sections Fast
        $productionResult = $this->processProductionData($dailyData, $startDate, $endDate);
        $ngBreakdown = $this->processNgBreakdown($dailyData, $masterItems);
        $downtimeAnalysis = $this->processDowntimeAnalysis($dailyData, $masterItems);
        $topRemarks = $this->processTopProblematicRemarks($dailyData, $masterItems);
        $machineWorkingHours = $this->processMachineWorkingHours($dailyData);
        $shiftPersonnelAnalysis = $this->processShiftPersonnelAndNgAnalysis(
            $dailyData,
            $adjustLogsRaw,
            $mouldLogsRaw,
            $masterItems,
            $startDate,
            $endDate,
            $isHalfDay,
            $preloadedHalfDayDates,
            $repairLogsRaw
        );
        $adjusterNgTrend = $this->processAdjusterNgTrend(
            $dailyData,
            $adjustLogsRaw,
            $startDate,
            $endDate,
            $isHalfDay,
            $preloadedHalfDayDates
        );

        return [
            'chart_data'               => $productionResult['chart_data'] ?? [],
            'summary'                  => $productionResult['summary'] ?? [],
            'purging_details'          => $productionResult['purging_details'] ?? [],
            'ng_breakdown'             => $ngBreakdown,
            'downtime_analysis'        => $downtimeAnalysis,
            'top_remarks'              => $topRemarks,
            'machine_working_hours'    => $machineWorkingHours,
            'shift_personnel_analysis' => $shiftPersonnelAnalysis,
            'adjuster_ng_trend'        => $adjusterNgTrend,
        ];
    }

    /**
     * Get production data for dashboard (backward-compatible wrapper)
     */
    public function getProductionData(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null
    ): array {
        $query = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails.ngType', 'user:id,name'])
            ->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($itemCode) $query->where('item_code', $itemCode);
        if ($machineUserId) $query->where('user_id', $machineUserId);
        if ($plant === 'karawang') $query->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        elseif ($plant === 'kbn') $query->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));

        return $this->processProductionData($query->get(), $startDate, $endDate);
    }

    /**
     * Process raw collection into chart-ready format (Fast in-memory)
     */
    public function processProductionData($dailyData, Carbon $startDate, Carbon $endDate): array
    {
        $chartData = [];
        $summary = [
            'total_target'     => 0,
            'total_actual'     => 0,
            'total_ng'         => 0,
            'ng_rate'          => 0,
            'achievement_rate' => 0,
        ];

        if ($startDate->isSameDay($endDate)) {
            // Daily View: Breakdown by 24 hours (00:00 to 23:00)
            $dateStr = $startDate->format('Y-m-d');
            $hourlyBuckets = array_fill(0, 24, ['target' => 0, 'actual' => 0, 'ng' => 0, 'machines' => []]);

            foreach ($dailyData as $daily) {
                $machineId = $daily->user_id;

                foreach ($daily->hourlyRemarks as $hourly) {
                    $hourSlot = self::parseHourSlot($hourly->start_time);
                    if ($hourSlot < 0 || $hourSlot > 23) $hourSlot = 0;

                    $target = (int)($hourly->target ?? 0);
                    $actual = (int)($hourly->actual_production ?? 0);
                    $ng = 0;

                    if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                        foreach ($hourly->ngDetails as $ngDetail) {
                            $ng += (int)($ngDetail->ng_quantity ?? 0);
                        }
                    }

                    $hourlyBuckets[$hourSlot]['target'] += $target;
                    $hourlyBuckets[$hourSlot]['actual'] += $actual;
                    $hourlyBuckets[$hourSlot]['ng'] += $ng;

                    if ($machineId && !in_array($machineId, $hourlyBuckets[$hourSlot]['machines'])) {
                        $hourlyBuckets[$hourSlot]['machines'][] = $machineId;
                    }
                }
            }

            for ($hour = 0; $hour < 24; $hour++) {
                $b = $hourlyBuckets[$hour];
                $target = $b['target'];
                $actual = $b['actual'];
                $ng = $b['ng'];
                $totalProd = $actual + $ng;

                $chartData[] = [
                    'date'          => sprintf('%02d:00', $hour),
                    'full_date'     => sprintf('%s %02d:00', $dateStr, $hour),
                    'target'        => $target,
                    'actual'        => $actual,
                    'ng'            => $ng,
                    'ng_rate'       => $totalProd > 0 ? round(($ng / $totalProd) * 100, 2) : 0,
                    'achievement'   => $target > 0 ? round(($actual / $target) * 100, 2) : 0,
                    'working_hours' => count($b['machines']),
                ];

                $summary['total_target'] += $target;
                $summary['total_actual'] += $actual;
                $summary['total_ng']     += $ng;
            }
        } else {
            // Monthly / Weekly View: Pre-group by start_date for O(1) lookups
            $dicsByDate = [];
            foreach ($dailyData as $daily) {
                $dicsByDate[$daily->start_date][] = $daily;
            }

            $period = CarbonPeriod::create($startDate, $endDate);

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $dayData = $dicsByDate[$dateStr] ?? [];

                $dayUniqueSlots = [];
                $target = 0;
                $actual = 0;
                $ng = 0;

                foreach ($dayData as $daily) {
                    $machineId = $daily->user_id;
                    if (!$machineId) continue;

                    foreach ($daily->hourlyRemarks as $hourly) {
                        $hourSlot = self::parseHourSlot($hourly->start_time);
                        $slotKey = $machineId . '_' . $hourSlot;
                        if (!isset($dayUniqueSlots[$slotKey])) {
                            $dayUniqueSlots[$slotKey] = true;
                        }

                        $target += (int)($hourly->target ?? 0);
                        $actual += (int)($hourly->actual_production ?? 0);

                        if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                            foreach ($hourly->ngDetails as $ngDetail) {
                                $ng += (int)($ngDetail->ng_quantity ?? 0);
                            }
                        }
                    }
                }

                $totalProd = $actual + $ng;

                $chartData[] = [
                    'date'          => $date->format('d M'),
                    'full_date'     => $dateStr,
                    'target'        => $target,
                    'actual'        => $actual,
                    'ng'            => $ng,
                    'ng_rate'       => $totalProd > 0 ? round(($ng / $totalProd) * 100, 2) : 0,
                    'achievement'   => $target > 0 ? round(($actual / $target) * 100, 2) : 0,
                    'working_hours' => count($dayUniqueSlots),
                ];

                $summary['total_target'] += $target;
                $summary['total_actual'] += $actual;
                $summary['total_ng']     += $ng;
            }
        }

        $totalProduction = $summary['total_actual'] + $summary['total_ng'];
        $summary['ng_rate'] = $totalProduction > 0
            ? round(($summary['total_ng'] / $totalProduction) * 100, 2)
            : 0;

        $summary['achievement_rate'] = $summary['total_target'] > 0
            ? round(($summary['total_actual'] / $summary['total_target']) * 100, 2)
            : 0;

        $summary['total_purging'] = (float)$dailyData->sum('resin_usage');

        $purgingDetails = $dailyData->filter(function($dic) {
            return $dic->resin_usage !== null && $dic->resin_usage > 0;
        })->map(function($dic) {
            return [
                'dic_id'       => $dic->id,
                'date'         => $dic->start_date ?? $dic->schedule_date,
                'shift'        => $dic->shift,
                'machine_name' => $dic->user->name ?? 'Unknown',
                'item_code'    => $dic->item_code,
                'resin_usage'  => (float)$dic->resin_usage,
            ];
        })->values()->toArray();

        return [
            'chart_data'      => $chartData,
            'summary'         => $summary,
            'purging_details' => $purgingDetails,
        ];
    }

    /**
     * Process NG breakdown by defect type with model breakdown and detailed remarks
     */
    public function processNgBreakdown($dailyData, $masterItems = null): array
    {
        $ngBreakdown = [];

        if ($masterItems === null) {
            $neededCodes = collect($dailyData)->pluck('item_code')->filter()->unique()->values()->toArray();
            $masterItems = !empty($neededCodes)
                ? MasterListItem::whereIn('item_code', $neededCodes)->get()->keyBy('item_code')
                : collect();
        }

        foreach ($dailyData as $daily) {
            $itemCode = $daily->item_code ?? 'UNKNOWN';
            $master = $masterItems instanceof \Illuminate\Support\Collection
                ? $masterItems->get($itemCode)
                : ($masterItems[$itemCode] ?? null);
            $itemName = $master?->item_name ?: $itemCode;
            $machineName = $daily->user?->name ?: '-';
            $dateStr = $daily->start_date ? Carbon::parse($daily->start_date)->format('d M Y') : '-';
            $shift = $daily->shift ?? '-';

            foreach ($daily->hourlyRemarks as $hourly) {
                if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                    $hourLabel = ($hourly->start_time && $hourly->end_time)
                        ? substr($hourly->start_time, 0, 5) . ' - ' . substr($hourly->end_time, 0, 5)
                        : '-';
                    $hourlyRemarkText = trim((string)($hourly->remark ?? ''));

                    foreach ($hourly->ngDetails as $ngDetail) {
                        $qty = (int)($ngDetail->ng_quantity ?? 0);
                        if ($qty <= 0) continue;

                        $ngTypeName = trim((string)($ngDetail->ngType->ng_type ?? 'Unknown'));
                        if ($ngTypeName === '') $ngTypeName = 'Unknown';

                        $ngRemarkText = trim((string)($ngDetail->ng_remarks ?? ''));

                        // Tentukan format remark tampilan
                        if ($ngRemarkText !== '' && $hourlyRemarkText !== '' && $ngRemarkText !== $hourlyRemarkText) {
                            $displayRemark = $ngRemarkText . ' (' . $hourlyRemarkText . ')';
                        } elseif ($ngRemarkText !== '') {
                            $displayRemark = $ngRemarkText;
                        } elseif ($hourlyRemarkText !== '') {
                            $displayRemark = $hourlyRemarkText;
                        } else {
                            $displayRemark = '-';
                        }

                        if (!isset($ngBreakdown[$ngTypeName])) {
                            $ngBreakdown[$ngTypeName] = [
                                'name'   => $ngTypeName,
                                'total'  => 0,
                                'models' => [],
                            ];
                        }

                        $ngBreakdown[$ngTypeName]['total'] += $qty;

                        if (!isset($ngBreakdown[$ngTypeName]['models'][$itemCode])) {
                            $ngBreakdown[$ngTypeName]['models'][$itemCode] = [
                                'item_code' => $itemCode,
                                'item_name' => $itemName,
                                'total'     => 0,
                                'records'   => [],
                            ];
                        }

                        $ngBreakdown[$ngTypeName]['models'][$itemCode]['total'] += $qty;
                        $ngBreakdown[$ngTypeName]['models'][$itemCode]['records'][] = [
                            'date'          => $dateStr,
                            'shift'         => $shift,
                            'hour'          => $hourLabel,
                            'machine'       => $machineName,
                            'quantity'      => $qty,
                            'remark'        => $displayRemark,
                            'ng_remark'     => $ngRemarkText ?: null,
                            'hourly_remark' => $hourlyRemarkText ?: null,
                        ];
                    }
                }
            }
        }

        // Urutkan models di dalam setiap defect type berdasarkan total desc
        foreach ($ngBreakdown as &$typeData) {
            $models = array_values($typeData['models']);
            usort($models, fn($a, $b) => $b['total'] <=> $a['total']);
            $typeData['models'] = $models;
            $typeData['models_count'] = count($models);
        }
        unset($typeData);

        usort($ngBreakdown, fn($a, $b) => $b['total'] <=> $a['total']);
        return array_values($ngBreakdown);
    }

    /**
     * Backward-compatible getNgBreakdown
     */
    public function getNgBreakdown(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null
    ): array {
        $query = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails.ngType', 'user:id,name'])
            ->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($itemCode) $query->where('item_code', $itemCode);
        if ($machineUserId) $query->where('user_id', $machineUserId);
        if ($plant === 'karawang') $query->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        elseif ($plant === 'kbn') $query->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));

        return $this->processNgBreakdown($query->get());
    }

    /**
     * Process Downtime Analysis
     */
    public function processDowntimeAnalysis($dailyData, $masterItems): array
    {
        $totalDowntime  = 0;
        $downtimeByHour = [];
        $problemHours   = [];
        $mergedHours    = [];

        foreach ($dailyData as $daily) {
            $hourlyByHour = [];

            foreach ($daily->hourlyRemarks as $hourly) {
                $hour = self::parseHourSlot($hourly->start_time);
                $hourly->_hour_num = $hour;
                $hourly->_hour_label = sprintf('%02d:00', $hour);

                if (isset($hourlyByHour[$hour])) {
                    $existingActual = $hourlyByHour[$hour]->actual_production ?? 0;
                    $newActual      = $hourly->actual_production ?? 0;
                    if ($newActual > $existingActual) {
                        $hourlyByHour[$hour] = $hourly;
                    }
                } else {
                    $hourlyByHour[$hour] = $hourly;
                }
            }

            foreach ($hourlyByHour as $hour => $hourly) {
                $target = (int)($hourly->target ?? 0);
                $actual = (int)($hourly->actual_production ?? 0);

                if ($target <= 0 || $actual >= $target) continue;

                $cycleTimeSec = ($daily->temporal_cycle_time && $daily->temporal_cycle_time > 0)
                    ? $daily->temporal_cycle_time
                    : ($masterItems instanceof \Illuminate\Support\Collection ? $masterItems->get($daily->item_code)?->cycle_time : ($masterItems[$daily->item_code] ?? null));

                if (!$cycleTimeSec || $cycleTimeSec <= 0) continue;

                $cycleTimeMinutes = $cycleTimeSec / 60;
                $actualMinutes    = $actual * $cycleTimeMinutes;

                $key = ($daily->user_id ?? 'unknown') . '_' . $daily->start_date . '_' . $hour;

                if (!isset($mergedHours[$key])) {
                    $startLabel = sprintf('%02d:00', $hour);
                    $endLabel   = sprintf('%02d:00', ($hour + 1) % 24);

                    $mergedHours[$key] = [
                        'machine'        => $daily->user_id ?? 'unknown',
                        'date'           => $daily->start_date,
                        'hour'           => $hour,
                        'hour_label'     => $startLabel . ' - ' . $endLabel,
                        'total_prod_min' => 0,
                        'remarks'        => [],
                        'items'          => [],
                    ];
                }

                $mergedHours[$key]['total_prod_min'] += $actualMinutes;
                if (!empty($hourly->remark)) {
                    $mergedHours[$key]['remarks'][] = $hourly->remark;
                }
                $mergedHours[$key]['items'][] = [
                    'id'           => $hourly->id,
                    'item_code'    => $daily->item_code,
                    'target'       => $target,
                    'actual'       => $actual,
                    'cycle_time'   => $cycleTimeSec,
                    'prod_minutes' => round($actualMinutes, 2),
                ];
            }
        }

        foreach ($mergedHours as $merged) {
            $hour      = $merged['hour'];
            $hourLabel = $merged['hour_label'];

            $finalDowntime = max(0, 60 - $merged['total_prod_min']);
            if ($finalDowntime <= 0) continue;

            $totalDowntime += $finalDowntime;

            if (!isset($downtimeByHour[$hour])) {
                $downtimeByHour[$hour] = [
                    'hour'           => $hourLabel,
                    'total_downtime' => 0,
                    'occurrences'    => 0,
                ];
            }

            $downtimeByHour[$hour]['total_downtime'] += $finalDowntime;
            $downtimeByHour[$hour]['occurrences']++;

            $problemHours[] = [
                'id'             => $merged['items'][0]['id'] ?? null,
                'date'           => $merged['date'],
                'hour'           => $hourLabel,
                'total_prod_min' => round($merged['total_prod_min'], 2),
                'downtime'       => round($finalDowntime, 2),
                'remark'         => implode(' | ', array_filter($merged['remarks'])),
                'items'          => $merged['items'],
            ];
        }

        uasort($downtimeByHour, fn($a, $b) => $b['occurrences'] <=> $a['occurrences']);

        return [
            'total_downtime_minutes' => round($totalDowntime, 2),
            'total_downtime_hours'   => round($totalDowntime / 60, 2),
            'downtime_by_hour'       => array_values($downtimeByHour),
            'problem_hours_count'    => count($problemHours),
        ];
    }

    /**
     * Backward-compatible getDowntimeAnalysis
     */
    public function getDowntimeAnalysis(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null
    ): array {
        $query = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails'])
            ->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($itemCode) $query->where('item_code', $itemCode);
        if ($machineUserId) $query->where('user_id', $machineUserId);
        if ($plant === 'karawang') $query->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        elseif ($plant === 'kbn') $query->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));

        $dailyData = $query->get();
        $masterItems = MasterListItem::whereIn('item_code', $dailyData->pluck('item_code')->unique())
            ->pluck('cycle_time', 'item_code');

        return $this->processDowntimeAnalysis($dailyData, $masterItems);
    }

    /**
     * Process Top Problematic Remarks
     */
    public function processTopProblematicRemarks($dailyData, $masterItems): array
    {
        $mergedHours = [];

        foreach ($dailyData as $daily) {
            $hourlyByHour = [];

            foreach ($daily->hourlyRemarks as $hourly) {
                $hour = self::parseHourSlot($hourly->start_time);

                if (isset($hourlyByHour[$hour])) {
                    $existingActual = $hourlyByHour[$hour]->actual_production ?? 0;
                    $newActual      = $hourly->actual_production ?? 0;
                    if ($newActual > $existingActual) {
                        $hourlyByHour[$hour] = $hourly;
                    }
                } else {
                    $hourlyByHour[$hour] = $hourly;
                }
            }

            foreach ($hourlyByHour as $hour => $hourly) {
                $target = (int)($hourly->target ?? 0);
                $actual = (int)($hourly->actual_production ?? 0);

                if ($target <= 0 || $actual >= $target) continue;
                if (empty($hourly->remark)) continue;

                $cycleTimeSec = ($daily->temporal_cycle_time && $daily->temporal_cycle_time > 0)
                    ? $daily->temporal_cycle_time
                    : ($masterItems instanceof \Illuminate\Support\Collection ? $masterItems->get($daily->item_code)?->cycle_time : ($masterItems[$daily->item_code] ?? null));

                if (!$cycleTimeSec || $cycleTimeSec <= 0) continue;

                $cycleTimeMinutes = $cycleTimeSec / 60;
                $actualMinutes    = $actual * $cycleTimeMinutes;

                $key = ($daily->user_id ?? 'unknown') . '_' . $daily->start_date . '_' . $hour;

                if (!isset($mergedHours[$key])) {
                    $startLabel = sprintf('%02d:00', $hour);
                    $endLabel   = sprintf('%02d:00', ($hour + 1) % 24);

                    $mergedHours[$key] = [
                        'date'           => $daily->start_date,
                        'hour'           => $hour,
                        'hour_label'     => $startLabel . ' - ' . $endLabel,
                        'machine'        => $daily->user->name ?? 'Unknown',
                        'total_prod_min' => 0,
                        'remarks'        => [],
                        'items'          => [],
                    ];
                }

                $mergedHours[$key]['total_prod_min'] += $actualMinutes;
                $mergedHours[$key]['remarks'][]       = $hourly->remark;
                $mergedHours[$key]['items'][]         = [
                    'item_code'  => $daily->item_code,
                    'target'     => $target,
                    'actual'     => $actual,
                    'cycle_time' => $cycleTimeSec,
                ];
            }
        }

        $problemRemarks = [];

        foreach ($mergedHours as $merged) {
            $finalDowntime = max(0, 60 - $merged['total_prod_min']);
            if ($finalDowntime <= 0) continue;

            $totalTarget = collect($merged['items'])->sum('target');
            $totalActual = collect($merged['items'])->sum('actual');
            $gap         = $totalTarget - $totalActual;

            $problemRemarks[] = [
                'date'             => $merged['date'],
                'hour'             => $merged['hour_label'],
                'machine'          => $merged['machine'],
                'item_code'        => collect($merged['items'])->pluck('item_code')->unique()->implode(', '),
                'target'           => $totalTarget,
                'actual'           => $totalActual,
                'gap'              => $gap,
                'downtime_minutes' => round($finalDowntime, 2),
                'remark'           => implode(' | ', array_filter($merged['remarks'])),
                'severity'         => $this->calculateSeverity($gap, $totalTarget),
            ];
        }

        usort($problemRemarks, fn($a, $b) => $b['gap'] - $a['gap']);
        return array_slice($problemRemarks, 0, 20);
    }

    /**
     * Backward-compatible getTopProblematicRemarks
     */
    public function getTopProblematicRemarks(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null
    ): array {
        $query = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails', 'user'])
            ->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($itemCode) $query->where('item_code', $itemCode);
        if ($machineUserId) $query->where('user_id', $machineUserId);
        if ($plant === 'karawang') $query->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        elseif ($plant === 'kbn') $query->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));

        $dailyData = $query->get();
        $masterItems = MasterListItem::whereIn('item_code', $dailyData->pluck('item_code')->unique())
            ->pluck('cycle_time', 'item_code');

        return $this->processTopProblematicRemarks($dailyData, $masterItems);
    }

    private function calculateSeverity(int $gap, int $target): string
    {
        if ($target == 0) return 'low';
        $percentage = ($gap / $target) * 100;
        if ($percentage >= 50) return 'critical';
        if ($percentage >= 30) return 'high';
        if ($percentage >= 15) return 'medium';
        return 'low';
    }

    /**
     * Process Machine Working Hours
     */
    public function processMachineWorkingHours($dailyData): array
    {
        $machineHours = [];

        foreach ($dailyData as $daily) {
            $machineId = $daily->user_id;
            $machineName = $daily->user->name ?? 'Unknown';

            if (!$machineId) continue;

            if (!isset($machineHours[$machineId])) {
                $machineHours[$machineId] = [
                    'id'           => $machineId,
                    'name'         => $machineName,
                    'hours'        => 0,
                    'unique_slots' => [],
                ];
            }

            foreach ($daily->hourlyRemarks as $hourly) {
                $hourSlot = self::parseHourSlot($hourly->start_time);
                $slotKey = $daily->start_date . '_' . $hourSlot;

                if (!isset($machineHours[$machineId]['unique_slots'][$slotKey])) {
                    $machineHours[$machineId]['unique_slots'][$slotKey] = true;
                    $machineHours[$machineId]['hours'] += 1;
                }
            }
        }

        uasort($machineHours, fn($a, $b) => $b['hours'] <=> $a['hours']);

        return array_map(function($item) {
            unset($item['unique_slots']);
            return $item;
        }, array_values($machineHours));
    }

    /**
     * Backward-compatible getMachineWorkingHours
     */
    public function getMachineWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null
    ): array {
        $query = DailyItemCode::query()
            ->with(['hourlyRemarks', 'user:id,name'])
            ->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if ($itemCode) $query->where('item_code', $itemCode);
        if ($machineUserId) $query->where('user_id', $machineUserId);
        if ($plant === 'karawang') $query->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        elseif ($plant === 'kbn') $query->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));

        return $this->processMachineWorkingHours($query->get());
    }

    /**
     * Process Adjuster, Change Mould, and Shift NG Performance Analysis
     */
    public function processShiftPersonnelAndNgAnalysis(
        $dailyData,
        $adjustLogsRaw,
        $mouldLogsRaw,
        $masterItems,
        Carbon $startDate,
        Carbon $endDate,
        bool|array|null $isHalfDay = null,
        ?array $preloadedHalfDayDates = null,
        $repairLogsRaw = []
    ): array {
        $isSingleDay = $startDate->isSameDay($endDate);
        $isHalfDaySingleDay = false;
        if ($isSingleDay) {
            if ($isHalfDay === true) {
                $isHalfDaySingleDay = true;
            } elseif (is_array($isHalfDay)) {
                $isHalfDaySingleDay = ($startDate->isSaturday() && !empty($isHalfDay['saturday']))
                    || ($startDate->isSunday() && !empty($isHalfDay['sunday']))
                    || (!empty($isHalfDay[$startDate->format('Y-m-d')]))
                    || self::isDateHalfDay($startDate, $preloadedHalfDayDates);
            } else {
                $isHalfDaySingleDay = self::isDateHalfDay($startDate, $preloadedHalfDayDates);
            }
        }

        $shiftDefs = [
            1 => [
                'name'  => 'Shift 1 (Pagi)',
                'time'  => !$isSingleDay
                    ? '07:30 - 15:30 (Setengah Hari: 07:30 - 12:30)'
                    : ($isHalfDaySingleDay ? '07:30 - 12:30' : '07:30 - 15:30'),
                'theme' => 'amber',
            ],
            2 => [
                'name'  => 'Shift 2 (Sore)',
                'time'  => !$isSingleDay
                    ? '15:30 - 23:30 (Setengah Hari: 12:30 - 17:30)'
                    : ($isHalfDaySingleDay ? '12:30 - 17:30' : '15:30 - 23:30'),
                'theme' => 'emerald',
            ],
            3 => [
                'name'  => 'Shift 3 (Malam)',
                'time'  => !$isSingleDay
                    ? '23:30 - 07:30 (Setengah Hari: 17:30 - 22:30)'
                    : ($isHalfDaySingleDay ? '17:30 - 22:30' : '23:30 - 07:30'),
                'theme' => 'indigo',
            ],
        ];

        // Format and categorize Adjust Logs fast
        $allActivityLogs = [];
        $processedAdjustLogs = [];
        foreach ($adjustLogsRaw as $log) {
            $localCreated = self::getLocalCarbon($log->created_at);
            $shiftInfo = self::getProductionDateAndShift($localCreated, $isHalfDay, $preloadedHalfDayDates);
            $shiftNum = $shiftInfo['shift'];

            $durationMin = 0;
            if ($log->end_time) {
                $startSec = strtotime((string)$log->created_at);
                $endSec = strtotime((string)$log->end_time);
                $durationMin = max(0, round(($endSec - $startSec) / 60, 1));
            }

            $setupTimeSec = ($masterItems instanceof \Illuminate\Support\Collection)
                ? $masterItems->get($log->item_code)?->setup_time_minute
                : ($masterItems[$log->item_code] ?? null);
            $targetSetupMin = $setupTimeSec ?? 30;

            $localEnd = $log->end_time ? self::getLocalCarbon($log->end_time) : null;

            $entry = [
                'id'               => $log->id,
                'type'             => 'adjust',
                'type_label'       => 'Adjust Machine',
                'shift'            => $shiftNum,
                'machine_name'     => $log->user->name ?? 'Unknown',
                'item_code'        => $log->item_code ?? '-',
                'pic'              => trim($log->pic ?? '') ?: 'Unknown',
                'start_time'       => $localCreated->format('d M H:i'),
                'end_time'         => $localEnd ? $localEnd->format('H:i') : 'In Progress',
                'duration_minutes' => $durationMin,
                'target_minutes'   => $targetSetupMin,
                'is_overtime'      => ($durationMin > $targetSetupMin && $targetSetupMin > 0),
                'remark'           => $log->remark ?? '',
                'created_at'       => $localCreated->format('Y-m-d H:i:s'),
                'prod_date'        => $shiftInfo['date'],
            ];
            $processedAdjustLogs[] = $entry;
            $allActivityLogs[] = $entry;
        }

        // Format and categorize Mould Change Logs fast
        $processedMouldLogs = [];
        foreach ($mouldLogsRaw as $log) {
            $localCreated = self::getLocalCarbon($log->created_at);
            $shiftInfo = self::getProductionDateAndShift($localCreated, $isHalfDay, $preloadedHalfDayDates);
            $shiftNum = $shiftInfo['shift'];

            $durationMin = 0;
            if ($log->end_time) {
                $startSec = strtotime((string)$log->created_at);
                $endSec = strtotime((string)$log->end_time);
                $durationMin = max(0, round(($endSec - $startSec) / 60, 1));
            }

            $setupTimeSec = ($masterItems instanceof \Illuminate\Support\Collection)
                ? $masterItems->get($log->item_code)?->setup_time_minute
                : ($masterItems[$log->item_code] ?? null);
            $targetSetupMin = $setupTimeSec ?? 60;

            $localEnd = $log->end_time ? self::getLocalCarbon($log->end_time) : null;

            $entry = [
                'id'               => $log->id,
                'type'             => 'mould_change',
                'type_label'       => 'Mould Change',
                'shift'            => $shiftNum,
                'machine_name'     => $log->user->name ?? 'Unknown',
                'item_code'        => $log->item_code ?? '-',
                'pic'              => trim($log->pic ?? '') ?: 'Unknown',
                'start_time'       => $localCreated->format('d M H:i'),
                'end_time'         => $localEnd ? $localEnd->format('H:i') : 'In Progress',
                'duration_minutes' => $durationMin,
                'target_minutes'   => $targetSetupMin,
                'is_overtime'      => ($durationMin > $targetSetupMin && $targetSetupMin > 0),
                'remark'           => $log->remark ?? '',
                'created_at'       => $localCreated->format('Y-m-d H:i:s'),
                'prod_date'        => $shiftInfo['date'],
            ];
            $processedMouldLogs[] = $entry;
            $allActivityLogs[] = $entry;
        }

        // Format and categorize Repair Machine Logs fast
        $processedRepairLogs = [];
        if (!empty($repairLogsRaw)) {
            foreach ($repairLogsRaw as $log) {
                $localCreated = self::getLocalCarbon($log->created_at);
                $shiftInfo = self::getProductionDateAndShift($localCreated, $isHalfDay, $preloadedHalfDayDates);
                $shiftNum = $shiftInfo['shift'];

                $durationMin = 0;
                if ($log->finish_repair) {
                    $startSec = strtotime((string)$log->created_at);
                    $endSec = strtotime((string)$log->finish_repair);
                    $durationMin = max(0, round(($endSec - $startSec) / 60, 1));
                }

                $localEnd = $log->finish_repair ? self::getLocalCarbon($log->finish_repair) : null;

                $problemStr = trim($log->problem ?? '');
                $remarkStr = trim($log->remark ?? '');
                $fullRemark = $problemStr !== '' 
                    ? ($remarkStr !== '' ? "[{$problemStr}] {$remarkStr}" : "[{$problemStr}]")
                    : ($remarkStr !== '' ? $remarkStr : '');

                $entry = [
                    'id'               => $log->id,
                    'type'             => 'repair',
                    'type_label'       => 'Repair Machine',
                    'shift'            => $shiftNum,
                    'machine_name'     => $log->user->name ?? 'Unknown',
                    'item_code'        => $log->item_code ?? '-',
                    'pic'              => trim($log->pic ?? '') ?: 'Unknown',
                    'start_time'       => $localCreated->format('d M H:i'),
                    'end_time'         => $localEnd ? $localEnd->format('H:i') : 'In Progress',
                    'duration_minutes' => $durationMin,
                    'target_minutes'   => 0,
                    'is_overtime'      => false,
                    'problem'          => $problemStr,
                    'remark'           => $fullRemark,
                    'created_at'       => $localCreated->format('Y-m-d H:i:s'),
                    'prod_date'        => $shiftInfo['date'],
                ];
                $processedRepairLogs[] = $entry;
                $allActivityLogs[] = $entry;
            }
        }

        usort($allActivityLogs, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        // Aggregate by Shift (1, 2, 3)
        $shiftResults = [];
        $dicsByShift = [1 => [], 2 => [], 3 => []];
        foreach ($dailyData as $dic) {
            $s = (int)$dic->shift;
            if ($s >= 1 && $s <= 3) {
                $dicsByShift[$s][] = $dic;
            }
        }

        for ($s = 1; $s <= 3; $s++) {
            $shiftDics = $dicsByShift[$s];
            $shiftAdjusts = array_values(array_filter($processedAdjustLogs, fn($l) => $l['shift'] === $s));
            $shiftMoulds = array_values(array_filter($processedMouldLogs, fn($l) => $l['shift'] === $s));
            $shiftRepairs = array_values(array_filter($processedRepairLogs, fn($l) => $l['shift'] === $s));

            $target = 0;
            $actual = 0;
            $ng = 0;
            $ngBreakdownShift = [];

            foreach ($shiftDics as $daily) {
                foreach ($daily->hourlyRemarks as $hourly) {
                    $target += (int)($hourly->target ?? 0);
                    $actual += (int)($hourly->actual_production ?? 0);

                    if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                        foreach ($hourly->ngDetails as $ngDetail) {
                            $ngQty = (int)($ngDetail->ng_quantity ?? 0);
                            $ng += $ngQty;
                            $typeName = $ngDetail->ngType->ng_type ?? 'Unknown';
                            $ngBreakdownShift[$typeName] = ($ngBreakdownShift[$typeName] ?? 0) + $ngQty;
                        }
                    }
                }
            }

            arsort($ngBreakdownShift);
            $topNgTypes = [];
            foreach (array_slice($ngBreakdownShift, 0, 4, true) as $tName => $tQty) {
                $topNgTypes[] = [
                    'name'     => $tName,
                    'quantity' => $tQty,
                    'percent'  => $ng > 0 ? round(($tQty / $ng) * 100, 1) : 0,
                ];
            }

            $totalProduction = $actual + $ng;
            $ngRate = $totalProduction > 0 ? round(($ng / $totalProduction) * 100, 2) : 0;
            $achievementRate = $target > 0 ? round(($actual / $target) * 100, 1) : 0;

            // Distinct PICs for Adjuster, Mould Change, and Repair in this shift
            $distinctAdjusters = array_values(array_unique(array_filter(array_column($shiftAdjusts, 'pic'))));
            $distinctMouldChangers = array_values(array_unique(array_filter(array_column($shiftMoulds, 'pic'))));
            $distinctRepairers = array_values(array_unique(array_filter(array_column($shiftRepairs, 'pic'))));

            $totalAdjustDuration = array_sum(array_column($shiftAdjusts, 'duration_minutes'));
            $totalMouldDuration = array_sum(array_column($shiftMoulds, 'duration_minutes'));
            $totalRepairDuration = array_sum(array_column($shiftRepairs, 'duration_minutes'));

            $shiftResults[$s] = [
                'shift_number'                  => $s,
                'name'                          => $shiftDefs[$s]['name'],
                'time_range'                    => $shiftDefs[$s]['time'],
                'theme'                         => $shiftDefs[$s]['theme'],
                'adjusters'                     => $distinctAdjusters,
                'adjusters_str'                 => !empty($distinctAdjusters) ? implode(', ', $distinctAdjusters) : 'No Adjuster Logged',
                'mould_changers'                => $distinctMouldChangers,
                'mould_changers_str'            => !empty($distinctMouldChangers) ? implode(', ', $distinctMouldChangers) : 'No Mould Changer Logged',
                'repairers'                     => $distinctRepairers,
                'repairers_str'                 => !empty($distinctRepairers) ? implode(', ', $distinctRepairers) : 'No Repair Logged',
                'adjust_count'                  => count($shiftAdjusts),
                'adjust_duration_minutes'       => $totalAdjustDuration,
                'mould_change_count'            => count($shiftMoulds),
                'mould_change_duration_minutes' => $totalMouldDuration,
                'repair_count'                  => count($shiftRepairs),
                'repair_duration_minutes'       => $totalRepairDuration,
                'total_setup_minutes'           => $totalAdjustDuration + $totalMouldDuration,
                'total_target'                  => $target,
                'total_actual'                  => $actual,
                'total_ng'                      => $ng,
                'ng_rate'                       => $ngRate,
                'achievement_rate'              => $achievementRate,
                'top_ng_types'                  => $topNgTypes,
                'adjust_logs'                   => $shiftAdjusts,
                'mould_change_logs'             => $shiftMoulds,
                'repair_logs'                   => $shiftRepairs,
            ];
        }

        return [
            'shifts'                    => $shiftResults,
            'all_logs'                  => array_slice($allActivityLogs, 0, 100), // Limit payload size to 100 recent
            'total_adjust_count'        => count($processedAdjustLogs),
            'total_mould_change_count'  => count($processedMouldLogs),
            'total_repair_count'        => count($processedRepairLogs),
            'total_setup_time_minutes'  => array_sum(array_column($processedAdjustLogs, 'duration_minutes')) + array_sum(array_column($processedMouldLogs, 'duration_minutes')),
            'total_repair_time_minutes' => array_sum(array_column($processedRepairLogs, 'duration_minutes')),
        ];
    }

    /**
     * Backward-compatible getShiftPersonnelAndNgAnalysis
     */
    public function getShiftPersonnelAndNgAnalysis(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null
    ): array {
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        $windowStartUtc = Carbon::parse($startDateStr . ' 07:30:00', 'Asia/Jakarta')->setTimezone('UTC');
        $windowEndUtc = Carbon::parse($endDateStr . ' 07:30:00', 'Asia/Jakarta')->addDay()->setTimezone('UTC');

        $masterItems = MasterListItem::pluck('setup_time_minute', 'item_code');

        $effectivePlant = $plant;
        if (!$effectivePlant && $machineUserId) {
            $targetUser = User::find($machineUserId);
            if ($targetUser) {
                $effectivePlant = str_starts_with(strtoupper($targetUser->name), 'K') ? 'karawang' : 'kbn';
            }
        }

        $adjustQuery = AdjustMachineLog::query()
            ->with(['user:id,name'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        $mouldQuery = MouldChangeLog::query()
            ->with(['user:id,name'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        $repairQuery = RepairMachineLog::query()
            ->with(['user:id,name'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        if ($itemCode) {
            $adjustQuery->where('item_code', $itemCode);
            $mouldQuery->where('item_code', $itemCode);
            $repairQuery->where('item_code', $itemCode);
        }

        if ($machineUserId) {
            $adjustQuery->where('user_id', $machineUserId);
            $mouldQuery->where('user_id', $machineUserId);
            $repairQuery->where('user_id', $machineUserId);
        } elseif ($effectivePlant === 'karawang') {
            $adjustQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
            $mouldQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
            $repairQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($effectivePlant === 'kbn') {
            $adjustQuery->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
            $mouldQuery->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
            $repairQuery->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }
        $adjustLogsRaw = $adjustQuery->get();
        $mouldLogsRaw = $mouldQuery->get();
        $repairLogsRaw = $repairQuery->get();

        $dicQuery = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails.ngType', 'user:id,name'])
            ->whereBetween('start_date', [$startDateStr, $endDateStr]);

        if ($itemCode) $dicQuery->where('item_code', $itemCode);
        if ($machineUserId) $dicQuery->where('user_id', $machineUserId);
        if ($plant === 'karawang') $dicQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        elseif ($plant === 'kbn') $dicQuery->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));

        return $this->processShiftPersonnelAndNgAnalysis(
            $dicQuery->get(),
            $adjustLogsRaw,
            $mouldLogsRaw,
            $masterItems,
            $startDate,
            $endDate,
            null,
            null,
            $repairLogsRaw
        );
    }

    /**
     * Process Daily NG Trend per Adjuster Line Chart Data (Ultra-fast in-memory)
     */
    public function processAdjusterNgTrend(
        $dailyData,
        $adjustLogsRaw,
        Carbon $startDate,
        Carbon $endDate,
        bool|array|null $isHalfDay = null,
        ?array $preloadedHalfDayDates = null
    ): array {
        $period = CarbonPeriod::create($startDate, $endDate);
        $dateLabels = [];
        $dateStrings = [];

        foreach ($period as $date) {
            $dateLabels[] = $date->format('d M');
            $dateStrings[] = $date->format('Y-m-d');
        }

        // Map shift adjusters: $shiftAdjustersMap[date][shift] = [pic1, pic2]
        $shiftAdjustersMap = [];
        $allAdjusterNames = [];
        $adjusterStats = [];

        foreach ($adjustLogsRaw as $log) {
            $pic = trim($log->pic ?? '');
            if (empty($pic)) continue;

            $localCreated = self::getLocalCarbon($log->created_at);
            $shiftInfo = self::getProductionDateAndShift($localCreated, $isHalfDay, $preloadedHalfDayDates);
            $shift = $shiftInfo['shift'];
            $prodDate = $shiftInfo['date'];

            if (!isset($shiftAdjustersMap[$prodDate][$shift])) {
                $shiftAdjustersMap[$prodDate][$shift] = [];
            }
            if (!in_array($pic, $shiftAdjustersMap[$prodDate][$shift])) {
                $shiftAdjustersMap[$prodDate][$shift][] = $pic;
            }

            if (!in_array($pic, $allAdjusterNames)) {
                $allAdjusterNames[] = $pic;
            }

            if (!isset($adjusterStats[$pic])) {
                $adjusterStats[$pic] = [
                    'adjust_count'   => 0,
                    'adjust_minutes' => 0,
                ];
            }
            $adjusterStats[$pic]['adjust_count']++;
            if ($log->end_time) {
                $startSec = strtotime((string)$log->created_at);
                $endSec = strtotime((string)$log->end_time);
                $adjusterStats[$pic]['adjust_minutes'] += max(0, ($endSec - $startSec) / 60);
            }
        }

        // Group daily item codes by date & shift
        $shiftProductionMap = [];
        foreach ($dailyData as $dic) {
            $dStr = $dic->start_date;
            $s = (int)$dic->shift;
            if (!$s) $s = 1;

            if (!isset($shiftProductionMap[$dStr][$s])) {
                $shiftProductionMap[$dStr][$s] = ['actual' => 0, 'ng' => 0];
            }

            foreach ($dic->hourlyRemarks as $hourly) {
                $shiftProductionMap[$dStr][$s]['actual'] += (int)($hourly->actual_production ?? 0);
                if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                    foreach ($hourly->ngDetails as $ngDetail) {
                        $shiftProductionMap[$dStr][$s]['ng'] += (int)($ngDetail->ng_quantity ?? 0);
                    }
                }
            }
        }

        // Calculate daily NG per Adjuster
        $adjusterDailyNg = [];
        $adjusterDailyActual = [];

        foreach ($allAdjusterNames as $adj) {
            $adjusterDailyNg[$adj] = array_fill_keys($dateStrings, 0);
            $adjusterDailyActual[$adj] = array_fill_keys($dateStrings, 0);
        }

        foreach ($shiftProductionMap as $dStr => $shifts) {
            if (!isset($shiftAdjustersMap[$dStr])) continue;

            foreach ($shifts as $sNum => $prod) {
                $shiftNg = $prod['ng'];
                $shiftActual = $prod['actual'];
                $adjustersInShift = $shiftAdjustersMap[$dStr][$sNum] ?? [];

                if (!empty($adjustersInShift)) {
                    $count = count($adjustersInShift);
                    $baseNg = intdiv($shiftNg, $count);
                    $remainderNg = $shiftNg % $count;

                    $baseActual = intdiv($shiftActual, $count);
                    $remainderActual = $shiftActual % $count;

                    foreach ($adjustersInShift as $idx => $adj) {
                        $adjNg = $baseNg + ($idx < $remainderNg ? 1 : 0);
                        $adjActual = $baseActual + ($idx < $remainderActual ? 1 : 0);

                        if (isset($adjusterDailyNg[$adj][$dStr])) {
                            $adjusterDailyNg[$adj][$dStr] += $adjNg;
                            $adjusterDailyActual[$adj][$dStr] += $adjActual;
                        }
                    }
                }
            }
        }

        // Build Datasets for Chart.js
        $palette = [
            '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899',
            '#06b6d4', '#f97316', '#14b8a6', '#6366f1', '#84cc16'
        ];

        $datasets = [];
        $adjusterSummaries = [];
        $colorIdx = 0;

        foreach ($allAdjusterNames as $adj) {
            $color = $palette[$colorIdx % count($palette)];
            $ngValues = array_values($adjusterDailyNg[$adj]);
            $actualValues = array_values($adjusterDailyActual[$adj]);

            $totalNg = array_sum($ngValues);
            $totalActual = array_sum($actualValues);
            $totalProd = $totalActual + $totalNg;
            $ngRate = $totalProd > 0 ? round(($totalNg / $totalProd) * 100, 2) : 0;

            $datasets[] = [
                'label'            => $adj,
                'data'             => $ngValues,
                'borderColor'      => $color,
                'backgroundColor'  => $color,
                'fill'             => false,
                'tension'          => 0.3,
                'borderWidth'      => 2.5,
                'pointRadius'      => 3,
                'pointHoverRadius' => 6,
            ];

            $adjusterSummaries[] = [
                'name'           => $adj,
                'color'          => $color,
                'total_ng'       => $totalNg,
                'total_actual'   => $totalActual,
                'ng_rate'        => $ngRate,
                'adjust_count'   => $adjusterStats[$adj]['adjust_count'] ?? 0,
                'adjust_minutes' => round($adjusterStats[$adj]['adjust_minutes'] ?? 0, 1),
            ];

            $colorIdx++;
        }

        usort($adjusterSummaries, fn($a, $b) => $b['total_ng'] <=> $a['total_ng']);

        return [
            'labels'             => $dateLabels,
            'datasets'           => $datasets,
            'adjuster_summaries' => $adjusterSummaries,
            'has_data'           => count($datasets) > 0,
        ];
    }

    /**
     * Backward-compatible getAdjusterNgTrendChartData
     */
    public function getAdjusterNgTrendChartData(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null,
        bool|array|null $isHalfDay = null
    ): array {
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        $windowStartUtc = Carbon::parse($startDateStr . ' 07:30:00', 'Asia/Jakarta')->setTimezone('UTC');
        $windowEndUtc = Carbon::parse($endDateStr . ' 07:30:00', 'Asia/Jakarta')->addDay()->setTimezone('UTC');

        $effectivePlant = $plant;
        if (!$effectivePlant && $machineUserId) {
            $targetUser = User::find($machineUserId);
            if ($targetUser) {
                $effectivePlant = str_starts_with(strtoupper($targetUser->name), 'K') ? 'karawang' : 'kbn';
            }
        }

        $adjustQuery = AdjustMachineLog::query()
            ->with(['user:id,name'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        if ($itemCode) {
            $adjustQuery->where('item_code', $itemCode);
        }

        if ($machineUserId) {
            $adjustQuery->where('user_id', $machineUserId);
        } elseif ($effectivePlant === 'karawang') {
            $adjustQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($effectivePlant === 'kbn') {
            $adjustQuery->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }
        $adjustLogs = $adjustQuery->get();

        $dicQuery = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails'])
            ->whereBetween('start_date', [$startDateStr, $endDateStr]);

        if ($itemCode) $dicQuery->where('item_code', $itemCode);
        if ($machineUserId) $dicQuery->where('user_id', $machineUserId);
        if ($plant === 'karawang') $dicQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        elseif ($plant === 'kbn') $dicQuery->whereHas('user', fn($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));

        return $this->processAdjusterNgTrend($dicQuery->get(), $adjustLogs, $startDate, $endDate, $isHalfDay);
    }

    /**
     * Get unique item codes for filter using indexed start_date
     */
    public function getItemCodes(?int $year = null, ?int $month = null, ?string $plant = null, ?string $date = null): array
    {
        $query = DailyItemCode::select('item_code')->distinct();

        if ($date) {
            $query->whereDate('start_date', $date);
        } else {
            if ($year && $month) {
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
                $query->whereBetween('start_date', [$startDate, $endDate]);
            } elseif ($year) {
                $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear()->format('Y-m-d');
                $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear()->format('Y-m-d');
                $query->whereBetween('start_date', [$startDate, $endDate]);
            }
        }

        if ($plant === 'karawang') {
            $krwIds = User::where(function($q) {
                $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%');
            })->pluck('id')->toArray();
            $query->whereIn('user_id', $krwIds);
        } elseif ($plant === 'kbn') {
            $kbnIds = User::where(function($q) {
                $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%');
            })->pluck('id')->toArray();
            $query->whereIn('user_id', $kbnIds);
        }

        return $query->orderBy('item_code')
            ->pluck('item_code')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Get weeks in a month
     */
    public function getWeeksInMonth(int $year, int $month): array
    {
        $date = Carbon::createFromDate($year, $month, 1);
        $endOfMonth = $date->copy()->endOfMonth();

        $weeks = [];
        $weekNumber = 1;

        while ($date <= $endOfMonth) {
            $weekStart = $date->copy()->startOfWeek();
            $weekEnd = $date->copy()->endOfWeek();

            if ($weekStart->month != $month) {
                $weekStart = $date->copy()->startOfMonth();
            }

            if ($weekEnd->month != $month) {
                $weekEnd = $date->copy()->endOfMonth();
            }

            $weeks[] = [
                'number' => $weekNumber,
                'label'  => "Week {$weekNumber} ({$weekStart->format('d')} - {$weekEnd->format('d')})",
                'start'  => $weekStart->format('Y-m-d'),
                'end'    => $weekEnd->format('Y-m-d'),
            ];

            $date->addWeek()->startOfWeek();
            $weekNumber++;
        }

        return $weeks;
    }
}