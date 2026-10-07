<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionReport;
use App\Models\ProductionScannedData;
use App\Models\SpkMaster;
use App\Models\Delivery\sapInventoryFg;
use App\Models\User;
use App\Models\MachineJob;
use App\Models\OperatorUser;
use App\Models\DailyItemCode;
use App\Models\MouldChangeLog;
use App\Models\AdjustMachineLog;
use App\Models\MasterZone;
use App\Models\MasterListItem;
use App\Models\ZoneLog;
use App\Models\ZonePengawas;
use App\Models\RepairMachineLog;
use App\Models\HourlyRemark;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ProductionDashboardService;


use App\Services\QualityDataService;


class ProductionDashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('date', Carbon::now()->toDateString());
        $machineName = $request->input('machine_name', '');
        $machineId = User::where('name', $machineName)->pluck('id')->first();

        // Calculate shift range: from 07:30 Jakarta time of selectedDate to 07:30 Jakarta time of next day
        $startDateStr = Carbon::parse($selectedDate . ' 07:30:00', 'Asia/Jakarta')->setTimezone('UTC');
        $endDateStr = Carbon::parse($selectedDate . ' 07:30:00', 'Asia/Jakarta')->addDay()->setTimezone('UTC');

        $machineJobs = MachineJob::with([
            'user',
            'dailyItemCode' => function ($query) use ($selectedDate) {
                $query->where('schedule_date', $selectedDate)->with(['scannedData', 'hourlyRemarks','masterItem','delsched']);
            },
            'mouldChangeLogs' => function ($query) use ($startDateStr, $endDateStr) {
                $query->whereBetween('created_at', [$startDateStr, $endDateStr]);
            },
            'adjustMachineLogs' => function ($query) use ($startDateStr, $endDateStr) {
                $query->whereBetween('created_at', [$startDateStr, $endDateStr]);
            },
            'repairMachineLogs' => function ($query) use ($startDateStr, $endDateStr) {
                $query->whereBetween('created_at', [$startDateStr, $endDateStr]);
            }
        ])
        ->when($machineId, function ($query) use ($machineId) {
            return $query->whereHas('user', function ($query) use ($machineId) {
                $query->where('id', $machineId);
            });
        })
        ->get();

        
        $structuredData = [];

        foreach ($machineJobs as $machineJob) {
            $userName = $machineJob->user->name ?? 'Unknown User';

            $user = $machineJob->user;
            $zoneId = $user->zone_id ?? 'A';
            $zoneData = MasterZone::find($zoneId);

            $pengawas = []; // Initialize as array

            for ($shift = 1; $shift <= 3; $shift++) {
                $latestZoneLog = ZoneLog::where('zone_id', $zoneId)
                    ->where('shift', $shift)
                    ->whereDate('start_date', '<=', $selectedDate)
                    ->whereDate('end_date', '>=', $selectedDate)
                    ->orderByDesc('updated_at')
                    ->first();

                $pengawasName = $latestZoneLog->pengawas ?? 'Unknown';

                $pengawasUser = OperatorUser::where('name', $pengawasName)->first();

                $pengawasProfilePath = $pengawasUser && $pengawasUser->profile_picture
                    ? asset('storage/' . $pengawasUser->profile_picture)
                    : asset('images/default_profile.jpg');

                $pengawas[$shift] = [
                    'name' => $pengawasName,
                    'profile_path' => $pengawasProfilePath,
                    'zone_name' => $zoneData->zone_name ?? 'Unknown',
                ];
            }

            if (!isset($structuredData[$userName])) {
                $structuredData[$userName] = [
                    'pengawas' => $pengawas,
                    'mould_change_log' => [],
                    'adjust_machine_logs' => [],
                    'repair_machine_logs' => [], 
                    'daily_item_code' => [],
                    'hourly_production' => [],
                    'hourly_remarks' => [] // 🔹 Add hourly_remarks array
                ];
            }

            // Process mould change logs
            foreach ($machineJob->mouldChangeLogs as $mouldChange) {
                $setupTimeMinute = 20; // Mould change predicted time is capped/changed to max 20 minutes
                $startTime = Carbon::parse($mouldChange->created_at);
                $endTime = Carbon::parse($mouldChange->end_time);
                $actualTime = $startTime->diffInMinutes($endTime);

                $operatorUser = OperatorUser::where('name', $mouldChange->pic)->first();
                $operatorProfilePath = $operatorUser && $operatorUser->profile_picture 
                    ? asset('storage/' . $operatorUser->profile_picture) 
                    : asset('images/default_profile.jpg');

                $structuredData[$userName]['mould_change_log'][] = [
                    'id' => $mouldChange->id,
                    'machine_name' => $mouldChange->user->name,
                    'item_code' => $mouldChange->item_code,
                    'start_time' => $startTime->format('Y-m-d H:i:s'),
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                    'predicted_time' => $setupTimeMinute,
                    'actual_time' => $actualTime,
                    'pic' => $mouldChange->pic,
                    'pic_profile_path' => $operatorProfilePath,
                    'status' => ($actualTime > $setupTimeMinute) ? 'problem' : 'safe',
                    'remark' => $mouldChange->remark,
                ];
            }

            // Process adjust machine logs
            foreach ($machineJob->adjustMachineLogs as $adjustLog) {
                $setupTimeMinute = $adjustLog->masterListItem->setup_time_minute ?? 0;
                $startTime = Carbon::parse($adjustLog->created_at);
                $endTime = Carbon::parse($adjustLog->end_time);
                $actualTime = $startTime->diffInMinutes($endTime);

                $operatorUser = OperatorUser::where('name', $adjustLog->pic)->first();
                $operatorProfilePath = $operatorUser && $operatorUser->profile_picture 
                    ? asset('storage/' . $operatorUser->profile_picture) 
                    : asset('images/default_profile.jpg');
                
                $structuredData[$userName]['adjust_machine_logs'][] = [
                    'id' => $adjustLog->id,
                    'machine_name' => $adjustLog->user->name,
                    'item_code' => $adjustLog->item_code,
                    'start_time' => $startTime->format('Y-m-d H:i:s'),
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                    'predicted_time' => $setupTimeMinute,
                    'actual_time' => $actualTime,
                    'pic' => $adjustLog->pic,
                    'pic_profile_path' => $operatorProfilePath,
                    'status' => ($actualTime > $setupTimeMinute) ? 'problem' : 'safe',
                    'remark' => $adjustLog->remark,
                ];
            }

             // Process repair machine logs
            foreach ($machineJob->repairMachineLogs as $repairLog) {
                $startTime = Carbon::parse($repairLog->created_at);
                $endTime = $repairLog->finish_repair ? Carbon::parse($repairLog->finish_repair) : null;
                $actualTime = $endTime ? $startTime->diffInMinutes($endTime) : $startTime->diffInMinutes(now());
                
                $operatorUser = OperatorUser::where('name', $repairLog->pic)->first();
                $operatorProfilePath = $operatorUser && $operatorUser->profile_picture 
                    ? asset('storage/' . $operatorUser->profile_picture) 
                    : asset('images/default_profile.jpg');

                $structuredData[$userName]['repair_machine_logs'][] = [
                    'id' => $repairLog->id,
                    'machine_name' => $repairLog->user->name,
                    'start_time' => $startTime->format('Y-m-d H:i:s'),
                    'end_time' => $endTime ? $endTime->format('Y-m-d H:i:s') : null,
                    'problem' => $repairLog->problem,
                    'remark' => $repairLog->remark,
                    'actual_time' => $actualTime,
                    'is_completed' => !is_null($repairLog->finish_repair),
                    'pic' => $repairLog->pic,
                    'pic_profile_path' => $operatorProfilePath,
                    'status' => ($actualTime > 30) ? 'problem' : 'safe',
                ];
            }



            // Process daily item codes
            foreach ($machineJob->dailyItemCode as $dailyItem) {
                // $totalScannedQuantity = collect($dailyItem->scannedData)->sum('quantity');


                $today = now()->startOfDay();
                $fiveDaysLater = now()->addDays(5)->endOfDay();

                $delschedData = $dailyItem->delsched()
                    ->where(function ($query) use ($today, $fiveDaysLater) {
                        $query->whereBetween('delivery_date', [$today->format('Y-m-d'), $fiveDaysLater->format('Y-m-d')])
                            ->orWhere(function ($q) use ($today) {
                                $q->where('status', 'open')
                                    ->where('delivery_date', '<=', $today->format('Y-m-d'));
                            });
                    })
                    ->orderBy('delivery_date', 'asc')
                    ->get();

                $totalScannedQuantity = $dailyItem->hourlyRemarks->sum(function ($remark) {
                    return $remark->actual_production ?? 0;
                });
                
                if ($dailyItem->temporal_cycle_time) {
                    $cycleTimeInSeconds = $dailyItem->temporal_cycle_time;
                } else {
                    $cycleTime = sapInventoryFg::where('item_code', $dailyItem->item_code)->value('cycle_time');
                    $cycleTimeInSeconds = $cycleTime ? $cycleTime * 60 : null;
                }

                $sapCycleTime = MasterListItem::where('item_code', $dailyItem->item_code)
                ->value('cycle_time');

                $formattedDailyItem = [
                    'id' => $dailyItem->id,
                    'item_code' => $dailyItem->item_code,
                    'item_name' => $dailyItem->masterItem?->item_name ?? '',
                    'quantity' => $dailyItem->quantity,
                    'final_quantity' => $dailyItem->final_quantity,
                    'loss_package_quantity' => $dailyItem->loss_package_quantity,
                    'actual_quantity' => $dailyItem->actual_quantity,
                    'shift' => $dailyItem->shift,
                    'remark' => $dailyItem->remark,
                    'start_date' => Carbon::parse($dailyItem->start_date)->format('Y-m-d'),
                    'start_time' => Carbon::parse($dailyItem->start_time)->timezone('Asia/Jakarta')->format('H:i:s'),
                    'end_date' => Carbon::parse($dailyItem->end_date)->format('Y-m-d'),
                    'end_time' => Carbon::parse($dailyItem->end_time)->timezone('Asia/Jakarta')->format('H:i:s'),
                    'total_scanned_quantity' => $totalScannedQuantity,
                    'cycle_time_seconds' => $cycleTimeInSeconds,
                    'sap_cycle_time' => $sapCycleTime,  
                    'scanned_data' => [],
                    'delsched' => $delschedData,
                    'resin_usage' => $dailyItem->resin_usage
                ];

                // Hourly production
                $hourlyProduction = [];

                foreach ($dailyItem->scannedData as $scan) {
                    $hour = Carbon::parse($scan->created_at)->timezone('Asia/Jakarta')->format('H:00');
                    $scanUser = $scan->user ?? 'Unknown';
                
                    $scannedUser = OperatorUser::where('name', $scanUser)->first();
                    $scannedUserProfilePath = $scannedUser && $scannedUser->profile_picture 
                        ? asset('storage/' . $scannedUser->profile_picture) 
                        : asset('images/default_profile.jpg');
                
                    $itemCode = $dailyItem->item_code;

                    if (!isset($hourlyProduction[$hour])) {
                        $hourlyProduction[$hour] = [];
                    }
                    if (!isset($hourlyProduction[$hour][$itemCode])) {
                        $hourlyProduction[$hour][$itemCode] = [];
                    }
                    if (!isset($hourlyProduction[$hour][$itemCode][$scanUser])) {
                        $hourlyProduction[$hour][$itemCode][$scanUser] = [
                            'quantity' => 0,
                            'user_profile_path' => $scannedUserProfilePath
                        ];
                    }
                    $hourlyProduction[$hour][$itemCode][$scanUser]['quantity'] += $scan->quantity;
                
                    $formattedDailyItem['scanned_data'][] = [
                        'id' => $scan->id,
                        'spk_code' => $scan->spk_code,
                        'item_code' => $scan->item_code,
                        'warehouse' => $scan->warehouse,
                        'quantity' => $scan->quantity,
                        'label' => $scan->label,
                        'user' => $scanUser,
                        'user_profile_path' => $scannedUserProfilePath,
                        'scanned_at' => Carbon::parse($scan->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    ];
                }
                
                foreach ($hourlyProduction as $hour => $items) {
                    foreach ($items as $itemCode => $userData) {
                        $structuredData[$userName]['hourly_production'][] = [
                            'hour' => $hour,
                            'item_code' => $itemCode,
                            'users' => $userData
                        ];
                    }
                }
                
                // Process hourly remarks for this daily item
                $allRemarkPics = $dailyItem->hourlyRemarks->flatMap(function ($hr) {
                    return [$hr->pic, $hr->pic_2, $hr->pic_3];
                })->filter(fn($p) => !empty($p) && trim((string)$p) !== '-' && trim((string)$p) !== '0')->unique()->values();
                $opUserMap = OperatorUser::whereIn('name', $allRemarkPics)->get()->keyBy('name');

                foreach ($dailyItem->hourlyRemarks->sortBy('start_time') as $hourlyRemark) {
                    $rawPics = array_values(array_filter([
                        $hourlyRemark->pic,
                        $hourlyRemark->pic_2,
                        $hourlyRemark->pic_3,
                    ], fn($p) => !empty($p) && trim((string)$p) !== '-' && trim((string)$p) !== '0'));

                    $picsList = [];
                    foreach ($rawPics as $pIdx => $pName) {
                        $pUser = $opUserMap->get($pName);
                        $picsList[] = [
                            'name' => $pName,
                            'profile_path' => $pUser && $pUser->profile_picture 
                                ? asset('storage/' . $pUser->profile_picture) 
                                : asset('images/default_profile.jpg'),
                            'role' => 'PIC ' . ($pIdx + 1),
                        ];
                    }

                    if (empty($picsList)) {
                        $defaultUser = $opUserMap->get($hourlyRemark->pic);
                        $picsList[] = [
                            'name' => $hourlyRemark->pic ?: '-',
                            'profile_path' => $defaultUser && $defaultUser->profile_picture 
                                ? asset('storage/' . $defaultUser->profile_picture) 
                                : asset('images/default_profile.jpg'),
                            'role' => 'PIC 1',
                        ];
                    }

                    $operatorProfilePath = $picsList[0]['profile_path'];
                    $primaryPic = $picsList[0]['name'];

                    // Calculate achievement percentage
                    $achievementPercentage = 0;
                    if ($hourlyRemark->target > 0) {
                         $achievementPercentage = round(
                            min(($hourlyRemark->actual_production / $hourlyRemark->target) * 100, 100),
                            2
                        );
                    }

                    $status = $hourlyRemark->is_achieve;

                    // Determine status based on achievement
                    // $status = 'normal';
                    if ($status === 1) {
                        $status = 'achieved';
                    } else {
                        $status = 'Not Achieved';
                    }

                    $structuredData[$userName]['hourly_remarks'][] = [
                        'id' => $hourlyRemark->id,
                        'machine_name' => $userName,
                        'dic_id' => $dailyItem->id,
                        'item_code' => $dailyItem->item_code,
                        'start_time' => Carbon::parse($hourlyRemark->start_time)->format('H:i'),
                        'end_time' => Carbon::parse($hourlyRemark->end_time)->format('H:i'),
                        'time_range' => Carbon::parse($hourlyRemark->start_time)->format('H:i') . ' - ' . Carbon::parse($hourlyRemark->end_time)->format('H:i'),
                        'target' => $hourlyRemark->target,
                        'actual' => $hourlyRemark->actual,
                        'achievement_percentage' => $achievementPercentage,
                        'actual_production' => $hourlyRemark->actual_production,
                        'ng' =>$hourlyRemark->NG,
                        'remark' => $hourlyRemark->remark ?: '-',
                        'is_achieve' => $hourlyRemark->is_achieve,
                        'status' => $status,
                        'shift' => $dailyItem->shift,
                        'pic' => $primaryPic,
                        'pic_2' => $hourlyRemark->pic_2,
                        'pic_3' => $hourlyRemark->pic_3,
                        'pic_profile_path' => $operatorProfilePath,
                        'pics' => $picsList,
                        'pics_count' => count($picsList),
                        'created_at' => Carbon::parse($hourlyRemark->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        'updated_at' => Carbon::parse($hourlyRemark->updated_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                        'ng_details' => $hourlyRemark->ngDetails->map(function ($ng) {
                            return [
                                'id' => $ng->id,
                                'ng_type_id' => $ng->ng_type_id,
                                'ng_type' => $ng->ngType->ng_type ?? 'Unknown',
                                'ng_quantity' => $ng->ng_quantity,
                                'ng_remarks' => $ng->ng_remarks,
                                'created_at' => $ng->created_at?->format('Y-m-d H:i:s'),
                                'updated_at' => $ng->updated_at?->format('Y-m-d H:i:s')
                            ];
                        }),
                    ];
                }

                $structuredData[$userName]['daily_item_code'][] = $formattedDailyItem;
            }


            $structuredData[$userName]['hourly_remarks'] = collect($structuredData[$userName]['hourly_remarks'])
            ->sort(function ($a, $b) {
                // Urutkan berdasarkan shift terlebih dahulu
                if ($a['shift'] !== $b['shift']) {
                    return $a['shift'] <=> $b['shift'];
                }
                
                // Jika shift sama, urutkan secara kronologis berdasarkan start_time
                // Menentukan jam dasar (base hour) per shift untuk menangani wrap-around tengah malam (shift 3)
                $baseHour = 7; // Default Shift 1 (07:30 - 15:30)
                if ($a['shift'] == 2) {
                    $baseHour = 15; // Shift 2 (15:30 - 23:30)
                } elseif ($a['shift'] == 3) {
                    $baseHour = 23; // Shift 3 (23:30 - 07:30)
                }

                $getMinutesFromBase = function ($timeStr, $baseH) {
                    $parts = explode(':', $timeStr);
                    $hour = intval($parts[0] ?? 0);
                    $minute = intval($parts[1] ?? 0);
                    
                    $totalMinutes = $hour * 60 + $minute;
                    $baseMinutes = $baseH * 60;
                    
                    $diff = $totalMinutes - $baseMinutes;
                    if ($diff < 0) {
                        $diff += 1440; // Tambah 24 jam dalam menit jika melewati tengah malam
                    }
                    
                    return $diff;
                };

                $offsetA = $getMinutesFromBase($a['start_time'], $baseHour);
                $offsetB = $getMinutesFromBase($b['start_time'], $baseHour);

                if ($offsetA !== $offsetB) {
                    return $offsetA <=> $offsetB;
                }
                
                // Jika start_time sama, fallback ke created_at
                $createdAtA = \Carbon\Carbon::parse($a['created_at']);
                $createdAtB = \Carbon\Carbon::parse($b['created_at']);
                
                return $createdAtA <=> $createdAtB;
            })
            ->values()
            ->all();


            // 2. ← TARUH DISINI — re-evaluate achievement pakai cycle time
            $itemCodesInRemarks = collect($structuredData[$userName]['hourly_remarks'])
                ->pluck('item_code')->unique()->values()->toArray();

            $cycleTimeMap = MasterListItem::whereIn('item_code', $itemCodesInRemarks)
                ->pluck('cycle_time', 'item_code');

            $groupedByTimeRange = collect($structuredData[$userName]['hourly_remarks'])
                ->groupBy('time_range');

           foreach ($groupedByTimeRange as $timeRange => $remarks) {
                $totalProdSeconds = 0;
                $singleItem       = $remarks->count() === 1;

                foreach ($remarks as $remark) {
                    $cycleTimeSec = $cycleTimeMap->get($remark['item_code']);
                    if ($cycleTimeSec && $cycleTimeSec > 0) {
                        $totalProdSeconds += $remark['actual_production'] * $cycleTimeSec;
                    }
                }

                if ($singleItem) {
                    // Single item — pakai logic: (actual_production) >= target
                    $firstRemark = $remarks->first();
                    $combinedAchieved = ($firstRemark['actual_production'] ?? 0) >= $firstRemark['target'];
                } elseif ($totalProdSeconds > 0) {
                    // Multi item — pakai cycle time
                    $combinedAchieved = $totalProdSeconds >= 3600;
                } else {
                    // Multi item tapi cycle time tidak ada — fallback sum actual vs max target
                    $combinedAchieved = $remarks->sum('actual_production') >= $remarks->max('target');
                }

                foreach ($structuredData[$userName]['hourly_remarks'] as &$remark) {
                    if ($remark['time_range'] === $timeRange) {
                        $remark['combined_actual_seconds'] = round($totalProdSeconds);
                        $remark['combined_achieved']       = $combinedAchieved;
                        $remark['is_multi_item']           = !$singleItem;
                    }
                }
                unset($remark);
            }

            // foreach ($structuredData as $machineName => $machineData) {
            //     $remarks = $machineData['hourly_remarks'] ?? [];

            //     $average = collect($remarks)
            //         ->pluck('achievement_percentage')
            //         ->avg();

            //     $structuredData[$machineName]['average_achievement'] = round($average, 2);
            // }

            foreach ($structuredData as $machineName => $machineData) {
                $remarks = collect($machineData['hourly_remarks'] ?? []);

                // Preload cycle time dari MasterListItem
                $itemCodes    = $remarks->pluck('item_code')->unique()->values()->toArray();
                $cycleTimeMap = MasterListItem::whereIn('item_code', $itemCodes)
                    ->pluck('cycle_time', 'item_code');

                // Preload temporal_cycle_time dari DailyItemCode via dic_id
                $dicIds          = $remarks->pluck('dic_id')->filter()->unique()->values()->toArray();
                $temporalCycleMap = DailyItemCode::whereIn('id', $dicIds)
                    ->pluck('temporal_cycle_time', 'id'); // key = dic_id

                // Group by time_range
                $groupedByHour  = $remarks->groupBy('time_range');
                $totalJamAktif  = $groupedByHour->count();
                $totalProdDetik = 0;

                foreach ($groupedByHour as $timeRange => $hourRemarks) {
                    $jamProdDetik = 0;
                    foreach ($hourRemarks as $remark) {
                        // Prioritas: temporal_cycle_time (dari dic_id) → MasterListItem cycle_time
                        $dicId        = $remark['dic_id'] ?? null;
                        $temporal     = $dicId ? $temporalCycleMap->get($dicId) : null;
                        $cycleTimeSec = ($temporal && $temporal > 0)
                            ? $temporal
                            : $cycleTimeMap->get($remark['item_code']);

                        if ($cycleTimeSec && $cycleTimeSec > 0) {
                            $totalQty = ($remark['actual_production'] ?? 0) + ($remark['ng'] ?? 0);
                            $jamProdDetik += $totalQty * $cycleTimeSec;
                        }
                    }
                    $totalProdDetik += $jamProdDetik;
                }

                // Patokan berdasarkan shift aktif × 8 jam
                $activeShifts = $remarks->pluck('shift')->unique()->count();
                $patokanJam   = $activeShifts * 8;

                // Hitung downtime dari mould change & adjust machine dalam detik
                $mouldChangeMinutes = collect($machineData['mould_change_log'] ?? [])->sum('actual_time');
                $adjustMinutes       = collect($machineData['adjust_machine_logs'] ?? [])->sum('actual_time');
                $setupDowntimeSeconds = ($mouldChangeMinutes + $adjustMinutes) * 60;

                $maxDetik     = max(($patokanJam * 3600) - $setupDowntimeSeconds, 0);
                
                // Capped agar total prod detik tidak melebihi jam aktif kerja mesin yang tersedia
                $cappedProdDetik = min($totalProdDetik, $maxDetik);
                $efficiency = $maxDetik > 0 ? ($cappedProdDetik / $maxDetik) * 100 : 0;

                $structuredData[$machineName]['machine_efficiency']   = round(min($efficiency, 100), 2);
                $structuredData[$machineName]['total_jam_aktif']      = $totalJamAktif;
                $structuredData[$machineName]['patokan_jam']          = $patokanJam;
                $structuredData[$machineName]['total_prod_menit']     = round($cappedProdDetik / 60, 1);
                $structuredData[$machineName]['total_downtime_menit'] = round(($maxDetik - $cappedProdDetik) / 60, 1);
                // dd($totalProdDetik);
                // Daily percentage
                $average = $remarks->avg(function ($remark) {
                    $achieved = $remark['combined_achieved'] ?? $remark['is_achieve'];
                    return $achieved ? 100 : ($remark['achievement_percentage'] ?? 0);
                });

                $structuredData[$machineName]['average_achievement'] = round($average ?? 0, 2);
            }
                    
        }
        

        $machineNames = [
            '0350F',
            '0450F',
            '0450G',
            '0450H',
            '0450I',
            '0450J',
            '0550B',
            '0650D',
            '0650E',
            '0850D',
            '0150E',
            '0360A',
            '0360D',
            '0450B',
            'K2800A',
            'K2100A',
            'K1400A',
            'K1400B',
            'K1400C',
            'K0900A',
            'K0900B',
            'K0650A',
            'K0650B',
            'K0750A',
            'K0750B',
            'K0450A',
        ];
        
        $machines = User::distinct()
            // ->whereIn('id', MachineJob::pluck('user_id'))
            ->whereIn('name', $machineNames)
            ->pluck('name', 'id');
        
        // Fetch all mould changes on the selected date to find the ones > 20 minutes
        $allMouldChanges = MouldChangeLog::whereDate('created_at', $selectedDate)
            ->with(['user', 'masterListItem'])
            ->get();

        $longMouldChanges = [];
        foreach ($allMouldChanges as $mouldChange) {
            $startTime = Carbon::parse($mouldChange->created_at);
            $endTime = Carbon::parse($mouldChange->end_time);
            $actualTime = $startTime->diffInMinutes($endTime);

            if ($actualTime > 20) {
                $operatorUser = OperatorUser::where('name', $mouldChange->pic)->first();
                $operatorProfilePath = $operatorUser && $operatorUser->profile_picture 
                    ? asset('storage/' . $operatorUser->profile_picture) 
                    : asset('images/default_profile.jpg');

                $longMouldChanges[] = [
                    'id' => $mouldChange->id,
                    'machine_name' => $mouldChange->user->name ?? 'Unknown',
                    'item_code' => $mouldChange->item_code,
                    'start_time' => $startTime->format('Y-m-d H:i:s'),
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                    'predicted_time' => 20,
                    'actual_time' => $actualTime,
                    'pic' => $mouldChange->pic,
                    'pic_profile_path' => $operatorProfilePath,
                    'status' => 'problem',
                    'remark' => $mouldChange->remark,
                ];
            }
        }
        usort($longMouldChanges, fn ($a, $b) => $b['actual_time'] <=> $a['actual_time']);

        // Fetch all hourly remarks on the selected date that have actual_production = 0 (or null)
        $allHourlyRemarks = HourlyRemark::whereHas('dailyItemCode', function ($query) use ($selectedDate) {
            $query->where('schedule_date', $selectedDate);
        })
        ->where(function($q) {
            $q->where('actual_production', 0)
              ->orWhereNull('actual_production');
        })
        ->with(['dailyItemCode.user', 'dailyItemCode.masterItem'])
        ->get();

        $zeroActualRemarks = [];
        $allZeroPics = $allHourlyRemarks->flatMap(function ($hr) {
            return [$hr->pic, $hr->pic_2, $hr->pic_3];
        })->filter(fn($p) => !empty($p) && trim((string)$p) !== '-' && trim((string)$p) !== '0')->unique()->values();
        $zeroOpMap = OperatorUser::whereIn('name', $allZeroPics)->get()->keyBy('name');

        foreach ($allHourlyRemarks as $remark) {
            $rawZPics = array_values(array_filter([
                $remark->pic,
                $remark->pic_2,
                $remark->pic_3,
            ], fn($p) => !empty($p) && trim((string)$p) !== '-' && trim((string)$p) !== '0'));

            $zPicsList = [];
            foreach ($rawZPics as $zIdx => $zName) {
                $zUser = $zeroOpMap->get($zName);
                $zPicsList[] = [
                    'name' => $zName,
                    'profile_path' => $zUser && $zUser->profile_picture 
                        ? asset('storage/' . $zUser->profile_picture) 
                        : asset('images/default_profile.jpg'),
                    'role' => 'PIC ' . ($zIdx + 1),
                ];
            }

            if (empty($zPicsList)) {
                $defaultZUser = $zeroOpMap->get($remark->pic);
                $zPicsList[] = [
                    'name' => $remark->pic ?: '-',
                    'profile_path' => $defaultZUser && $defaultZUser->profile_picture 
                        ? asset('storage/' . $defaultZUser->profile_picture) 
                        : asset('images/default_profile.jpg'),
                    'role' => 'PIC 1',
                ];
            }

            $zeroActualRemarks[] = [
                'id' => $remark->id,
                'machine_name' => $remark->dailyItemCode->user->name ?? 'Unknown',
                'item_code' => $remark->dailyItemCode->item_code ?? 'Unknown',
                'item_name' => $remark->dailyItemCode->masterItem->item_name ?? 'Unknown',
                'time_range' => Carbon::parse($remark->start_time)->format('H:i') . ' - ' . Carbon::parse($remark->end_time)->format('H:i'),
                'target' => $remark->target,
                'actual_scan' => $remark->actual,
                'actual_production' => $remark->actual_production ?? 0,
                'ng' => $remark->NG ?? 0,
                'remark' => $remark->remark ?: '-',
                'pic' => $zPicsList[0]['name'],
                'pic_2' => $remark->pic_2,
                'pic_3' => $remark->pic_3,
                'pic_profile_path' => $zPicsList[0]['profile_path'],
                'pics' => $zPicsList,
                'pics_count' => count($zPicsList),
                'shift' => $remark->dailyItemCode->shift ?? '-',
            ];
        }

        // Urutkan berdasarkan shift, lalu nama mesin, lalu jam mulai
        usort($zeroActualRemarks, function ($a, $b) {
            if ($a['shift'] !== $b['shift']) {
                return $a['shift'] <=> $b['shift'];
            }
            if ($a['machine_name'] !== $b['machine_name']) {
                return $a['machine_name'] <=> $b['machine_name'];
            }
            return $a['time_range'] <=> $b['time_range'];
        });

        // dd($structuredData);
        return view('dashboards.dashboard-master-production', compact('structuredData', 'machines', 'selectedDate', 'longMouldChanges', 'zeroActualRemarks'));
    }

    public function getMachinesByItem(Request $request)
    {
        $itemCode = $request->input('item_code');

        $results = DailyItemCode::with('user')
        ->where('item_code', $itemCode)
        ->get()
        ->map(function ($dic) {
            return [
                'machine' => $dic->user->name ?? 'Unknown',
                'date' => Carbon::parse($dic->schedule_date)->format('Y-m-d'),
            ];
        })
        ->unique(fn ($item) => $item['machine'] . '|' . $item['date'])
        ->values(); // ⬅⬅ Ini penting biar bisa pakai forEach() di frontend

        return response()->json($results);
    }

    public function adminView()
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $fourDaysAgo = now()->subDays(4)->toDateString();
        // dd($fourDaysAgo);

        $dailyItemCodes = DailyItemCode::with('user', 'hourlyRemarks', 'scannedData') // Load relasi user
            ->whereIn('start_date', [$yesterday, $today, $tomorrow, $fourDaysAgo])
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        return view('admin.dailyitemcodesindex', compact('dailyItemCodes', 'today', 'yesterday', 'tomorrow', 'fourDaysAgo'));
    }

    public function setStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|in:1,null',
        ]);

        $code = DailyItemCode::findOrFail($id);
        $code->is_done = $request->status === 'null' ? null : 1;
        $code->save();

        return back()->with('status', 'Status updated successfully.');
    }

    public function destroyHourlyRemark($id)
    {
        $hr = HourlyRemark::findOrFail($id);
        $hr->delete();

        return back()->with('success', 'Hourly remark berhasil dihapus.');
    }


}