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
use Illuminate\Support\Facades\Cache;

class ProductionDashboardService
{
    /**
     * Fast hour extraction from time strings like "08:00", "08:00:00", "8", or "2026-08-15 08:00:00"
     */
    private static function parseHourSlot($rawTime): int
    {
        if ($rawTime === null || $rawTime === '') {
            return 0;
        }
        if (is_numeric($rawTime)) {
            $h = (int) $rawTime;

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
            return (int) substr($rawTime, 0, $colonPos);
        }

        return (int) $rawTime;
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
            $halfDayActive = ($targetDate->isSaturday() && ! empty($isHalfDay['saturday']))
                || ($targetDate->isSunday() && ! empty($isHalfDay['sunday']))
                || (! empty($isHalfDay[$prodDate]))
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
     * Get all dashboard data with high-performance caching (3 min TTL).
     */
    public function getAllDashboardData(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null,
        bool|array|null $isHalfDay = null,
        bool $forceRefresh = false
    ): array {
        if (app()->runningUnitTests() && ! $forceRefresh) {
            return $this->computeAllDashboardData(
                $startDate,
                $endDate,
                $itemCode,
                $machineUserId,
                $plant,
                $isHalfDay
            );
        }

        $cacheKey = 'prod_dash_' . md5(json_encode([
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $itemCode,
            $machineUserId,
            $plant,
            $isHalfDay,
        ]));

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 180, function () use (
            $startDate,
            $endDate,
            $itemCode,
            $machineUserId,
            $plant,
            $isHalfDay
        ) {
            return $this->computeAllDashboardData(
                $startDate,
                $endDate,
                $itemCode,
                $machineUserId,
                $plant,
                $isHalfDay
            );
        });
    }

    /**
     * Compute all dashboard data in a single unified, ultra-fast pass.
     */
    public function computeAllDashboardData(
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
                ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
                ->flip()
                ->toArray();
        } catch (\Throwable $e) {
            $preloadedHalfDayDates = [];
        }

        // Pre-fetch machine IDs for the selected plant for instant indexed queries (no slow whereHas subqueries)
        $plantMachineIds = null;
        if ($plant === 'karawang') {
            $plantMachineIds = User::where(function ($q) {
                $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%');
            })->pluck('id')->toArray();
        } elseif ($plant === 'kbn') {
            $plantMachineIds = User::where(function ($q) {
                $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%');
            })->pluck('id')->toArray();
        }

        // 1. Single database query for DailyItemCodes
        $dicQuery = DailyItemCode::query()
            ->with([
                'hourlyRemarks.ngDetails.ngType',
                'user:id,name',
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
        $masterItems = ! empty($neededItemCodes)
            ? MasterListItem::when(\Illuminate\Support\Facades\Schema::hasTable('master_customer_deliveries'), fn ($q) => $q->with('customer'))
                ->whereIn('item_code', $neededItemCodes)
                ->get()
                ->keyBy('item_code')
            : collect();

        // 3. Fetch Adjust Logs & Mould Change Logs in precise local timestamp window
        // Note: logs are created in UTC or local server time, query by window
        $windowStartUtc = Carbon::parse($startDateStr . ' 07:30:00', 'Asia/Jakarta')->setTimezone('UTC');
        $windowEndUtc = Carbon::parse($endDateStr . ' 07:30:00', 'Asia/Jakarta')->addDay()->setTimezone('UTC');

        // Strict plant filtering for Adjust and Mould Change logs
        $effectivePlant = $plant;
        if (! $effectivePlant && $machineUserId) {
            $targetUser = User::find($machineUserId);
            if ($targetUser) {
                $effectivePlant = str_starts_with(strtoupper($targetUser->name), 'K') ? 'karawang' : 'kbn';
            }
        }

        $adjustQuery = AdjustMachineLog::query()
            ->with(['user:id,name', 'ngDetails.ngType'])
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
            $krwMachineIds = ($plant === 'karawang' && $plantMachineIds !== null)
                ? $plantMachineIds
                : User::where(function ($q) {
                    $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%');
                })->pluck('id')->toArray();
            $adjustQuery->whereIn('user_id', $krwMachineIds);
            $mouldQuery->whereIn('user_id', $krwMachineIds);
            $repairQuery->whereIn('user_id', $krwMachineIds);
        } elseif ($effectivePlant === 'kbn') {
            $kbnMachineIds = ($plant === 'kbn' && $plantMachineIds !== null)
                ? $plantMachineIds
                : User::where(function ($q) {
                    $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%');
                })->pluck('id')->toArray();
            $adjustQuery->whereIn('user_id', $kbnMachineIds);
            $mouldQuery->whereIn('user_id', $kbnMachineIds);
            $repairQuery->whereIn('user_id', $kbnMachineIds);
        }
        $adjustLogsRaw = $adjustQuery->get();
        $mouldLogsRaw = $mouldQuery->get();
        $repairLogsRaw = $repairQuery->get();

        // 4. In-Memory Process All Sections Fast
        $productionResult = $this->processProductionData($dailyData, $startDate, $endDate, $adjustLogsRaw);
        $ngBreakdown = $this->processNgBreakdown($dailyData, $masterItems, $adjustLogsRaw);
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

        $ngExecutiveSummary = $this->processExecutiveNgAnalysis(
            $dailyData,
            $masterItems,
            $adjustLogsRaw,
            $ngBreakdown,
            $productionResult['summary'] ?? []
        );

        return [
            'chart_data' => $productionResult['chart_data'] ?? [],
            'summary' => $productionResult['summary'] ?? [],
            'purging_details' => $productionResult['purging_details'] ?? [],
            'ng_breakdown' => $ngBreakdown,
            'ng_executive_summary' => $ngExecutiveSummary,
            'downtime_analysis' => $downtimeAnalysis,
            'top_remarks' => $topRemarks,
            'machine_working_hours' => $machineWorkingHours,
            'shift_personnel_analysis' => $shiftPersonnelAnalysis,
            'adjuster_ng_trend' => $adjusterNgTrend,
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

        if ($itemCode) {
            $query->where('item_code', $itemCode);
        }
        if ($machineUserId) {
            $query->where('user_id', $machineUserId);
        }
        if ($plant === 'karawang') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($plant === 'kbn') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }

        return $this->processProductionData($query->get(), $startDate, $endDate);
    }

    /**
     * Process raw collection into chart-ready format (Fast in-memory)
     */
    public function processProductionData($dailyData, Carbon $startDate, Carbon $endDate, $adjustLogsRaw = null): array
    {
        $chartData = [];
        $summary = [
            'total_target' => 0,
            'total_actual' => 0,
            'total_ng' => 0,
            'ng_rate' => 0,
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
                    if ($hourSlot < 0 || $hourSlot > 23) {
                        $hourSlot = 0;
                    }

                    $target = (int) ($hourly->target ?? 0);
                    $actual = (int) ($hourly->actual_production ?? 0);
                    $ng = 0;

                    if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                        foreach ($hourly->ngDetails as $ngDetail) {
                            $ng += (int) ($ngDetail->ng_quantity ?? 0);
                        }
                    }

                    $hourlyBuckets[$hourSlot]['target'] += $target;
                    $hourlyBuckets[$hourSlot]['actual'] += $actual;
                    $hourlyBuckets[$hourSlot]['ng'] += $ng;

                    if ($machineId && ! in_array($machineId, $hourlyBuckets[$hourSlot]['machines'])) {
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
                    'date' => sprintf('%02d:00', $hour),
                    'full_date' => sprintf('%s %02d:00', $dateStr, $hour),
                    'target' => $target,
                    'actual' => $actual,
                    'ng' => $ng,
                    'ng_rate' => $totalProd > 0 ? round(($ng / $totalProd) * 100, 2) : 0,
                    'achievement' => $target > 0 ? round(($actual / $target) * 100, 2) : 0,
                    'working_hours' => count($b['machines']),
                ];

                $summary['total_target'] += $target;
                $summary['total_actual'] += $actual;
                $summary['total_ng'] += $ng;
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
                    if (! $machineId) {
                        continue;
                    }

                    foreach ($daily->hourlyRemarks as $hourly) {
                        $hourSlot = self::parseHourSlot($hourly->start_time);
                        $slotKey = $machineId . '_' . $hourSlot;
                        if (! isset($dayUniqueSlots[$slotKey])) {
                            $dayUniqueSlots[$slotKey] = true;
                        }

                        $target += (int) ($hourly->target ?? 0);
                        $actual += (int) ($hourly->actual_production ?? 0);

                        if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                            foreach ($hourly->ngDetails as $ngDetail) {
                                $ng += (int) ($ngDetail->ng_quantity ?? 0);
                            }
                        }
                    }
                }

                $totalProd = $actual + $ng;

                $chartData[] = [
                    'date' => $date->format('d M'),
                    'full_date' => $dateStr,
                    'target' => $target,
                    'actual' => $actual,
                    'ng' => $ng,
                    'ng_rate' => $totalProd > 0 ? round(($ng / $totalProd) * 100, 2) : 0,
                    'achievement' => $target > 0 ? round(($actual / $target) * 100, 2) : 0,
                    'working_hours' => count($dayUniqueSlots),
                ];

                $summary['total_target'] += $target;
                $summary['total_actual'] += $actual;
                $summary['total_ng'] += $ng;
            }
        }

        $totalAdjusterNg = 0;
        if ($adjustLogsRaw) {
            foreach ($adjustLogsRaw as $adjLog) {
                if ($adjLog->relationLoaded('ngDetails') || $adjLog->ngDetails) {
                    foreach ($adjLog->ngDetails as $ngDetail) {
                        $totalAdjusterNg += (int) ($ngDetail->ng_quantity ?? 0);
                    }
                }
            }
        }

        $summary['total_ng_production'] = $summary['total_ng'];
        $summary['total_adjuster_ng'] = $totalAdjusterNg;
        $summary['total_ng'] += $totalAdjusterNg;

        $totalProduction = $summary['total_actual'] + $summary['total_ng'];
        $summary['ng_rate'] = $totalProduction > 0
            ? round(($summary['total_ng'] / $totalProduction) * 100, 2)
            : 0;

        $summary['achievement_rate'] = $summary['total_target'] > 0
            ? round(($summary['total_actual'] / $summary['total_target']) * 100, 2)
            : 0;

        $summary['total_purging'] = (float) $dailyData->sum('resin_usage');

        $purgingDetails = $dailyData->filter(function ($dic) {
            return $dic->resin_usage !== null && $dic->resin_usage > 0;
        })->map(function ($dic) {
            return [
                'dic_id' => $dic->id,
                'date' => $dic->start_date ?? $dic->schedule_date,
                'shift' => $dic->shift,
                'machine_name' => $dic->user->name ?? 'Unknown',
                'item_code' => $dic->item_code,
                'resin_usage' => (float) $dic->resin_usage,
            ];
        })->values()->take(50)->toArray();

        return [
            'chart_data' => $chartData,
            'summary' => $summary,
            'purging_details' => $purgingDetails,
        ];
    }

    /**
     * Process NG breakdown by defect type with model breakdown and detailed remarks
     */
    public function processNgBreakdown($dailyData, $masterItems = null, $adjustLogs = null): array
    {
        $ngBreakdown = [];

        if ($masterItems === null) {
            $neededCodes = collect($dailyData)->pluck('item_code')->filter()->unique()->values()->toArray();
            $masterItems = ! empty($neededCodes)
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
                    $hourlyRemarkText = trim((string) ($hourly->remark ?? ''));

                    foreach ($hourly->ngDetails as $ngDetail) {
                        $qty = (int) ($ngDetail->ng_quantity ?? 0);
                        if ($qty <= 0) {
                            continue;
                        }

                        $ngTypeName = trim((string) ($ngDetail->ngType->ng_type ?? 'Unknown'));
                        if ($ngTypeName === '') {
                            $ngTypeName = 'Unknown';
                        }

                        $ngRemarkText = trim((string) ($ngDetail->ng_remarks ?? ''));

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

                        if (! isset($ngBreakdown[$ngTypeName])) {
                            $ngBreakdown[$ngTypeName] = [
                                'name' => $ngTypeName,
                                'total' => 0,
                                'models' => [],
                            ];
                        }

                        $ngBreakdown[$ngTypeName]['total'] += $qty;

                        if (! isset($ngBreakdown[$ngTypeName]['models'][$itemCode])) {
                            $ngBreakdown[$ngTypeName]['models'][$itemCode] = [
                                'item_code' => $itemCode,
                                'item_name' => $itemName,
                                'total' => 0,
                                'total_records' => 0,
                                'records' => [],
                            ];
                        }

                        $ngBreakdown[$ngTypeName]['models'][$itemCode]['total'] += $qty;
                        $ngBreakdown[$ngTypeName]['models'][$itemCode]['total_records']++;

                        // Cap records to 5 per model to keep DOM and memory footprint ultra-lightweight
                        if (count($ngBreakdown[$ngTypeName]['models'][$itemCode]['records']) < 5) {
                            $ngBreakdown[$ngTypeName]['models'][$itemCode]['records'][] = [
                                'date' => $dateStr,
                                'shift' => $shift,
                                'hour' => $hourLabel,
                                'machine' => $machineName,
                                'quantity' => $qty,
                                'remark' => $displayRemark,
                                'ng_remark' => $ngRemarkText ?: null,
                                'hourly_remark' => $hourlyRemarkText ?: null,
                            ];
                        }
                    }
                }
            }
        }

        if ($adjustLogs) {
            foreach ($adjustLogs as $adjustLog) {
                if (! $adjustLog->relationLoaded('ngDetails') && ! $adjustLog->ngDetails) {
                    continue;
                }
                $itemCode = $adjustLog->item_code ?: 'UNKNOWN';
                $master = $masterItems instanceof \Illuminate\Support\Collection
                    ? $masterItems->get($itemCode)
                    : ($masterItems[$itemCode] ?? null);
                $itemName = $master?->item_name ?: $itemCode;
                $machineName = $adjustLog->user?->name ?: '-';
                $createdLocal = self::getLocalCarbon($adjustLog->created_at);
                $dateStr = $createdLocal->format('d M Y');
                $hourLabel = $createdLocal->format('H:i') . ' (Adjust)';
                $picName = trim((string) ($adjustLog->pic ?? ''));

                foreach ($adjustLog->ngDetails as $ngDetail) {
                    $qty = (int) ($ngDetail->ng_quantity ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $ngTypeName = trim((string) ($ngDetail->ngType?->ng_type ?? 'Unknown'));
                    if ($ngTypeName === '') {
                        $ngTypeName = 'Unknown';
                    }
                    $ngRemarkText = trim((string) ($ngDetail->ng_remarks ?? ''));
                    $displayRemark = '[Adjuster: ' . ($picName ?: '-') . '] ' . ($ngRemarkText ?: ($adjustLog->remark ?: '-'));

                    if (! isset($ngBreakdown[$ngTypeName])) {
                        $ngBreakdown[$ngTypeName] = [
                            'name' => $ngTypeName,
                            'total' => 0,
                            'models' => [],
                        ];
                    }
                    $ngBreakdown[$ngTypeName]['total'] += $qty;

                    if (! isset($ngBreakdown[$ngTypeName]['models'][$itemCode])) {
                        $ngBreakdown[$ngTypeName]['models'][$itemCode] = [
                            'item_code' => $itemCode,
                            'item_name' => $itemName,
                            'total' => 0,
                            'total_records' => 0,
                            'records' => [],
                        ];
                    }
                    $ngBreakdown[$ngTypeName]['models'][$itemCode]['total'] += $qty;
                    $ngBreakdown[$ngTypeName]['models'][$itemCode]['total_records']++;

                    if (count($ngBreakdown[$ngTypeName]['models'][$itemCode]['records']) < 5) {
                        $ngBreakdown[$ngTypeName]['models'][$itemCode]['records'][] = [
                            'date' => $dateStr,
                            'shift' => '-',
                            'hour' => $hourLabel,
                            'machine' => $machineName,
                            'quantity' => $qty,
                            'remark' => $displayRemark,
                            'ng_remark' => $ngRemarkText ?: null,
                            'hourly_remark' => 'Adjuster: ' . ($picName ?: '-'),
                        ];
                    }
                }
            }
        }

        // Urutkan models di dalam setiap defect type berdasarkan total desc dan batasi ke top 10
        foreach ($ngBreakdown as &$typeData) {
            $models = array_values($typeData['models']);
            usort($models, fn ($a, $b) => $b['total'] <=> $a['total']);
            $typeData['models_count'] = count($models);
            // Cap to top 10 models per defect type to prevent massive DOM footprint
            $typeData['models'] = array_slice($models, 0, 10);
        }
        unset($typeData);

        // Hanya sertakan defect type yang memiliki NG (> 0)
        $ngBreakdown = array_filter($ngBreakdown, fn ($t) => ($t['total'] ?? 0) > 0);
        usort($ngBreakdown, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_values($ngBreakdown);
    }

    /**
     * Executive Quality Analysis & Pareto Generator tailored for Board of Directors (BOD).
     * Computes Pareto 80/20 defect ranking, Customer risk exposure, Shift breakdown, and Machine Quality Ranking.
     * Segregates data into 3 modes: 'all' (combined), 'production' (running), and 'adjuster' (setting).
     */
    public function processExecutiveNgAnalysis($dailyData, $masterItems, $adjustLogs, array $ngBreakdown, array $summary = []): array
    {
        $totalActual = (int) ($summary['total_actual'] ?? 0);

        // 1. Extract Production Records from $dailyData (Running Operator NG)
        $productionRecords = [];
        foreach ($dailyData as $daily) {
            $itemCode = $daily->item_code ?? 'UNKNOWN';
            $machineName = $daily->user?->name ?: '-';
            $shift = in_array((int) $daily->shift, [1, 2, 3], true) ? (int) $daily->shift : 1;

            foreach ($daily->hourlyRemarks as $hourly) {
                if ($hourly->ngDetails) {
                    foreach ($hourly->ngDetails as $ngDetail) {
                        $qty = (int) ($ngDetail->ng_quantity ?? 0);
                        if ($qty <= 0) {
                            continue;
                        }
                        $ngType = trim((string) ($ngDetail->ngType?->ng_type ?? 'Unknown'));
                        if ($ngType === '') {
                            $ngType = 'Unknown';
                        }
                        $productionRecords[] = [
                            'source' => 'production',
                            'item_code' => $itemCode,
                            'machine_name' => $machineName,
                            'shift' => $shift,
                            'ng_type' => $ngType,
                            'qty' => $qty,
                        ];
                    }
                }
            }
        }

        // 2. Extract Adjuster Records from $adjustLogs (Setting / Trial Scrap)
        $adjusterRecords = [];
        if ($adjustLogs) {
            foreach ($adjustLogs as $adjustLog) {
                if (! $adjustLog->relationLoaded('ngDetails') && ! $adjustLog->ngDetails) {
                    continue;
                }
                $itemCode = $adjustLog->item_code ?: 'UNKNOWN';
                $machineName = $adjustLog->user?->name ?: '-';
                $createdLocal = self::getLocalCarbon($adjustLog->created_at);
                $shiftInfo = self::getProductionDateAndShift($createdLocal, false);
                $shift = in_array((int) ($shiftInfo['shift'] ?? 1), [1, 2, 3], true) ? (int) $shiftInfo['shift'] : 1;

                foreach ($adjustLog->ngDetails as $ngDetail) {
                    $qty = (int) ($ngDetail->ng_quantity ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $ngType = trim((string) ($ngDetail->ngType?->ng_type ?? 'NG Adjuster'));
                    if ($ngType === '') {
                        $ngType = 'NG Adjuster';
                    }
                    $adjusterRecords[] = [
                        'source' => 'adjuster',
                        'item_code' => $itemCode,
                        'machine_name' => $machineName,
                        'shift' => $shift,
                        'ng_type' => $ngType,
                        'qty' => $qty,
                    ];
                }
            }
        }

        $totalProdNg = array_sum(array_column($productionRecords, 'qty'));
        $totalAdjNg = array_sum(array_column($adjusterRecords, 'qty'));
        $totalCombinedNg = $totalProdNg + $totalAdjNg;

        $allRecords = array_merge($productionRecords, $adjusterRecords);

        $sliceAll = $this->aggregateNgSlice($allRecords, $totalActual, $masterItems, 'all', $totalCombinedNg);
        $sliceProduction = $this->aggregateNgSlice($productionRecords, $totalActual, $masterItems, 'production', $totalCombinedNg);
        $sliceAdjuster = $this->aggregateNgSlice($adjusterRecords, $totalActual, $masterItems, 'adjuster', $totalCombinedNg);

        return [
            'total_combined_ng' => $totalCombinedNg,
            'total_production_ng' => $totalProdNg,
            'total_adjuster_ng' => $totalAdjNg,
            'modes' => [
                'all' => $sliceAll,
                'production' => $sliceProduction,
                'adjuster' => $sliceAdjuster,
            ],
            ...$sliceAll,
        ];
    }

    /**
     * Aggregates a slice of NG records (all, production, or adjuster) into an executive summary.
     */
    protected function aggregateNgSlice(array $records, int $totalActual, $masterItems, string $mode, int $totalCombinedNg): array
    {
        $totalNg = array_sum(array_column($records, 'qty'));
        $ngRate = ($totalActual + $totalNg) > 0 ? round(($totalNg / ($totalActual + $totalNg)) * 100, 2) : 0.0;

        // Toleransi Mutu Pabrik: <=2.0% Aman, 2.1-3.0% Waspada, >3.0% Kritis
        $healthStatus = 'Aman';
        $healthClass = 'emerald';
        if ($ngRate > 3.0) {
            $healthStatus = 'Kritis';
            $healthClass = 'rose';
        } elseif ($ngRate > 2.0) {
            $healthStatus = 'Waspada';
            $healthClass = 'amber';
        }

        // 1. Defect Pareto Breakdown
        $defectCounts = [];
        $partAggregates = [];
        $shiftCounts = [1 => 0, 2 => 0, 3 => 0];
        $machineAggregates = [];

        foreach ($records as $r) {
            $qty = $r['qty'];
            $ngType = $r['ng_type'];
            $itemCode = $r['item_code'];
            $machineName = $r['machine_name'];
            $shift = $r['shift'];

            // Defect Pareto
            $defectCounts[$ngType] = ($defectCounts[$ngType] ?? 0) + $qty;

            // Part Aggregation
            if (! isset($partAggregates[$itemCode])) {
                $master = $masterItems instanceof \Illuminate\Support\Collection
                    ? $masterItems->get($itemCode)
                    : ($masterItems[$itemCode] ?? null);
                $customerName = $master?->customer?->customer_name ?? $master?->customer_code ?? 'Internal / Non-Customer';
                $itemName = $master?->item_name ?: $itemCode;

                $partAggregates[$itemCode] = [
                    'item_code' => $itemCode,
                    'item_name' => $itemName,
                    'customer' => $customerName,
                    'total_ng' => 0,
                    'defects' => [],
                ];
            }
            $partAggregates[$itemCode]['total_ng'] += $qty;
            $partAggregates[$itemCode]['defects'][$ngType] = ($partAggregates[$itemCode]['defects'][$ngType] ?? 0) + $qty;

            // Shift Aggregation
            $shiftCounts[$shift] = ($shiftCounts[$shift] ?? 0) + $qty;

            // Machine Aggregation
            if (! isset($machineAggregates[$machineName])) {
                $isKarawang = preg_match('/^K\d{3,4}[A-Z]?$/i', $machineName);
                $machineAggregates[$machineName] = [
                    'machine_name' => $machineName,
                    'plant' => $isKarawang ? 'KRW' : 'KBN',
                    'total_ng' => 0,
                    'defects' => [],
                ];
            }
            $machineAggregates[$machineName]['total_ng'] += $qty;
            $machineAggregates[$machineName]['defects'][$ngType] = ($machineAggregates[$machineName]['defects'][$ngType] ?? 0) + $qty;
        }

        arsort($defectCounts);

        $paretoDefects = [];
        $runningCumulative = 0.0;
        $chartLabels = [];
        $chartQuantities = [];
        $chartCumulative = [];
        $rank = 1;

        foreach ($defectCounts as $name => $qty) {
            $pct = $totalNg > 0 ? round(($qty / $totalNg) * 100, 1) : 0.0;
            $runningCumulative = min(100.0, round($runningCumulative + $pct, 1));

            // Klasifikasi akar masalah standar industri injection moulding
            $nameUpper = strtoupper($name);
            $category = 'Operasional';
            if (preg_match('/GASMARK|SINK|FLOW|SILVER|BURN|FLASH|WELD|JET/i', $nameUpper)) {
                $category = 'Setting / Parameter';
            } elseif (preg_match('/SHORT|SCRATCH|PIN|EJECT|CRACK|BEND|DEFORM/i', $nameUpper)) {
                $category = 'Moulding / Cetakan';
            } elseif (preg_match('/BLACK|BUBL|BUBBLE|FOREIGN|CONTAM|MOIST/i', $nameUpper)) {
                $category = 'Raw Material / Drying';
            }

            $defectItem = [
                'rank' => $rank,
                'name' => $name,
                'total' => $qty,
                'percentage' => $pct,
                'cumulative_percentage' => $runningCumulative,
                'category' => $category,
            ];
            $paretoDefects[] = $defectItem;

            if ($rank <= 8) {
                $chartLabels[] = $name;
                $chartQuantities[] = $qty;
                $chartCumulative[] = $runningCumulative;
            }
            $rank++;
        }

        // Top 5 Affected Parts
        uasort($partAggregates, fn ($a, $b) => $b['total_ng'] <=> $a['total_ng']);
        $topAffectedParts = [];
        foreach (array_slice($partAggregates, 0, 5) as $p) {
            arsort($p['defects']);
            $domDefect = ! empty($p['defects']) ? array_key_first($p['defects']) : '-';
            $pShare = $totalNg > 0 ? round(($p['total_ng'] / $totalNg) * 100, 1) : 0.0;
            $topAffectedParts[] = [
                'item_code' => $p['item_code'],
                'item_name' => $p['item_name'],
                'customer' => $p['customer'],
                'total_ng' => $p['total_ng'],
                'dominant_defect' => $domDefect,
                'percentage' => $pShare,
            ];
        }

        // Top 5 Machines by Reject
        uasort($machineAggregates, fn ($a, $b) => $b['total_ng'] <=> $a['total_ng']);
        $topMachinesQuality = [];
        foreach (array_slice($machineAggregates, 0, 5) as $m) {
            arsort($m['defects']);
            $domDefect = ! empty($m['defects']) ? array_key_first($m['defects']) : '-';
            $mShare = $totalNg > 0 ? round(($m['total_ng'] / $totalNg) * 100, 1) : 0.0;
            $topMachinesQuality[] = [
                'machine_name' => $m['machine_name'],
                'plant' => $m['plant'],
                'total_ng' => $m['total_ng'],
                'dominant_defect' => $domDefect,
                'percentage' => $mShare,
            ];
        }

        // Shift Summary
        $totalShiftNg = array_sum($shiftCounts);
        $shiftSummary = [];
        $worstShiftNumber = 1;
        $maxShiftNg = -1;
        foreach ([1 => 'Shift 1 (Pagi)', 2 => 'Shift 2 (Sore)', 3 => 'Shift 3 (Malam)'] as $sNum => $sLabel) {
            $sQty = $shiftCounts[$sNum] ?? 0;
            $sPct = $totalShiftNg > 0 ? round(($sQty / $totalShiftNg) * 100, 1) : 0.0;
            $shiftSummary[$sNum] = [
                'shift_number' => $sNum,
                'label' => $sLabel,
                'total_ng' => $sQty,
                'percentage' => $sPct,
            ];
            if ($sQty > $maxShiftNg) {
                $maxShiftNg = $sQty;
                $worstShiftNumber = $sNum;
            }
        }

        return [
            'mode' => $mode,
            'total_ng' => $totalNg,
            'total_actual' => $totalActual,
            'ng_rate' => $ngRate,
            'health_status' => $healthStatus,
            'health_class' => $healthClass,
            'dominant_defect' => ! empty($paretoDefects[0]) ? $paretoDefects[0]['name'] : '-',
            'dominant_defect_qty' => ! empty($paretoDefects[0]) ? $paretoDefects[0]['total'] : 0,
            'dominant_defect_pct' => ! empty($paretoDefects[0]) ? $paretoDefects[0]['percentage'] : 0.0,
            'worst_shift' => "Shift {$worstShiftNumber}",
            'worst_shift_label' => $shiftSummary[$worstShiftNumber]['label'] ?? "Shift {$worstShiftNumber}",
            'worst_shift_qty' => max(0, $maxShiftNg),
            'pareto_defects' => $paretoDefects,
            'top_affected_parts' => $topAffectedParts,
            'top_machines_quality' => $topMachinesQuality,
            'shift_summary' => $shiftSummary,
            'pareto_chart' => [
                'labels' => $chartLabels,
                'quantities' => $chartQuantities,
                'cumulative' => $chartCumulative,
            ],
        ];
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

        if ($itemCode) {
            $query->where('item_code', $itemCode);
        }
        if ($machineUserId) {
            $query->where('user_id', $machineUserId);
        }
        if ($plant === 'karawang') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($plant === 'kbn') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }

        return $this->processNgBreakdown($query->get());
    }

    /**
     * Process Downtime Analysis
     */
    public function processDowntimeAnalysis($dailyData, $masterItems): array
    {
        $totalDowntime = 0;
        $downtimeByHour = [];
        $problemHours = [];
        $mergedHours = [];

        foreach ($dailyData as $daily) {
            $hourlyByHour = [];

            foreach ($daily->hourlyRemarks as $hourly) {
                $hour = self::parseHourSlot($hourly->start_time);
                $hourly->_hour_num = $hour;
                $hourly->_hour_label = sprintf('%02d:00', $hour);

                if (isset($hourlyByHour[$hour])) {
                    $existingActual = $hourlyByHour[$hour]->actual_production ?? 0;
                    $newActual = $hourly->actual_production ?? 0;
                    if ($newActual > $existingActual) {
                        $hourlyByHour[$hour] = $hourly;
                    }
                } else {
                    $hourlyByHour[$hour] = $hourly;
                }
            }

            foreach ($hourlyByHour as $hour => $hourly) {
                $target = (int) ($hourly->target ?? 0);
                $actual = (int) ($hourly->actual_production ?? 0);

                if ($target <= 0 || $actual >= $target) {
                    continue;
                }

                $cycleTimeSec = ($daily->temporal_cycle_time && $daily->temporal_cycle_time > 0)
                    ? $daily->temporal_cycle_time
                    : ($masterItems instanceof \Illuminate\Support\Collection ? $masterItems->get($daily->item_code)?->cycle_time : ($masterItems[$daily->item_code] ?? null));

                if (! $cycleTimeSec || $cycleTimeSec <= 0) {
                    continue;
                }

                $cycleTimeMinutes = $cycleTimeSec / 60;
                $actualMinutes = $actual * $cycleTimeMinutes;

                $key = ($daily->user_id ?? 'unknown') . '_' . $daily->start_date . '_' . $hour;

                if (! isset($mergedHours[$key])) {
                    $startLabel = sprintf('%02d:00', $hour);
                    $endLabel = sprintf('%02d:00', ($hour + 1) % 24);

                    $mergedHours[$key] = [
                        'machine' => $daily->user_id ?? 'unknown',
                        'date' => $daily->start_date,
                        'hour' => $hour,
                        'hour_label' => $startLabel . ' - ' . $endLabel,
                        'total_prod_min' => 0,
                        'remarks' => [],
                        'items' => [],
                    ];
                }

                $mergedHours[$key]['total_prod_min'] += $actualMinutes;
                if (! empty($hourly->remark)) {
                    $mergedHours[$key]['remarks'][] = $hourly->remark;
                }
                $mergedHours[$key]['items'][] = [
                    'id' => $hourly->id,
                    'item_code' => $daily->item_code,
                    'target' => $target,
                    'actual' => $actual,
                    'cycle_time' => $cycleTimeSec,
                    'prod_minutes' => round($actualMinutes, 2),
                ];
            }
        }

        foreach ($mergedHours as $merged) {
            $hour = $merged['hour'];
            $hourLabel = $merged['hour_label'];

            $finalDowntime = max(0, 60 - $merged['total_prod_min']);
            if ($finalDowntime <= 0) {
                continue;
            }

            $totalDowntime += $finalDowntime;

            if (! isset($downtimeByHour[$hour])) {
                $downtimeByHour[$hour] = [
                    'hour' => $hourLabel,
                    'total_downtime' => 0,
                    'occurrences' => 0,
                ];
            }

            $downtimeByHour[$hour]['total_downtime'] += $finalDowntime;
            $downtimeByHour[$hour]['occurrences']++;

            $problemHours[] = [
                'id' => $merged['items'][0]['id'] ?? null,
                'date' => $merged['date'],
                'hour' => $hourLabel,
                'total_prod_min' => round($merged['total_prod_min'], 2),
                'downtime' => round($finalDowntime, 2),
                'remark' => implode(' | ', array_filter($merged['remarks'])),
                'items' => $merged['items'],
            ];
        }

        uasort($downtimeByHour, fn ($a, $b) => $b['occurrences'] <=> $a['occurrences']);

        return [
            'total_downtime_minutes' => round($totalDowntime, 2),
            'total_downtime_hours' => round($totalDowntime / 60, 2),
            'downtime_by_hour' => array_values($downtimeByHour),
            'problem_hours_count' => count($problemHours),
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

        if ($itemCode) {
            $query->where('item_code', $itemCode);
        }
        if ($machineUserId) {
            $query->where('user_id', $machineUserId);
        }
        if ($plant === 'karawang') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($plant === 'kbn') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }

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
                    $newActual = $hourly->actual_production ?? 0;
                    if ($newActual > $existingActual) {
                        $hourlyByHour[$hour] = $hourly;
                    }
                } else {
                    $hourlyByHour[$hour] = $hourly;
                }
            }

            foreach ($hourlyByHour as $hour => $hourly) {
                $target = (int) ($hourly->target ?? 0);
                $actual = (int) ($hourly->actual_production ?? 0);

                if ($target <= 0 || $actual >= $target) {
                    continue;
                }
                if (empty($hourly->remark)) {
                    continue;
                }

                $cycleTimeSec = ($daily->temporal_cycle_time && $daily->temporal_cycle_time > 0)
                    ? $daily->temporal_cycle_time
                    : ($masterItems instanceof \Illuminate\Support\Collection ? $masterItems->get($daily->item_code)?->cycle_time : ($masterItems[$daily->item_code] ?? null));

                if (! $cycleTimeSec || $cycleTimeSec <= 0) {
                    continue;
                }

                $cycleTimeMinutes = $cycleTimeSec / 60;
                $actualMinutes = $actual * $cycleTimeMinutes;

                $key = ($daily->user_id ?? 'unknown') . '_' . $daily->start_date . '_' . $hour;

                if (! isset($mergedHours[$key])) {
                    $startLabel = sprintf('%02d:00', $hour);
                    $endLabel = sprintf('%02d:00', ($hour + 1) % 24);

                    $mergedHours[$key] = [
                        'date' => $daily->start_date,
                        'hour' => $hour,
                        'hour_label' => $startLabel . ' - ' . $endLabel,
                        'machine' => $daily->user->name ?? 'Unknown',
                        'total_prod_min' => 0,
                        'remarks' => [],
                        'items' => [],
                    ];
                }

                $mergedHours[$key]['total_prod_min'] += $actualMinutes;
                $mergedHours[$key]['remarks'][] = $hourly->remark;
                $mergedHours[$key]['items'][] = [
                    'item_code' => $daily->item_code,
                    'target' => $target,
                    'actual' => $actual,
                    'cycle_time' => $cycleTimeSec,
                ];
            }
        }

        $problemRemarks = [];

        foreach ($mergedHours as $merged) {
            $finalDowntime = max(0, 60 - $merged['total_prod_min']);
            if ($finalDowntime <= 0) {
                continue;
            }

            $totalTarget = collect($merged['items'])->sum('target');
            $totalActual = collect($merged['items'])->sum('actual');
            $gap = $totalTarget - $totalActual;

            $problemRemarks[] = [
                'date' => $merged['date'],
                'hour' => $merged['hour_label'],
                'machine' => $merged['machine'],
                'item_code' => collect($merged['items'])->pluck('item_code')->unique()->implode(', '),
                'target' => $totalTarget,
                'actual' => $totalActual,
                'gap' => $gap,
                'downtime_minutes' => round($finalDowntime, 2),
                'remark' => implode(' | ', array_filter($merged['remarks'])),
                'severity' => $this->calculateSeverity($gap, $totalTarget),
            ];
        }

        usort($problemRemarks, fn ($a, $b) => $b['gap'] - $a['gap']);

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

        if ($itemCode) {
            $query->where('item_code', $itemCode);
        }
        if ($machineUserId) {
            $query->where('user_id', $machineUserId);
        }
        if ($plant === 'karawang') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($plant === 'kbn') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }

        $dailyData = $query->get();
        $masterItems = MasterListItem::whereIn('item_code', $dailyData->pluck('item_code')->unique())
            ->pluck('cycle_time', 'item_code');

        return $this->processTopProblematicRemarks($dailyData, $masterItems);
    }

    private function calculateSeverity(int $gap, int $target): string
    {
        if ($target == 0) {
            return 'low';
        }
        $percentage = ($gap / $target) * 100;
        if ($percentage >= 50) {
            return 'critical';
        }
        if ($percentage >= 30) {
            return 'high';
        }
        if ($percentage >= 15) {
            return 'medium';
        }

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

            if (! $machineId) {
                continue;
            }

            if (! isset($machineHours[$machineId])) {
                $machineHours[$machineId] = [
                    'id' => $machineId,
                    'name' => $machineName,
                    'hours' => 0,
                    'unique_slots' => [],
                ];
            }

            foreach ($daily->hourlyRemarks as $hourly) {
                $hourSlot = self::parseHourSlot($hourly->start_time);
                $slotKey = $daily->start_date . '_' . $hourSlot;

                if (! isset($machineHours[$machineId]['unique_slots'][$slotKey])) {
                    $machineHours[$machineId]['unique_slots'][$slotKey] = true;
                    $machineHours[$machineId]['hours'] += 1;
                }
            }
        }

        uasort($machineHours, fn ($a, $b) => $b['hours'] <=> $a['hours']);

        return array_map(function ($item) {
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

        if ($itemCode) {
            $query->where('item_code', $itemCode);
        }
        if ($machineUserId) {
            $query->where('user_id', $machineUserId);
        }
        if ($plant === 'karawang') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($plant === 'kbn') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }

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
                $isHalfDaySingleDay = ($startDate->isSaturday() && ! empty($isHalfDay['saturday']))
                    || ($startDate->isSunday() && ! empty($isHalfDay['sunday']))
                    || (! empty($isHalfDay[$startDate->format('Y-m-d')]))
                    || self::isDateHalfDay($startDate, $preloadedHalfDayDates);
            } else {
                $isHalfDaySingleDay = self::isDateHalfDay($startDate, $preloadedHalfDayDates);
            }
        }

        $shiftDefs = [
            1 => [
                'name' => 'Shift 1 (Pagi)',
                'time' => ! $isSingleDay
                    ? '07:30 - 15:30 (Setengah Hari: 07:30 - 12:30)'
                    : ($isHalfDaySingleDay ? '07:30 - 12:30' : '07:30 - 15:30'),
                'theme' => 'amber',
            ],
            2 => [
                'name' => 'Shift 2 (Sore)',
                'time' => ! $isSingleDay
                    ? '15:30 - 23:30 (Setengah Hari: 12:30 - 17:30)'
                    : ($isHalfDaySingleDay ? '12:30 - 17:30' : '15:30 - 23:30'),
                'theme' => 'emerald',
            ],
            3 => [
                'name' => 'Shift 3 (Malam)',
                'time' => ! $isSingleDay
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
                $startSec = strtotime((string) $log->created_at);
                $endSec = strtotime((string) $log->end_time);
                $durationMin = max(0, round(($endSec - $startSec) / 60, 1));
            }

            $setupTimeSec = ($masterItems instanceof \Illuminate\Support\Collection)
                ? $masterItems->get($log->item_code)?->setup_time_minute
                : ($masterItems[$log->item_code] ?? null);
            $targetSetupMin = $setupTimeSec ?? 30;

            $localEnd = $log->end_time ? self::getLocalCarbon($log->end_time) : null;

            $entry = [
                'id' => $log->id,
                'type' => 'adjust',
                'type_label' => 'Adjust Machine',
                'shift' => $shiftNum,
                'machine_name' => $log->user->name ?? 'Unknown',
                'item_code' => $log->item_code ?? '-',
                'pic' => trim($log->pic ?? '') ?: 'Unknown',
                'start_time' => $localCreated->format('d M H:i'),
                'end_time' => $localEnd ? $localEnd->format('H:i') : 'In Progress',
                'duration_minutes' => $durationMin,
                'target_minutes' => $targetSetupMin,
                'is_overtime' => ($durationMin > $targetSetupMin && $targetSetupMin > 0),
                'remark' => $log->remark ?? '',
                'created_at' => $localCreated->format('Y-m-d H:i:s'),
                'prod_date' => $shiftInfo['date'],
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
                $startSec = strtotime((string) $log->created_at);
                $endSec = strtotime((string) $log->end_time);
                $durationMin = max(0, round(($endSec - $startSec) / 60, 1));
            }

            $setupTimeSec = ($masterItems instanceof \Illuminate\Support\Collection)
                ? $masterItems->get($log->item_code)?->setup_time_minute
                : ($masterItems[$log->item_code] ?? null);
            $targetSetupMin = $setupTimeSec ?? 60;

            $localEnd = $log->end_time ? self::getLocalCarbon($log->end_time) : null;

            $entry = [
                'id' => $log->id,
                'type' => 'mould_change',
                'type_label' => 'Mould Change',
                'shift' => $shiftNum,
                'machine_name' => $log->user->name ?? 'Unknown',
                'item_code' => $log->item_code ?? '-',
                'pic' => trim($log->pic ?? '') ?: 'Unknown',
                'start_time' => $localCreated->format('d M H:i'),
                'end_time' => $localEnd ? $localEnd->format('H:i') : 'In Progress',
                'duration_minutes' => $durationMin,
                'target_minutes' => $targetSetupMin,
                'is_overtime' => ($durationMin > $targetSetupMin && $targetSetupMin > 0),
                'remark' => $log->remark ?? '',
                'created_at' => $localCreated->format('Y-m-d H:i:s'),
                'prod_date' => $shiftInfo['date'],
            ];
            $processedMouldLogs[] = $entry;
            $allActivityLogs[] = $entry;
        }

        // Format and categorize Repair Machine Logs fast
        $processedRepairLogs = [];
        if (! empty($repairLogsRaw)) {
            foreach ($repairLogsRaw as $log) {
                $localCreated = self::getLocalCarbon($log->created_at);
                $shiftInfo = self::getProductionDateAndShift($localCreated, $isHalfDay, $preloadedHalfDayDates);
                $shiftNum = $shiftInfo['shift'];

                $durationMin = 0;
                if ($log->finish_repair) {
                    $startSec = strtotime((string) $log->created_at);
                    $endSec = strtotime((string) $log->finish_repair);
                    $durationMin = max(0, round(($endSec - $startSec) / 60, 1));
                }

                $localEnd = $log->finish_repair ? self::getLocalCarbon($log->finish_repair) : null;

                $problemStr = trim($log->problem ?? '');
                $remarkStr = trim($log->remark ?? '');
                $fullRemark = $problemStr !== ''
                    ? ($remarkStr !== '' ? "[{$problemStr}] {$remarkStr}" : "[{$problemStr}]")
                    : ($remarkStr !== '' ? $remarkStr : '');

                $entry = [
                    'id' => $log->id,
                    'type' => 'repair',
                    'type_label' => 'Repair Machine',
                    'shift' => $shiftNum,
                    'machine_name' => $log->user->name ?? 'Unknown',
                    'item_code' => $log->item_code ?? '-',
                    'pic' => trim($log->pic ?? '') ?: 'Unknown',
                    'start_time' => $localCreated->format('d M H:i'),
                    'end_time' => $localEnd ? $localEnd->format('H:i') : 'In Progress',
                    'duration_minutes' => $durationMin,
                    'target_minutes' => 0,
                    'is_overtime' => false,
                    'problem' => $problemStr,
                    'remark' => $fullRemark,
                    'created_at' => $localCreated->format('Y-m-d H:i:s'),
                    'prod_date' => $shiftInfo['date'],
                ];
                $processedRepairLogs[] = $entry;
                $allActivityLogs[] = $entry;
            }
        }

        usort($allActivityLogs, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        // Aggregate by Shift (1, 2, 3)
        $shiftResults = [];
        $dicsByShift = [1 => [], 2 => [], 3 => []];
        foreach ($dailyData as $dic) {
            $s = (int) $dic->shift;
            if ($s >= 1 && $s <= 3) {
                $dicsByShift[$s][] = $dic;
            }
        }

        for ($s = 1; $s <= 3; $s++) {
            $shiftDics = $dicsByShift[$s];
            $shiftAdjusts = array_values(array_filter($processedAdjustLogs, fn ($l) => $l['shift'] === $s));
            $shiftMoulds = array_values(array_filter($processedMouldLogs, fn ($l) => $l['shift'] === $s));
            $shiftRepairs = array_values(array_filter($processedRepairLogs, fn ($l) => $l['shift'] === $s));

            $target = 0;
            $actual = 0;
            $ng = 0;
            $ngBreakdownShift = [];

            foreach ($shiftDics as $daily) {
                foreach ($daily->hourlyRemarks as $hourly) {
                    $target += (int) ($hourly->target ?? 0);
                    $actual += (int) ($hourly->actual_production ?? 0);

                    if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                        foreach ($hourly->ngDetails as $ngDetail) {
                            $ngQty = (int) ($ngDetail->ng_quantity ?? 0);
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
                    'name' => $tName,
                    'quantity' => $tQty,
                    'percent' => $ng > 0 ? round(($tQty / $ng) * 100, 1) : 0,
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
                'shift_number' => $s,
                'name' => $shiftDefs[$s]['name'],
                'time_range' => $shiftDefs[$s]['time'],
                'theme' => $shiftDefs[$s]['theme'],
                'adjusters' => $distinctAdjusters,
                'adjusters_str' => ! empty($distinctAdjusters) ? implode(', ', $distinctAdjusters) : 'No Adjuster Logged',
                'mould_changers' => $distinctMouldChangers,
                'mould_changers_str' => ! empty($distinctMouldChangers) ? implode(', ', $distinctMouldChangers) : 'No Mould Changer Logged',
                'repairers' => $distinctRepairers,
                'repairers_str' => ! empty($distinctRepairers) ? implode(', ', $distinctRepairers) : 'No Repair Logged',
                'adjust_count' => count($shiftAdjusts),
                'adjust_duration_minutes' => $totalAdjustDuration,
                'mould_change_count' => count($shiftMoulds),
                'mould_change_duration_minutes' => $totalMouldDuration,
                'repair_count' => count($shiftRepairs),
                'repair_duration_minutes' => $totalRepairDuration,
                'total_setup_minutes' => $totalAdjustDuration + $totalMouldDuration,
                'total_setup_hours' => round(($totalAdjustDuration + $totalMouldDuration) / 60, 1),
                'total_target' => $target,
                'total_actual' => $actual,
                'total_ng' => $ng,
                'ng_rate' => $ngRate,
                'achievement_rate' => $achievementRate,
                'top_ng_types' => $topNgTypes,
                'adjust_logs' => $shiftAdjusts,
                'mould_change_logs' => $shiftMoulds,
                'repair_logs' => $shiftRepairs,
            ];
        }

        $allSetupMinutes = array_sum(array_column($processedAdjustLogs, 'duration_minutes')) + array_sum(array_column($processedMouldLogs, 'duration_minutes'));

        return [
            'shifts' => $shiftResults,
            'all_logs' => array_slice($allActivityLogs, 0, 100), // Limit payload size to 100 recent
            'total_adjust_count' => count($processedAdjustLogs),
            'total_mould_change_count' => count($processedMouldLogs),
            'total_repair_count' => count($processedRepairLogs),
            'total_setup_time_minutes' => $allSetupMinutes,
            'total_setup_time_hours' => round($allSetupMinutes / 60, 1),
            'total_repair_time_minutes' => array_sum(array_column($processedRepairLogs, 'duration_minutes')),
        ];
    }

    /**
     * Backward-compatible getShiftPersonnelAnalysis alias
     */
    public function getShiftPersonnelAnalysis(
        Carbon $startDate,
        Carbon $endDate,
        ?string $itemCode = null,
        ?string $machineUserId = null,
        ?string $plant = null
    ): array {
        return $this->getShiftPersonnelAndNgAnalysis($startDate, $endDate, $itemCode, $machineUserId, $plant);
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
        if (! $effectivePlant && $machineUserId) {
            $targetUser = User::find($machineUserId);
            if ($targetUser) {
                $effectivePlant = str_starts_with(strtoupper($targetUser->name), 'K') ? 'karawang' : 'kbn';
            }
        }

        $adjustQuery = AdjustMachineLog::query()
            ->with(['user:id,name', 'ngDetails.ngType'])
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
            $adjustQuery->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
            $mouldQuery->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
            $repairQuery->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($effectivePlant === 'kbn') {
            $adjustQuery->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
            $mouldQuery->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
            $repairQuery->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }
        $adjustLogsRaw = $adjustQuery->get();
        $mouldLogsRaw = $mouldQuery->get();
        $repairLogsRaw = $repairQuery->get();

        $dicQuery = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails.ngType', 'user:id,name'])
            ->whereBetween('start_date', [$startDateStr, $endDateStr]);

        if ($itemCode) {
            $dicQuery->where('item_code', $itemCode);
        }
        if ($machineUserId) {
            $dicQuery->where('user_id', $machineUserId);
        }
        if ($plant === 'karawang') {
            $dicQuery->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($plant === 'kbn') {
            $dicQuery->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }

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
        $today = Carbon::now('Asia/Jakarta')->endOfDay();
        $chartStart = Carbon::parse($startDate->format('Y-m-d'), 'Asia/Jakarta')->startOfDay();
        $chartEnd = Carbon::parse($endDate->format('Y-m-d'), 'Asia/Jakarta')->endOfDay();
        $effectiveChartEndDate = ($chartStart->lte($today) && $chartEnd->gt($today)) ? $today : $chartEnd;
        $period = CarbonPeriod::create($chartStart, $effectiveChartEndDate);
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
        $adjusterDailyDirectNg = [];

        foreach ($adjustLogsRaw as $log) {
            $pic = trim($log->pic ?? '');
            if (empty($pic)) {
                continue;
            }

            $localCreated = self::getLocalCarbon($log->created_at);
            $shiftInfo = self::getProductionDateAndShift($localCreated, $isHalfDay, $preloadedHalfDayDates);
            $shift = $shiftInfo['shift'];
            $prodDate = $shiftInfo['date'];

            if (! isset($shiftAdjustersMap[$prodDate][$shift])) {
                $shiftAdjustersMap[$prodDate][$shift] = [];
            }
            if (! in_array($pic, $shiftAdjustersMap[$prodDate][$shift])) {
                $shiftAdjustersMap[$prodDate][$shift][] = $pic;
            }

            if (! in_array($pic, $allAdjusterNames)) {
                $allAdjusterNames[] = $pic;
            }

            if (! isset($adjusterStats[$pic])) {
                $adjusterStats[$pic] = [
                    'adjust_count' => 0,
                    'adjust_minutes' => 0,
                    'adjuster_direct_ng' => 0,
                    'adjuster_ng_breakdown' => [],
                ];
            }
            $adjusterStats[$pic]['adjust_count']++;
            if ($log->end_time) {
                $startSec = strtotime((string) $log->created_at);
                $endSec = strtotime((string) $log->end_time);
                $adjusterStats[$pic]['adjust_minutes'] += max(0, ($endSec - $startSec) / 60);
            }

            if ($log->relationLoaded('ngDetails') || $log->ngDetails) {
                foreach ($log->ngDetails as $ngDetail) {
                    $qty = (int) ($ngDetail->ng_quantity ?? 0);
                    $typeName = $ngDetail->ngType?->ng_type ?? 'Unknown';
                    $adjusterStats[$pic]['adjuster_direct_ng'] += $qty;
                    $adjusterStats[$pic]['adjuster_ng_breakdown'][$typeName] = ($adjusterStats[$pic]['adjuster_ng_breakdown'][$typeName] ?? 0) + $qty;

                    $adjusterDailyDirectNg[$pic][$prodDate] = ($adjusterDailyDirectNg[$pic][$prodDate] ?? 0) + $qty;
                }
            }
        }

        // Group daily item codes by date & shift
        $shiftProductionMap = [];
        foreach ($dailyData as $dic) {
            $dStr = $dic->start_date;
            $s = (int) $dic->shift;
            if (! $s) {
                $s = 1;
            }

            if (! isset($shiftProductionMap[$dStr][$s])) {
                $shiftProductionMap[$dStr][$s] = ['actual' => 0, 'ng' => 0];
            }

            foreach ($dic->hourlyRemarks as $hourly) {
                $shiftProductionMap[$dStr][$s]['actual'] += (int) ($hourly->actual_production ?? 0);
                if ($hourly->ngDetails && $hourly->ngDetails->count() > 0) {
                    foreach ($hourly->ngDetails as $ngDetail) {
                        $shiftProductionMap[$dStr][$s]['ng'] += (int) ($ngDetail->ng_quantity ?? 0);
                    }
                }
            }
        }

        // Calculate daily NG per Adjuster
        $adjusterDailyProdNg = [];
        $adjusterDailyActual = [];

        foreach ($shiftProductionMap as $dStr => $shifts) {
            $adjustersOnDay = $shiftAdjustersMap[$dStr] ?? [];

            foreach ($shifts as $sNum => $prod) {
                $shiftNg = $prod['ng'];
                $shiftActual = $prod['actual'];
                $adjustersInShift = $adjustersOnDay[$sNum] ?? [];

                // Fallback 1: If no adjuster specifically logged on this shift, check adjusters from other shifts on this day
                if (empty($adjustersInShift) && ! empty($adjustersOnDay)) {
                    $allDayAdjusters = array_unique(array_merge(...array_values($adjustersOnDay)));
                    if (! empty($allDayAdjusters)) {
                        $adjustersInShift = array_values($allDayAdjusters);
                    }
                }

                // Fallback 2: If no adjuster logged on this date at all, fallback to previous day's shift adjusters or known adjusters
                if (empty($adjustersInShift) && ! empty($allAdjusterNames)) {
                    $prevDate = Carbon::parse($dStr)->subDay()->format('Y-m-d');
                    if (! empty($shiftAdjustersMap[$prevDate][$sNum])) {
                        $adjustersInShift = $shiftAdjustersMap[$prevDate][$sNum];
                    }
                }

                if (! empty($adjustersInShift)) {
                    $count = count($adjustersInShift);
                    $baseNg = intdiv($shiftNg, $count);
                    $remainderNg = $shiftNg % $count;

                    $baseActual = intdiv($shiftActual, $count);
                    $remainderActual = $shiftActual % $count;

                    foreach ($adjustersInShift as $idx => $adj) {
                        $adjNg = $baseNg + ($idx < $remainderNg ? 1 : 0);
                        $adjActual = $baseActual + ($idx < $remainderActual ? 1 : 0);

                        $adjusterDailyProdNg[$adj][$dStr] = ($adjusterDailyProdNg[$adj][$dStr] ?? 0) + $adjNg;
                        $adjusterDailyActual[$adj][$dStr] = ($adjusterDailyActual[$adj][$dStr] ?? 0) + $adjActual;
                    }
                }
            }
        }

        // Build Datasets for Chart.js
        $palette = [
            '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899',
            '#06b6d4', '#f97316', '#14b8a6', '#6366f1', '#84cc16',
        ];

        $datasets = [];
        $adjusterSummaries = [];
        $colorIdx = 0;

        foreach ($allAdjusterNames as $adj) {
            $color = $palette[$colorIdx % count($palette)];

            $prodValues = [];
            $directValues = [];
            $totalValues = [];
            $actualValues = [];

            foreach ($dateStrings as $dStr) {
                $pNg = $adjusterDailyProdNg[$adj][$dStr] ?? 0;
                $dNg = $adjusterDailyDirectNg[$adj][$dStr] ?? 0;
                $act = $adjusterDailyActual[$adj][$dStr] ?? 0;

                $prodValues[] = $pNg;
                $directValues[] = $dNg;
                $totalValues[] = $pNg + $dNg;
                $actualValues[] = $act;
            }

            $ngProduction = array_sum($prodValues);
            $directNg = $adjusterStats[$adj]['adjuster_direct_ng'] ?? array_sum($directValues);
            $totalNg = $ngProduction + $directNg; // NG Production + NG Adjuster
            $totalActual = array_sum($actualValues);
            $totalProd = $totalActual + $totalNg;
            $ngRate = $totalProd > 0 ? round(($totalNg / $totalProd) * 100, 2) : 0;

            $datasets[] = [
                'label' => $adj,
                'data' => $totalValues, // Combined Total NG (NG Produksi + NG Setting Adjust)
                'prodNg' => $prodValues,
                'directNg' => $directValues,
                'borderColor' => $color,
                'backgroundColor' => $color,
                'fill' => false,
                'tension' => 0.3,
                'borderWidth' => 2.5,
                'pointRadius' => 3,
                'pointHoverRadius' => 6,
            ];

            $adjusterSummaries[] = [
                'name' => $adj,
                'color' => $color,
                'ng_production' => $ngProduction,
                'total_ng' => $totalNg,
                'total_actual' => $totalActual,
                'ng_rate' => $ngRate,
                'adjust_count' => $adjusterStats[$adj]['adjust_count'] ?? 0,
                'adjust_minutes' => round($adjusterStats[$adj]['adjust_minutes'] ?? 0, 1),
                'direct_ng' => $directNg,
                'direct_ng_breakdown' => $adjusterStats[$adj]['adjuster_ng_breakdown'] ?? [],
            ];

            $colorIdx++;
        }

        // Sort by NG rate descending (highest defect rate first for GM executive view)
        usort($adjusterSummaries, function ($a, $b) {
            if ($b['ng_rate'] !== $a['ng_rate']) {
                return $b['ng_rate'] <=> $a['ng_rate'];
            }

            return $b['total_ng'] <=> $a['total_ng'];
        });

        return [
            'labels' => $dateLabels,
            'datasets' => $datasets,
            'adjuster_summaries' => $adjusterSummaries,
            'has_data' => count($datasets) > 0,
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
        if (! $effectivePlant && $machineUserId) {
            $targetUser = User::find($machineUserId);
            if ($targetUser) {
                $effectivePlant = str_starts_with(strtoupper($targetUser->name), 'K') ? 'karawang' : 'kbn';
            }
        }

        $adjustQuery = AdjustMachineLog::query()
            ->with(['user:id,name', 'ngDetails.ngType'])
            ->where('created_at', '>=', $windowStartUtc->format('Y-m-d H:i:s'))
            ->where('created_at', '<', $windowEndUtc->format('Y-m-d H:i:s'));

        if ($itemCode) {
            $adjustQuery->where('item_code', $itemCode);
        }

        if ($machineUserId) {
            $adjustQuery->where('user_id', $machineUserId);
        } elseif ($effectivePlant === 'karawang') {
            $adjustQuery->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($effectivePlant === 'kbn') {
            $adjustQuery->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }
        $adjustLogs = $adjustQuery->get();

        $dicQuery = DailyItemCode::query()
            ->with(['hourlyRemarks.ngDetails'])
            ->whereBetween('start_date', [$startDateStr, $endDateStr]);

        if ($itemCode) {
            $dicQuery->where('item_code', $itemCode);
        }
        if ($machineUserId) {
            $dicQuery->where('user_id', $machineUserId);
        }
        if ($plant === 'karawang') {
            $dicQuery->whereHas('user', fn ($q) => $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%'));
        } elseif ($plant === 'kbn') {
            $dicQuery->whereHas('user', fn ($q) => $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%'));
        }

        return $this->processAdjusterNgTrend($dicQuery->get(), $adjustLogs, $startDate, $endDate, $isHalfDay);
    }

    /**
     * Get unique item codes for filter using indexed start_date
     */
    public function getItemCodes(?int $year = null, ?int $month = null, ?string $plant = null, ?string $date = null, ?string $search = null, int $limit = 50): array
    {
        $query = DailyItemCode::select('item_code')->distinct();

        if (! empty($search)) {
            $query->where('item_code', 'LIKE', '%' . $search . '%');
        }

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
            $krwIds = User::where(function ($q) {
                $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%');
            })->pluck('id')->toArray();
            $query->whereIn('user_id', $krwIds);
        } elseif ($plant === 'kbn') {
            $kbnIds = User::where(function ($q) {
                $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%');
            })->pluck('id')->toArray();
            $query->whereIn('user_id', $kbnIds);
        }

        if ($limit > 0) {
            $query->limit($limit);
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
                'label' => "Week {$weekNumber} ({$weekStart->format('d')} - {$weekEnd->format('d')})",
                'start' => $weekStart->format('Y-m-d'),
                'end' => $weekEnd->format('Y-m-d'),
            ];

            $date->addWeek()->startOfWeek();
            $weekNumber++;
        }

        return $weeks;
    }

    /**
     * Get aggregated annual production dashboard data designed specifically for Board of Directors (BOD).
     * Single unified pass across 12 months with machine breakdown and top defect analysis.
     */
    public function getYearlyDashboardData(
        int $year,
        ?string $plant = null,
        ?int $machineUserId = null,
        bool $forceRefresh = false
    ): array {
        $cacheKey = "production_yearly_dashboard_v3_{$year}_{$plant}_{$machineUserId}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 300, function () use ($year, $plant, $machineUserId) {
            $startDateStr = "{$year}-01-01";
            $endDateStr = "{$year}-12-31";

            // 1. Filter Machine IDs by Plant (Karawang vs KBN)
            $plantMachineIds = null;
            if ($plant === 'karawang') {
                $plantMachineIds = User::where(function ($q) {
                    $q->where('name', 'LIKE', 'K%')->orWhere('name', 'LIKE', 'k%');
                })->pluck('id')->toArray();
            } elseif ($plant === 'kbn') {
                $plantMachineIds = User::where(function ($q) {
                    $q->where('name', 'NOT LIKE', 'K%')->where('name', 'NOT LIKE', 'k%');
                })->pluck('id')->toArray();
            }

            // 2. Fetch Annual DailyItemCodes
            $dicQuery = DailyItemCode::query()
                ->with([
                    'hourlyRemarks.ngDetails.ngType',
                    'user:id,name',
                ])
                ->whereBetween('start_date', [$startDateStr, $endDateStr]);

            if ($machineUserId) {
                $dicQuery->where('user_id', $machineUserId);
            } elseif ($plantMachineIds !== null) {
                $dicQuery->whereIn('user_id', $plantMachineIds);
            }

            $dailyData = $dicQuery->get();

            // 3. Fetch Annual Downtime & Adjust Logs
            $windowStartUtc = Carbon::parse("{$year}-01-01 07:30:00", 'Asia/Jakarta')->setTimezone('UTC');
            $windowEndUtc = Carbon::parse(($year + 1) . '-01-01 07:30:00', 'Asia/Jakarta')->setTimezone('UTC');

            $mouldQuery = MouldChangeLog::query()
                ->where('created_at', '>=', $windowStartUtc)
                ->where('created_at', '<', $windowEndUtc);

            $repairQuery = RepairMachineLog::query()
                ->where('created_at', '>=', $windowStartUtc)
                ->where('created_at', '<', $windowEndUtc);

            $adjustQuery = AdjustMachineLog::query()
                ->with('ngDetails.ngType')
                ->where('created_at', '>=', $windowStartUtc)
                ->where('created_at', '<', $windowEndUtc);

            if ($machineUserId) {
                $mouldQuery->where('user_id', $machineUserId);
                $repairQuery->where('user_id', $machineUserId);
                $adjustQuery->where('user_id', $machineUserId);
            } elseif ($plantMachineIds !== null) {
                $mouldQuery->whereIn('user_id', $plantMachineIds);
                $repairQuery->whereIn('user_id', $plantMachineIds);
                $adjustQuery->whereIn('user_id', $plantMachineIds);
            }

            $mouldLogs = $mouldQuery->get(['id', 'user_id', 'created_at', 'end_time']);
            $repairLogs = $repairQuery->get(['id', 'user_id', 'created_at', 'finish_repair']);
            $adjustLogs = $adjustQuery->get(['id', 'user_id', 'created_at', 'end_time']);

            // 4. Initialize 12 Monthly Buckets
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            $monthShortNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
            ];

            $monthlyMetrics = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthlyMetrics[$m] = [
                    'month_number' => $m,
                    'month_name' => $monthNames[$m],
                    'month_short' => $monthShortNames[$m],
                    'target' => 0,
                    'actual' => 0,
                    'achievement_rate' => 0.0,
                    'ng_pcs' => 0,
                    'ng_rate' => 0.0,
                    'status' => 'Belum Berjalan',
                ];
            }

            // Machine tracking
            $machineMap = [];
            // Defect types tracking
            $defectTypeMap = [];

            // 5. Aggregate DailyItemCodes across months and machines
            foreach ($dailyData as $dic) {
                $m = Carbon::parse($dic->start_date)->month;

                // Calculate Target, Actual Output & NG consistently from hourly remarks
                $dicTarget = 0;
                $dicActual = 0;
                $dicNg = 0;
                foreach ($dic->hourlyRemarks as $remark) {
                    $dicTarget += (int) ($remark->target ?? 0);
                    $dicActual += (int) ($remark->actual_production ?? 0);
                    foreach ($remark->ngDetails as $ngDetail) {
                        $qty = (int) ($ngDetail->ng_quantity ?? 0);
                        if ($qty > 0) {
                            $dicNg += $qty;
                            $typeName = $ngDetail->ngType?->ng_type ?? 'Lain-lain';
                            $defectTypeMap[$typeName] = ($defectTypeMap[$typeName] ?? 0) + $qty;
                        }
                    }
                }
                $target = $dicTarget > 0 ? $dicTarget : (int) ($dic->quantity ?? 0);
                $actual = $dicActual;

                // Add to monthly bucket
                if (isset($monthlyMetrics[$m])) {
                    $monthlyMetrics[$m]['target'] += $target;
                    $monthlyMetrics[$m]['actual'] += $actual;
                    $monthlyMetrics[$m]['ng_pcs'] += $dicNg;
                }

                // Add to machine bucket
                $userId = $dic->user_id ?? 0;
                $userName = $dic->user?->name ?? 'Unknown Machine';
                if (! isset($machineMap[$userId])) {
                    $isKarawang = str_starts_with(strtoupper($userName), 'K');
                    $machineMap[$userId] = [
                        'machine_id' => $userId,
                        'machine_name' => $userName,
                        'plant' => $isKarawang ? 'Karawang' : 'KBN',
                        'target' => 0,
                        'actual' => 0,
                        'ng_pcs' => 0,
                        'downtime_minutes' => 0,
                    ];
                }

                $machineMap[$userId]['target'] += $target;
                $machineMap[$userId]['actual'] += $actual;
                $machineMap[$userId]['ng_pcs'] += $dicNg;
            }

            // 6. Incorporate Adjust Logs NG & Downtime
            foreach ($adjustLogs as $adj) {
                $m = Carbon::parse($adj->created_at)->timezone('Asia/Jakarta')->month;
                $userId = $adj->user_id;

                // Adjust NG
                foreach ($adj->ngDetails as $adjNg) {
                    $qty = (int) ($adjNg->ng_quantity ?? 0);
                    if ($qty > 0) {
                        if (isset($monthlyMetrics[$m])) {
                            $monthlyMetrics[$m]['ng_pcs'] += $qty;
                        }
                        if (isset($machineMap[$userId])) {
                            $machineMap[$userId]['ng_pcs'] += $qty;
                        }
                        $typeName = $adjNg->ngType?->ng_type ?? 'NG Adjuster';
                        $defectTypeMap[$typeName] = ($defectTypeMap[$typeName] ?? 0) + $qty;
                    }
                }

                // Adjust Downtime
                if ($adj->created_at && $adj->end_time) {
                    $diffMins = max(0, Carbon::parse($adj->created_at)->diffInMinutes(Carbon::parse($adj->end_time)));
                    if (isset($machineMap[$userId])) {
                        $machineMap[$userId]['downtime_minutes'] += $diffMins;
                    }
                }
            }

            // 7. Incorporate Mould Change & Repair Downtime
            foreach ($mouldLogs as $mLog) {
                if ($mLog->created_at && $mLog->end_time) {
                    $diffMins = max(0, Carbon::parse($mLog->created_at)->diffInMinutes(Carbon::parse($mLog->end_time)));
                    if (isset($machineMap[$mLog->user_id])) {
                        $machineMap[$mLog->user_id]['downtime_minutes'] += $diffMins;
                    }
                }
            }

            foreach ($repairLogs as $rLog) {
                if ($rLog->created_at && $rLog->finish_repair) {
                    $diffMins = max(0, Carbon::parse($rLog->created_at)->diffInMinutes(Carbon::parse($rLog->finish_repair)));
                    if (isset($machineMap[$rLog->user_id])) {
                        $machineMap[$rLog->user_id]['downtime_minutes'] += $diffMins;
                    }
                }
            }

            // 8. Finalize Monthly Calculations & Status
            $currentYear = now()->year;
            $currentMonth = now()->month;
            $totalYearTarget = 0;
            $totalYearActual = 0;
            $totalYearNg = 0;
            $bestMonthActual = 0;
            $bestMonthName = '-';

            foreach ($monthlyMetrics as $m => &$mMetric) {
                $target = $mMetric['target'];
                $actual = $mMetric['actual'];
                $ng = $mMetric['ng_pcs'];

                $totalYearTarget += $target;
                $totalYearActual += $actual;
                $totalYearNg += $ng;

                $achRate = $target > 0 ? round(($actual / $target) * 100, 1) : 0.0;
                $ngRate = ($actual + $ng) > 0 ? round(($ng / ($actual + $ng)) * 100, 2) : 0.0;

                $mMetric['achievement_rate'] = $achRate;
                $mMetric['ng_rate'] = $ngRate;

                // Status Evaluation (>= 90% Tercapai, 80-89.9% Kurang, <80% Kritis)
                if ($year > $currentYear || ($year === $currentYear && $m > $currentMonth)) {
                    $mMetric['status'] = 'Mendatang';
                } elseif ($target === 0 && $actual === 0) {
                    $mMetric['status'] = 'Tidak Ada Jadwal';
                } elseif ($achRate >= 90.0) {
                    $mMetric['status'] = 'Tercapai';
                } elseif ($achRate >= 80.0) {
                    $mMetric['status'] = 'Kurang';
                } else {
                    $mMetric['status'] = 'Kritis';
                }

                if ($actual > $bestMonthActual) {
                    $bestMonthActual = $actual;
                    $bestMonthName = $mMetric['month_name'];
                }
            }
            unset($mMetric);

            // 9. Finalize Machine Performance Matrix
            $machineList = [];
            foreach ($machineMap as $mInfo) {
                $target = $mInfo['target'];
                $actual = $mInfo['actual'];
                $ng = $mInfo['ng_pcs'];
                $achRate = $target > 0 ? round(($actual / $target) * 100, 1) : 0.0;
                $ngRate = ($actual + $ng) > 0 ? round(($ng / ($actual + $ng)) * 100, 2) : 0.0;
                $downtimeHours = round($mInfo['downtime_minutes'] / 60, 1);

                $status = 'Perlu Evaluasi';
                if ($achRate >= 90.0) {
                    $status = 'Optimal';
                } elseif ($achRate >= 80.0) {
                    $status = 'Stabil';
                }

                $machineList[] = [
                    'machine_id' => $mInfo['machine_id'],
                    'machine_name' => $mInfo['machine_name'],
                    'plant' => $mInfo['plant'],
                    'target' => $target,
                    'actual' => $actual,
                    'achievement_rate' => $achRate,
                    'ng_pcs' => $ng,
                    'ng_rate' => $ngRate,
                    'downtime_hours' => $downtimeHours,
                    'status' => $status,
                ];
            }

            // Sort machines by Achievement Rate descending, with Actual Output as tie-breaker
            usort($machineList, function ($a, $b) {
                if ($b['achievement_rate'] != $a['achievement_rate']) {
                    return $b['achievement_rate'] <=> $a['achievement_rate'];
                }

                return $b['actual'] <=> $a['actual'];
            });

            // Top 3 Machines
            $topMachines = array_slice($machineList, 0, 3);

            // Attention Machines (Achievement < 80% with target > 0)
            $attentionMachines = array_values(array_filter($machineList, function ($m) {
                return $m['target'] > 0 && ($m['achievement_rate'] < 80.0 || $m['ng_rate'] > 3.0);
            }));

            // 10. Top 5 Defect Types
            arsort($defectTypeMap);
            $topDefects = [];
            $rank = 1;
            foreach (array_slice($defectTypeMap, 0, 5, true) as $type => $qty) {
                $pct = $totalYearNg > 0 ? round(($qty / $totalYearNg) * 100, 1) : 0.0;
                $topDefects[] = [
                    'rank' => $rank++,
                    'type_name' => $type,
                    'quantity' => $qty,
                    'percentage' => $pct,
                ];
            }

            // 11. Executive Summary Totals
            $overallAchRate = $totalYearTarget > 0 ? round(($totalYearActual / $totalYearTarget) * 100, 1) : 0.0;
            $overallNgRate = ($totalYearActual + $totalYearNg) > 0 ? round(($totalYearNg / ($totalYearActual + $totalYearNg)) * 100, 2) : 0.0;

            $executiveSummary = [
                'year' => $year,
                'plant' => $plant ? strtoupper($plant) : 'SEMUA PLANT (KBN & KARAWANG)',
                'total_target' => $totalYearTarget,
                'total_actual' => $totalYearActual,
                'overall_achievement_rate' => $overallAchRate,
                'total_ng' => $totalYearNg,
                'overall_ng_rate' => $overallNgRate,
                'total_machines' => count($machineList),
                'best_month_name' => $bestMonthName,
                'best_month_output' => $bestMonthActual,
                'health_status' => $overallAchRate >= 90.0 ? 'Baik / Sesuai Target' : ($overallAchRate >= 80.0 ? 'Cukup / Perlu Optimasi' : 'Perlu Perhatian Khusus'),
            ];

            return [
                'summary' => $executiveSummary,
                'monthly_metrics' => array_values($monthlyMetrics),
                'machine_metrics' => $machineList,
                'top_machines' => $topMachines,
                'attention_machines' => $attentionMachines,
                'top_defects' => $topDefects,
            ];
        });
    }
}
