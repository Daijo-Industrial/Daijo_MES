<?php

namespace App\Http\Controllers;

use App\Events\ParentDataUpdated;
use App\Models\DailyItemCode;
use App\Models\ProductionOutputLog;
use Illuminate\Support\Facades\DB;
use App\Models\File;
use App\Models\MachineJob;
use App\Models\MasterListItem;
use App\Models\Production\PRD_BillOfMaterialChild;
use App\Models\Production\PRD_BillOfMaterialParent;
use App\Models\Production\PRD_MaterialLog;
use App\Models\Production\PRD_MouldingJob;
use App\Models\Production\PRD_MouldingUserLog;
use App\Models\ProductionReport;
use App\Models\ProductionScannedData;
use App\Models\Delivery\sapInventoryFg;
use App\Models\MouldChangeLog;
use App\Models\AdjustMachineLog;
use App\Models\RepairMachineLog;
use App\Models\SpkMaster;
use App\Models\OperatorUser;
use App\Models\User;
use App\Models\HourlyRemark;
use App\Models\ApiLog;

use App\Models\ProductionNgDetail;
use App\Models\ProductionNgType;
use App\Models\DicAccessoryLot;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Milon\Barcode\DNS1D;
use Illuminate\Support\Facades\Auth;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;

class DashboardController extends Controller
{

    // index untuk 3 role , operator , workshop , dan gudang moulding 
    // public function index()
    // {
    //     $user = auth()->user();
    //     if ($user->role->name === 'ADMIN') {
    //         return view('dashboards.dashboard-admin');
    //     } elseif ($user->role->name === 'OPERATOR') {

    //         $hourlyRemarks = HourlyRemark::whereHas('dailyItemCode', function ($query) use ($user) {
    //             $query->where('user_id', $user->id)
    //                   ->whereDate('schedule_date', \Carbon\Carbon::today());
    //         })->with('dailyItemCode.masterItem')->get();

    //         foreach ($hourlyRemarks as $remark) {
    //             $temporal = $remark->dailyItemCode->temporal_cycle_time ?? null;

    //             if (!is_null($temporal) && is_numeric($temporal) && $temporal != 0) {
    //                 $cavity = $remark->dailyItemCode->temporal_cavity 
    //                     ?? ($remark->dailyItemCode->masterItem->cavity ?? 0);
    //                 $cavity = $cavity > 0 ? $cavity : 1;

    //                     $remark->target = floor(3600 / $temporal) * $cavity;
    //             }

    //             if (!is_null($remark->actual_production)) {
    //                 $remark->is_achieve = $remark->actual_production >= $remark->target ? 1 : 0;
    //             } else {
    //                 $remark->is_achieve = 0;
    //             }
        
    //             $remark->save(); 
    //         }


    //         $files = collect();
    //         $machineJobShift = null;
    //         $itemCode = null;
    //         $uniquedata = collect();
    //         $machinejobid = MachineJob::where('user_id', $user->id)->first() ?? null;
            
    //         if(count($uniquedata) > 0){
    //             $dataWithSpkNo = ProductionReport::where('spk_no', $uniquedata[0]['spk'])->first();
    //         } else {
    //             $dataWithSpkNo = null;
    //         }

    //         $machineJobShift = MachineJob::where('user_id', auth()->user()->id)->first()->shift;
    //         $machineJobShift = $machineJob->shift ?? 1;

    //         $zone = $user->zone;

    //         $zonePengawas = $zone?->zoneData()
    //             ->where('shift', $machineJobShift)
    //             ->whereDate('start_date', '<=', now())
    //             ->whereDate('end_date', '>=', now())
    //             ->latest('updated_at')
    //             ->first();

    //         $pengawasName = $zonePengawas?->pengawas;

    //         $pengawasUser = \App\Models\OperatorUser::where('name', $pengawasName)->first();

    //         $pengawasProfile = $pengawasUser?->profile_picture;


    //         $datas = DailyItemCode::where('user_id', $user->id)
    //             ->whereDate('schedule_date', Carbon::today())
    //             ->with('masterItem','scannedData')
    //             ->get()
    //             ->sortBy([
    //                 ['shift', 'asc'],         // Urutkan berdasarkan shift dulu
    //                 ['item_code', 'asc'],     // Lalu urutkan berdasarkan item_code
    //             ]);
        
          
    //          // Ambil data dari MouldChangeLog sesuai dengan user_id dan tanggal hari ini
    //         $mouldChangeLogs = MouldChangeLog::where('user_id',  $user->id)
    //         ->whereDate('created_at', Carbon::today())
    //         ->get();

    //         // Ambil data dari AdjustMachineLog sesuai dengan user_id dan tanggal hari ini
    //         $adjustMachineLogs = AdjustMachineLog::where('user_id', $user->id)
    //                 ->whereDate('created_at', Carbon::today())
    //                 ->get();

    //         // Ambil data dari RepairMachineLog sesuai dengan user_id dan tanggal hari ini
    //         $repairMachineLogs = RepairMachineLog::where('user_id', $user->id)
    //                 ->whereDate('created_at', Carbon::today())
    //                 ->get();

    //          // Tambahkan array total_pengerjaan di setiap data, melewati yang null
    //             $mouldChangeLogs->each(function ($log) {
    //                 // Pastikan created_at dan end_time tidak null
    //                 if ($log->created_at && $log->end_time) {
    //                     $createdAt = Carbon::parse($log->created_at);
    //                     $endTime = Carbon::parse($log->end_time);
    //                     $log->total_pengerjaan = $endTime->diffInMinutes($createdAt); // Hitung selisih waktu dalam menit
    //                 } else {
    //                     $log->total_pengerjaan = null; // Jika null, beri nilai null
    //                 }
    //             });

    //             // Tangani AdjustMachineLogs
    //             $adjustMachineLogs->each(function ($log) {
    //                 // Pastikan created_at dan end_time tidak null
    //                 if ($log->created_at && $log->end_time) {
    //                     $createdAt = Carbon::parse($log->created_at);
    //                     $endTime = Carbon::parse($log->end_time);
    //                      $log->total_pengerjaan = $endTime->diffInMinutes($createdAt); // Hitung selisih waktu dalam menit
    //                 } else {
    //                     $log->total_pengerjaan = null; // Jika null, beri nilai null
    //                 }
    //             });

    //             // Tangani RepairMachineLogs
    //             $repairMachineLogs->each(function ($log) {
    //                 // Pastikan created_at dan finish_repair tidak null
    //                 if ($log->created_at && $log->finish_repair) {
    //                     $createdAt = Carbon::parse($log->created_at);
    //                     $endTime = Carbon::parse($log->finish_repair);
    //                     $log->total_pengerjaan = $endTime->diffInMinutes($createdAt); // Hitung selisih waktu dalam menit
    //                 } else {
    //                     $log->total_pengerjaan = null; // Jika null, beri nilai null
    //                 }
    //             });
            



    //        // Awal proses
    //         $itemCollections = [];
    //         $totalQuantities = [];
    //         $files = [];
    //         $itemCode = $user->jobs->item_code ?? null;
       
    //         // Helper untuk menentukan key utama (gabung ke yang lebih kecil atau yang muncul pertama)
    //         function getMainItemCode($itemCode, $pairCode) {
    //             return $itemCode ?? $pairCode;
    //         }

    //         // Kumpulkan total quantity dan file berdasarkan item utama
    //         foreach ($datas as $data) {
    //             $itemCodeAll = $data->item_code;
    //             $pairCode = $data->masterItem->pair ?? null;

    //             // Tentukan item utama
    //             $mainItemCode = getMainItemCode($itemCodeAll, $pairCode);
                
    //             // Total quantity
    //             if (!isset($totalQuantities[$mainItemCode])) {
    //                 $totalQuantities[$mainItemCode] = 0;
    //             }
    //             $totalQuantities[$mainItemCode] += $data->quantity;

    //             // Simpan file
    //             $fileData = File::where('item_code', $itemCodeAll)->get();
                
        
    //             $files[$mainItemCode] = $fileData->isEmpty() ? collect() : $fileData;
    //         }

    //         // Ambil data scanned dan total quantity per SPK
    //         foreach ($itemCollections as $mainItemCode => &$spkList) {
    //             foreach ($spkList as &$spkData) {
    //                 $spkData['scannedData'] = ProductionScannedData::where('spk_code', $spkData['spk'])
    //                     ->where('item_code', $spkData['item_code'])
    //                     ->count();

    //                 $spkData['totalquantity'] = ProductionScannedData::where('spk_code', $spkData['spk'])
    //                     ->where('item_code', $spkData['item_code'])
    //                     ->sum('quantity');
    //             }
    //         }

    //         // Auto expire jadwal lama yang sudah lewat end_time + 1 jam dan tidak ada aktivitas
    //         $expiredCandidates = DailyItemCode::where('user_id', $user->id)
    //             ->whereNull('is_done')
    //             ->whereDoesntHave('hourlyRemarks')
    //             ->get()
    //             ->filter(function ($dic) {
    //                 // Gabung end_date + end_time, tambah 1 jam
    //                 $endDateTime = Carbon::parse($dic->end_date . ' ' . $dic->end_time)
    //                     ->addHour();
    //                 return Carbon::now('Asia/Jakarta')->gt($endDateTime);
    //             })
    //             ->pluck('id');
    //         // dd($expiredCandidates);

    //         if ($expiredCandidates->isNotEmpty()) {
    //             DailyItemCode::whereIn('id', $expiredCandidates)
    //                 ->update(['is_done' => 99]);
    //         }


    //         $hourlyRemarksActiveDIC = null;
    //         $todayDIC = DailyItemCode::where('user_id', $user->id)
    //             ->whereDate('schedule_date', Carbon::today())
    //             ->where('item_code', $itemCode)
    //             ->with('masterItem', 'scannedData', 'masterFg', 'hourlyRemarks')
    //             ->whereNull('is_done')
    //             ->orderBy('shift')
    //             ->first();

    //         // dd($todayDIC);

    //         $previousDIC = DailyItemCode::where('user_id', $user->id)
    //             ->where('schedule_date', '<', Carbon::today())
    //             ->where('item_code', $itemCode)
    //             ->with('masterItem', 'scannedData', 'masterFg', 'hourlyRemarks')
    //             ->whereNull('is_done')
    //             ->orderByDesc('schedule_date')
    //             ->first();

    //         // Gunakan yang lebih lama dulu (kalau ada), baru hari ini
    //         $activeDIC = $previousDIC ?? $todayDIC;
    //         // dd($activeDIC);
    //         if (!$activeDIC) {
    //                 // Kalau null, cari lagi tanpa filter tanggal
    //             $activeDIC = DailyItemCode::where('user_id', $user->id)
    //                 ->where('item_code', $itemCode)
    //                 ->with(['masterItem','scannedData','masterFg','hourlyRemarks'])
    //                 ->whereNull('is_done')
    //                 ->first();
    //             // dd($activeDIC);
    //         }

    //         if ($activeDIC) {
    //             foreach ($activeDIC->hourlyRemarks as $remark) {
    //                 if (!is_null($remark->actual_production) && $remark->actual_production >= $remark->target) {
    //                     $remark->is_achieve = true;
    //                     $remark->save();
    //                 }
    //             }
            
    //             $hourlyRemarksActiveDIC = $activeDIC->hourlyRemarks;
    //         }

    //         if ($activeDIC) {
    //             $totalScannedQuantity = $activeDIC->scannedData->sum('quantity');
    //             $scannedCount = $activeDIC->scannedData->count();
    //             $hourlyRemarksActiveDIC = HourlyRemark::with('ngDetails', 'ngDetails.ngType')->where('dic_id', $activeDIC->id)
    //                 ->orderBy('start_time')
    //                 ->get();
    //             // dd($hourlyRemarksActiveDIC);
    //         } else {
    //             $totalScannedQuantity = 0;
    //             $scannedCount = 0;
    //         }
    //         // dd($activeDIC);
    //         $activeID = $activeDIC?->id;

    //         $today = Carbon::today();
    //         $tomorrow = Carbon::tomorrow();
    //         $userId = auth()->id();

    //          // Tentukan tanggal shift berdasarkan jam sekarang
    //          $now = Carbon::now('Asia/Jakarta');

    //          if ($now->hour < 7 || ($now->hour == 7 && $now->minute < 30)) {
    //              // Masih dalam shift kemarin
    //              $shiftToday = Carbon::yesterday('Asia/Jakarta')->toDateString();
    //              $shiftTomorrow = Carbon::today('Asia/Jakarta')->toDateString();
    //          } else {
    //              // Shift hari ini
    //              $shiftToday = Carbon::today('Asia/Jakarta')->toDateString();
    //              $shiftTomorrow = Carbon::tomorrow('Asia/Jakarta')->toDateString();
    //          }
 
    //          $hourlyRemarks = HourlyRemark::with('ngDetails', 'ngDetails.ngType')->whereHas('dailyItemCode', function ($query) use ($shiftToday, $shiftTomorrow, $userId) {
    //              $query->where(function ($q) use ($shiftToday, $shiftTomorrow) {
    //                  $q->whereDate('schedule_date', $shiftToday)
    //                  ->orWhereDate('schedule_date', $shiftTomorrow);
    //              })
    //              ->where('user_id', $userId);
    //          })
    //          ->where(function ($q) {
    //              $q->whereBetween('start_time', ['07:30:00', '23:59:59'])
    //              ->orWhereBetween('start_time', ['00:00:00', '07:30:00']);
    //          })
    //          ->orderBy('start_time')
    //          ->get();
       
    //         $spkData = ProductionScannedData::where('dic_id', $activeID)
    //             ->with('summary')
    //             ->get();
            

    //          $todayitems = DailyItemCode::where('user_id', $user->id)
    //             ->whereDate('schedule_date', Carbon::today())
    //             ->whereNull('is_done')
    //             ->orderBy('shift')
    //             ->get();
            
            
    //         $ngData = ProductionNgType::get();
            
    //         $outputLogs = $activeDIC
    //             ? ProductionOutputLog::where('dic_id', $activeID)
    //                 ->orderByDesc('logged_at')
    //                 ->get()
    //             : collect();
            
    //         return view('dashboards.dashboard-operator', compact('files', 'datas', 'itemCode', 'uniquedata', 'machineJobShift', 'dataWithSpkNo', 'machinejobid', 'itemCollections',  'mouldChangeLogs', 'adjustMachineLogs', 'repairMachineLogs','zone','pengawasName','pengawasProfile', 'activeDIC', 'totalScannedQuantity', 'scannedCount', 'hourlyRemarksActiveDIC', 'hourlyRemarks','spkData', 'todayitems','ngData', 'outputLogs'));
    //     } elseif ($user->role->name === 'WORKSHOP') {
    //         return view('dashboards.dashboard-workshop', compact('user'));
    //     } else {
    //         return view('dashboard', compact('user'));
    //     }
    // }

    // public function index()
    // {
    //     $user = auth()->user();
    
    //     // ── Early returns untuk role non-OPERATOR ────────────────────────────────
    //     if ($user->role->name === 'ADMIN') {
    //         return view('dashboards.dashboard-admin');
    //     }
    
    //     if ($user->role->name === 'WORKSHOP') {
    //         return view('dashboards.dashboard-workshop', compact('user'));
    //     }
    
    //     if ($user->role->name !== 'OPERATOR') {
    //         return view('dashboard', compact('user'));
    //     }
    
    //     // ════════════════════════════════════════════════════════════════════════
    //     //  OPERATOR
    //     // ════════════════════════════════════════════════════════════════════════
    
    //     $userId   = $user->id;
    //     $now      = Carbon::now('Asia/Jakarta');
    //     $today    = Carbon::today('Asia/Jakarta');
    //     $itemCode = $user->jobs->item_code ?? null;
    
    //     // ── 1. MACHINE JOB (query sekali, pakai ulang) ───────────────────────────
    //     $machineJob      = MachineJob::where('user_id', $userId)->first();
    //     $machinejobid    = $machineJob;
    //     $machineJobShift = $machineJob->shift ?? 1;
    
    //     // ── 2. SHIFT DATE BOUNDARY ───────────────────────────────────────────────
    //     // Jam 00:00 - 07:29 dianggap masih shift kemarin (shift 3 belum selesai)
    //     $isEarlyMorning = $now->hour < 7 || ($now->hour === 7 && $now->minute < 30);
    //     $shiftToday     = ($isEarlyMorning ? Carbon::yesterday('Asia/Jakarta') : $today)->toDateString();
    //     $shiftTomorrow  = ($isEarlyMorning ? $today : Carbon::tomorrow('Asia/Jakarta'))->toDateString();
    
    //     // ── 3. DAILY ITEM CODES ───────────────────────────────────────────────────
    //     // PENTING: kalau jam 00:00-07:29, DIC shift 3 ada di schedule_date KEMARIN
    //     // Jadi query harus include $shiftToday (bisa kemarin) dan $shiftTomorrow (bisa hari ini)
    //     // agar shift 3 yang masih berjalan tetap muncul
    //     $datas = DailyItemCode::where('user_id', $userId)
    //         ->where(function ($q) use ($shiftToday, $shiftTomorrow) {
    //             $q->whereDate('schedule_date', $shiftToday)
    //             ->orWhereDate('schedule_date', $shiftTomorrow);
    //         })
    //         ->with(['masterItem', 'scannedData', 'masterFg', 'hourlyRemarks.ngDetails.ngType'])
    //         ->get()
    //         ->sortBy([['shift', 'asc'], ['item_code', 'asc']]);
    
    //     // ── 4. AUTO-EXPIRE JADWAL LAMA (dari koleksi, tanpa query baru) ──────────
    //     // Hanya expire DIC yang end_time-nya sudah lewat + 1 jam DAN tidak ada hourlyRemarks
    //     $expiredIds = $datas
    //         ->whereNull('is_done')
    //         ->filter(fn($dic) =>
    //             $dic->hourlyRemarks->isEmpty() &&
    //             Carbon::parse($dic->end_date . ' ' . $dic->end_time)->addHour()->lt($now)
    //         )
    //         ->pluck('id');
    
    //     if ($expiredIds->isNotEmpty()) {
    //         DailyItemCode::whereIn('id', $expiredIds)->update(['is_done' => 99]);
    //         // Update in-memory supaya tidak stale di langkah berikutnya
    //         $datas->each(function ($d) use ($expiredIds) {
    //             if ($expiredIds->contains($d->id)) {
    //                 $d->is_done = 99;
    //             }
    //         });
    //     }
    
    //     // ── 5. UPDATE TARGET & IS_ACHIEVE (bulk, isDirty check) ──────────────────
    //     $todayRemarks = HourlyRemark::whereHas('dailyItemCode', fn($q) =>
    //             $q->where('user_id', $userId)
    //             ->where(function ($q2) use ($shiftToday, $shiftTomorrow) {
    //                 $q2->whereDate('schedule_date', $shiftToday)
    //                     ->orWhereDate('schedule_date', $shiftTomorrow);
    //             })
    //         )
    //         ->with('dailyItemCode.masterItem')
    //         ->get();
    
    //     foreach ($todayRemarks as $remark) {
    //         $dic      = $remark->dailyItemCode;
    //         $temporal = $dic->temporal_cycle_time ?? null;
    
    //         if (!is_null($temporal) && is_numeric($temporal) && $temporal != 0) {
    //             $cavity         = max((int)($dic->temporal_cavity ?? $dic->masterItem->cavity ?? 0), 1);
    //             $remark->target = floor(3600 / $temporal) * $cavity;
    //         }
    
    //         $remark->is_achieve = (!is_null($remark->actual_production) && $remark->actual_production >= $remark->target)
    //             ? 1 : 0;
    
    //         if ($remark->isDirty()) {
    //             $remark->save();
    //         }
    //     }
    
    //     // ── 6. ACTIVE DIC ────────────────────────────────────────────────────────
    //     // Prioritas sama persis dengan kode asli:
    //     // 1) previousDIC: DIC sebelum shiftToday yang belum selesai (termasuk kemarin kalau jam malam)
    //     // 2) todayDIC: DIC di shiftToday/shiftTomorrow yang belum selesai
    //     // 3) Fallback: tanpa filter tanggal
    
    //     $previousDIC = DailyItemCode::where('user_id', $userId)
    //         ->where('item_code', $itemCode)
    //         ->where('schedule_date', '<', $shiftToday)   // pakai $shiftToday bukan $today
    //         ->with(['masterItem', 'scannedData', 'masterFg', 'hourlyRemarks.ngDetails.ngType'])
    //         ->whereNull('is_done')
    //         ->orderByDesc('schedule_date')
    //         ->first();
    
    //     // Cari dari $datas (sudah include shiftToday + shiftTomorrow)
    //     $todayDIC = $datas
    //         ->where('item_code', $itemCode)
    //         ->whereNull('is_done')
    //         ->sortBy('shift')
    //         ->first();
    
    //     // Sama dengan kode asli: previousDIC didahulukan
    //     $activeDIC = $previousDIC ?? $todayDIC;
    
    //     if (!$activeDIC) {
    //         // Fallback terakhir: tanpa filter tanggal sama sekali
    //         $activeDIC = DailyItemCode::where('user_id', $userId)
    //             ->where('item_code', $itemCode)
    //             ->with(['masterItem', 'scannedData', 'masterFg', 'hourlyRemarks.ngDetails.ngType'])
    //             ->whereNull('is_done')
    //             ->first();
    //     }
    
    //     $activeID = $activeDIC?->id;
    
    //     // ── 7. SCANNED DATA & HOURLY REMARKS ACTIVE DIC ──────────────────────────
    //     $hourlyRemarksActiveDIC = null;
    //     $totalScannedQuantity   = 0;
    //     $scannedCount           = 0;
    
    //     if ($activeDIC) {
    //         $totalScannedQuantity = $activeDIC->scannedData->sum('quantity');
    //         $scannedCount         = $activeDIC->scannedData->count();
    
    //         $hourlyRemarksActiveDIC = HourlyRemark::with('ngDetails.ngType')
    //             ->where('dic_id', $activeID)
    //             ->orderBy('start_time')
    //             ->get();
    
    //         // Update is_achieve untuk remark active DIC
    //         foreach ($hourlyRemarksActiveDIC as $remark) {
    //             if (!is_null($remark->actual_production) && $remark->actual_production >= $remark->target) {
    //                 $remark->is_achieve = 1;
    //                 if ($remark->isDirty()) {
    //                     $remark->save();
    //                 }
    //             }
    //         }
    //     }
    
    //     // ── 8. HOURLY REMARKS WINDOW SHIFT ───────────────────────────────────────
    //     $hourlyRemarks = HourlyRemark::with('ngDetails.ngType')
    //         ->whereHas('dailyItemCode', fn($q) =>
    //             $q->where(function ($q2) use ($shiftToday, $shiftTomorrow) {
    //                 $q2->whereDate('schedule_date', $shiftToday)
    //                 ->orWhereDate('schedule_date', $shiftTomorrow);
    //             })
    //             ->where('user_id', $userId)
    //         )
    //         ->where(fn($q) =>
    //             $q->whereBetween('start_time', ['07:30:00', '23:59:59'])
    //             ->orWhereBetween('start_time', ['00:00:00', '07:30:00'])
    //         )
    //         ->orderBy('start_time')
    //         ->get();
    
    //     // ── 9. LOGS (closure reusable) ────────────────────────────────────────────
    //     $calcDuration = function ($log, string $endField = 'end_time') {
    //         $log->total_pengerjaan = ($log->created_at && $log->{$endField})
    //             ? Carbon::parse($log->{$endField})->diffInMinutes(Carbon::parse($log->created_at))
    //             : null;
    //     };
    
    //     // Log tetap pakai $today (tanggal kalender), bukan shift-aware
    //     $mouldChangeLogs   = MouldChangeLog::where('user_id', $userId)->whereDate('created_at', $today)->get();
    //     $adjustMachineLogs = AdjustMachineLog::where('user_id', $userId)->whereDate('created_at', $today)->get();
    //     $repairMachineLogs = RepairMachineLog::where('user_id', $userId)->whereDate('created_at', $today)->get();
    
    //     $mouldChangeLogs->each(fn($log)   => $calcDuration($log, 'end_time'));
    //     $adjustMachineLogs->each(fn($log) => $calcDuration($log, 'end_time'));
    //     $repairMachineLogs->each(fn($log) => $calcDuration($log, 'finish_repair'));
    
    //     // ── 10. FILES & TOTAL QUANTITIES (satu whereIn, bukan per-item query) ────
    //     $files           = [];
    //     $totalQuantities = [];
    //     $itemCollections = [];
    //     $uniquedata      = collect();   // selalu kosong, dipertahankan agar view tidak error
    //     $dataWithSpkNo   = null;
    
    //     $allItemCodes = $datas->pluck('item_code')->unique()->filter()->values();
    //     $allFiles     = File::whereIn('item_code', $allItemCodes)->get()->groupBy('item_code');
    
    //     foreach ($datas as $data) {
    //         $ic       = $data->item_code;
    //         $mainCode = $ic ?? ($data->masterItem->pair ?? $ic);
    
    //         $totalQuantities[$mainCode] = ($totalQuantities[$mainCode] ?? 0) + $data->quantity;
    //         $files[$mainCode]           = $allFiles->get($ic, collect());
    //     }
    
    //     // ── 11. ZONE / PENGAWAS ───────────────────────────────────────────────────
    //     $zone         = $user->zone;
    //     $zonePengawas = $zone?->zoneData()
    //         ->where('shift', $machineJobShift)
    //         ->whereDate('start_date', '<=', $now)
    //         ->whereDate('end_date', '>=', $now)
    //         ->latest('updated_at')
    //         ->first();
    
    //     $pengawasName    = $zonePengawas?->pengawas;
    //     $pengawasUser    = $pengawasName
    //         ? \App\Models\OperatorUser::where('name', $pengawasName)->first()
    //         : null;
    //     $pengawasProfile = $pengawasUser?->profile_picture;
    
    //     // ── 12. MISC ──────────────────────────────────────────────────────────────
    //     $spkData = ProductionScannedData::where('dic_id', $activeID)->with('summary')->get();
    
    //     // todayitems: DIC yang masih aktif dalam window shift sekarang
    //     // (dari $datas yang sudah shift-aware, tanpa query baru)
    //     $todayitems = $datas->whereNull('is_done')->sortBy('shift')->values();
    
    //     $ngData = ProductionNgType::all();

    //      $outputLogs = $activeDIC
    //          ? ProductionOutputLog::where('dic_id', $activeID)
    //              ->orderByDesc('logged_at')
    //              ->get()
    //             : collect();
    
    //     return view('dashboards.dashboard-operator', compact(
    //         'files', 'datas', 'itemCode', 'uniquedata', 'machineJobShift',
    //         'dataWithSpkNo', 'machinejobid', 'itemCollections',
    //         'mouldChangeLogs', 'adjustMachineLogs', 'repairMachineLogs',
    //         'zone', 'pengawasName', 'pengawasProfile',
    //         'activeDIC', 'totalScannedQuantity', 'scannedCount',
    //         'hourlyRemarksActiveDIC', 'hourlyRemarks', 'spkData',
    //         'todayitems', 'ngData', 'outputLogs'
    //     ));
    // }

    public function index()
    {
        $user = auth()->user();

        // ── Early returns untuk role non-OPERATOR ────────────────────────────
        if ($user->hasRole('ADMIN') || $user->hasRole('SUPER-ADMIN')) {
            return view('dashboards.dashboard-admin');
        }
        if ($user->hasRole('WORKSHOP')) {
            return view('dashboards.dashboard-workshop', compact('user'));
        }
        if (!$user->hasRole('OPERATOR')) {
            return view('dashboard', compact('user'));
        }

        // ════════════════════════════════════════════════════════════════════
        // OPERATOR
        // ════════════════════════════════════════════════════════════════════

        $userId   = $user->id;
        $now      = Carbon::now('Asia/Jakarta');
        $today    = Carbon::today('Asia/Jakarta');
        $itemCode = $user->jobs->item_code ?? null;

        // ── 1. MACHINE JOB ───────────────────────────────────────────────────
        $machineJob      = MachineJob::where('user_id', $userId)->first();
        $machinejobid    = $machineJob;
         $machineJobShift = $machineJob->shift ?? 1;

        // ── 2. SHIFT DATE BOUNDARY ───────────────────────────────────────────
        //
        // PENTING: Batas waktu shift 3 → shift 1 TIDAK ditentukan oleh jam.
        // Yang menentukan adalah submit/selesai oleh operator.
        //
        // Namun kita tetap butuh window tanggal untuk query DIC shift 3
        // yang mungkin punya schedule_date = kemarin (misal shift 3 mulai
        // 23:00 kemarin dan selesai 07:00 hari ini).
        //
        // Aturan window:
        //   - Kalau jam 00:00–07:29 → kita anggap "still in overnight window",
        //     artinya DIC shift 3 dengan schedule_date KEMARIN masih relevan.
        //     Tapi kita TIDAK auto-ganti shift / tampilan berdasarkan jam.
        //   - Kalau jam 07:30+ → window normal (hari ini & besok).
        //
        $isOvernightWindow = $now->hour < 7 || ($now->hour === 7 && $now->minute < 30);

        // shiftDateA = tanggal jadwal aktif:
        //   - Sebelum 07:30 → kemarin (shift 3 kemarin masih berjalan)
        //   - Mulai 07:30   → hari ini (jadwal baru dimulai)
        $shiftDateA = $isOvernightWindow
            ? Carbon::yesterday('Asia/Jakarta')->toDateString()
            : $today->toDateString();

        // ── 3. DAILY ITEM CODES ──────────────────────────────────────────────
        // Hanya tampilkan DIC untuk tanggal jadwal aktif ($shiftDateA).
        // Sebelum 07:30 → tampilkan semua shift (1,2,3) dari hari kemarin.
        // Mulai 07:30   → switch ke jadwal hari ini.
        // TIDAK menarik DIC dari tanggal lain meskipun belum selesai.
         $datas = DailyItemCode::where('user_id', $userId)
            ->whereDate('schedule_date', $shiftDateA)
            ->with(['masterItem', 'scannedData', 'masterFg', 'hourlyRemarks.ngDetails.ngType'])
            ->get()
            ->sortBy([['shift', 'asc'], ['item_code', 'asc']]);

        // ── 4. AUTO-EXPIRE JADWAL LAMA ───────────────────────────────────────
        //
        // Expire DIC yang:
        //   a) belum selesai (is_done = null)
        //   b) tidak punya hourlyRemarks sama sekali
        //   c) end_time sudah lewat lebih dari 1 jam
        //
        // TIDAK expire hanya karena jam melewati batas shift — harus lewat
        // end_time + 1 jam DAN tidak ada aktivitas.
        //
        $expiredIds = $datas
            ->whereNull('is_done')
            ->filter(fn ($dic) =>
                $dic->hourlyRemarks->isEmpty()
                && Carbon::parse($dic->end_date . ' ' . $dic->end_time)
                         ->addHour()
                         ->lt($now)
            )
            ->pluck('id');

        if ($expiredIds->isNotEmpty()) {
            DailyItemCode::whereIn('id', $expiredIds)->update(['is_done' => 99]);
            // Update in-memory supaya langkah berikutnya tidak pakai data stale
            $datas->each(function ($d) use ($expiredIds) {
                if ($expiredIds->contains($d->id)) {
                    $d->is_done = 99;
                }
            });
        }

        // ── 5. UPDATE TARGET & IS_ACHIEVE ────────────────────────────────────
        $todayRemarks = HourlyRemark::whereHas('dailyItemCode', fn ($q) =>
            $q->where('user_id', $userId)
              ->whereDate('schedule_date', $shiftDateA)
        )
        ->with('dailyItemCode.masterItem')
        ->get();

        foreach ($todayRemarks as $remark) {
            $dic      = $remark->dailyItemCode;
            $temporal = $dic->temporal_cycle_time ?? null;

            if (!is_null($temporal) && is_numeric($temporal) && $temporal != 0) {
                $cavity        = max((int) ($dic->temporal_cavity ?? $dic->masterItem->cavity ?? 0), 1);
                $remark->target = floor(3600 / $temporal) * $cavity;
            }

            $remark->is_achieve = (!is_null($remark->actual_production)
                && $remark->actual_production >= $remark->target) ? 1 : 0;

            if ($remark->isDirty()) {
                $remark->save();
            }
        }

        // ── 6. ACTIVE DIC ────────────────────────────────────────────────────
        //
        // Prioritas:
        //   1) dic_id dari machine_job (terpilih aktif oleh operator)
        //   2) previousDIC  – DIC sebelum shiftDateA yang belum selesai
        //   3) todayDIC     – DIC dalam window (shiftDateA / shiftDateB) yang belum selesai
        //
        $activeDIC = null;
        if ($machineJob && $machineJob->dic_id) {
            $activeDIC = DailyItemCode::where('id', $machineJob->dic_id)
                ->where('user_id', $userId)
                ->with(['masterItem', 'scannedData', 'masterFg', 'hourlyRemarks.ngDetails.ngType'])
                ->first();

            // Self-healing: Jika DIC dihapus PPIC tapi dic_id masih tertinggal di machine_jobs, reset machine job
            if (!$activeDIC) {
                $machineJob->update([
                    'item_code' => null,
                    'shift' => null,
                    'dic_id' => null,
                ]);
                $itemCode = null;
                $machineJobShift = 1;
            }
        }

        if (!$activeDIC) {
            $previousDIC = DailyItemCode::where('user_id', $userId)
                ->where('item_code', $itemCode)
                ->where('schedule_date', '<', $shiftDateA)
                ->with(['masterItem', 'scannedData', 'masterFg', 'hourlyRemarks.ngDetails.ngType'])
                ->whereNull('is_done')
                ->orderByDesc('schedule_date')
                ->first();

            // Ambil dari $datas (sudah dalam window)
            $todayDIC = $datas
                ->where('item_code', $itemCode)
                ->whereNull('is_done')
                ->sortBy('shift')
                ->first();

            $activeDIC = $previousDIC ?? $todayDIC;

            if (!$activeDIC) {
                $activeDIC = DailyItemCode::where('user_id', $userId)
                    ->where('item_code', $itemCode)
                    ->with(['masterItem', 'scannedData', 'masterFg', 'hourlyRemarks.ngDetails.ngType'])
                    ->whereNull('is_done')
                    ->first();
            }
        }

        $activeID = $activeDIC?->id;

        // ── 7. SCANNED DATA & HOURLY REMARKS ACTIVE DIC ──────────────────────
        $hourlyRemarksActiveDIC = null;
        $totalScannedQuantity   = 0;
        $scannedCount           = 0;

        if ($activeDIC) {
            $totalScannedQuantity = $activeDIC->scannedData->sum('quantity');
            $scannedCount         = $activeDIC->scannedData->count();

            $hourlyRemarksActiveDIC = HourlyRemark::with('ngDetails.ngType')
                ->where('dic_id', $activeID)
                ->orderBy('start_time')
                ->get();

            foreach ($hourlyRemarksActiveDIC as $remark) {
                if (!is_null($remark->actual_production)
                    && $remark->actual_production >= $remark->target
                ) {
                    $remark->is_achieve = 1;
                    if ($remark->isDirty()) {
                        $remark->save();
                    }
                }
            }
        }

        // ── 8. HOURLY REMARKS WINDOW SHIFT ───────────────────────────────────
        // Jika ada active DIC, tampilkan hourly remarks khusus untuk active DIC tersebut.
        // Ini memastikan operator yang masih di shift 3 (kemarin) tetap melihat hourly remarks job-nya
        // meskipun waktu sudah melewati jam 07:30.
        if ($activeDIC) {
            $hourlyRemarks = HourlyRemark::with('ngDetails.ngType')
                ->where('dic_id', $activeID)
                ->orderBy('start_time')
                ->get();
        } else {
            $hourlyRemarks = HourlyRemark::with('ngDetails.ngType')
                ->whereHas('dailyItemCode', fn ($q) =>
                    $q->where('user_id', $userId)
                      ->whereDate('schedule_date', $shiftDateA)
                )
                ->orderBy('start_time')
                ->get();
        }

        // ── 9. LOGS ───────────────────────────────────────────────────────────
        //
        // FIX: Log change mould & adjust machine & repair machine pakai activeShiftDate (shift-aware),
        // bukan Carbon::today() murni atau shiftDateA statis.
        //
        // Jika active DIC terjadwal kemarin (shift 3), maka activeShiftDate = kemarin.
        // Sehingga log shift 3 kemarin tetap muncul selama operator belum submit.
        //
        $activeShiftDate = $activeDIC ? $activeDIC->schedule_date : $shiftDateA;

        $calcDuration = function ($log, string $endField = 'end_time') {
            $log->total_pengerjaan = ($log->created_at && $log->{$endField})
                ? Carbon::parse($log->{$endField})->diffInMinutes(Carbon::parse($log->created_at))
                : null;
        };

        $logStart = Carbon::parse($activeShiftDate, 'Asia/Jakarta')->setTime(7, 30, 0)->utc();
        $logEnd = $logStart->copy()->addDay();

        $mouldChangeLogs = MouldChangeLog::where('user_id', $userId)
            ->where('created_at', '>=', $logStart)
            ->where('created_at', '<', $logEnd)
            ->get();

        $adjustMachineLogs = AdjustMachineLog::where('user_id', $userId)
            ->where('created_at', '>=', $logStart)
            ->where('created_at', '<', $logEnd)
            ->get();

        $repairMachineLogs = RepairMachineLog::where('user_id', $userId)
            ->where('created_at', '>=', $logStart)
            ->where('created_at', '<', $logEnd)
            ->get();

        $mouldChangeLogs->each(fn ($log)   => $calcDuration($log, 'end_time'));
        $adjustMachineLogs->each(fn ($log) => $calcDuration($log, 'end_time'));
        $repairMachineLogs->each(fn ($log) => $calcDuration($log, 'finish_repair'));

        // ── 10. FILES & TOTAL QUANTITIES ─────────────────────────────────────
        $files          = [];
        $totalQuantities = [];
        $itemCollections = [];
        $uniquedata     = collect();   // dipertahankan agar view tidak error
        $dataWithSpkNo  = null;

        $allItemCodes = $datas->pluck('item_code')->unique()->filter()->values();
        $allFiles     = File::whereIn('item_code', $allItemCodes)->get()->groupBy('item_code');

        foreach ($datas as $data) {
            $ic       = $data->item_code;
            $mainCode = $ic ?? ($data->masterItem->pair ?? $ic);

            $totalQuantities[$mainCode] = ($totalQuantities[$mainCode] ?? 0) + $data->quantity;
            $files[$mainCode]           = $allFiles->get($ic, collect());
        }

        // ── 11. ZONE / PENGAWAS ───────────────────────────────────────────────
        $zone = $user->zone;

        $zonePengawas = $zone?->zoneData()
            ->where('shift', $machineJobShift)
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->latest('updated_at')
            ->first();

        $pengawasName    = $zonePengawas?->pengawas;
        $pengawasUser    = $pengawasName
            ? \App\Models\OperatorUser::where('name', $pengawasName)->first()
            : null;
        $pengawasProfile = $pengawasUser?->profile_picture;

        // ── 12. MISC ──────────────────────────────────────────────────────────
        $spkData = ProductionScannedData::where('dic_id', $activeID)->with('summary')->get();

        // todayitems: DIC yang masih aktif dalam window shift
        // (dari $datas yang sudah shift-aware, tanpa query baru)
        $todayitems = $datas->whereNull('is_done')->sortBy('shift')->values();

        // Calculate default next item code for Change Mould and Adjust Machine actions
        $currentItemCode = $itemCode;
        $dailyItemsForNext = DailyItemCode::where('user_id', $userId)
            ->where('schedule_date', $today->toDateString())
            ->orderBy('start_time', 'asc')
            ->pluck('item_code')
            ->toArray();

        $defaultNextItemCode = null;
        $currentIndex = array_search($currentItemCode, $dailyItemsForNext);

        if ($currentIndex !== false && isset($dailyItemsForNext[$currentIndex + 1])) {
            $defaultNextItemCode = $dailyItemsForNext[$currentIndex + 1];
        } else {
            if ($currentIndex === false) {
                $defaultNextItemCode = $dailyItemsForNext[0] ?? null;
            }

            if (!$defaultNextItemCode) {
                $nextDay = Carbon::tomorrow()->format('Y-m-d');
                $defaultNextItemCode = DailyItemCode::where('user_id', $userId)
                    ->where('schedule_date', $nextDay)
                    ->orderBy('start_time', 'asc')
                    ->value('item_code');
            }

            if (!$defaultNextItemCode) {
                $defaultNextItemCode = DailyItemCode::where('user_id', $userId)
                    ->whereNull('is_done')
                    ->orderBy('start_time', 'asc')
                    ->value('item_code');
            }
        }

        $ngData     = ProductionNgType::all();
        $outputLogs = $activeDIC
            ? ProductionOutputLog::where('dic_id', $activeID)
                ->orderByDesc('logged_at')
                ->get()
            : collect();

        $setupMolders = OperatorUser::where('position', 'Setup Mold')->get();

        $karawangAdjusters = [
            'Haerul Anwar',
            'Rudi Siswanto',
            'Rodi Khayrudin',
            'Agung Setyawan',
        ];

        $machineName = strtoupper(trim($user->name ?? ''));
        $isKarawangMachine = str_starts_with($machineName, 'K');

        if ($isKarawangMachine) {
            $adjusters = OperatorUser::whereIn('name', $karawangAdjusters)
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $adjusters = OperatorUser::where('position', 'Adjuster')
                ->whereNotIn('name', $karawangAdjusters)
                ->orderBy('name', 'asc')
                ->get();
        }

        $allOperators = OperatorUser::orderBy('name', 'asc')->get();

        $assignedOperators = [];
        if ($machineJob && !empty($machineJob->employee_name)) {
            $assignedOperators = array_values(array_filter(array_map('trim', explode(',', $machineJob->employee_name))));
        }

        $activeMould = MouldChangeLog::where('user_id', $userId)->whereNull('end_time')->latest()->first();
        $activeAdjust = AdjustMachineLog::where('user_id', $userId)->whereNull('end_time')->latest()->first();
        $activeRepair = RepairMachineLog::where('user_id', $userId)->whereNull('finish_repair')->latest()->first();

        $activeState = 'RUNNING';
        $activeOperatorName = null;
        $activeOperatorProfile = null;
        $activeStateStartTime = null;

        if ($activeMould) {
            $activeState = 'MOULD_CHANGE';
            $activeOperatorName = $activeMould->pic;
            $activeStateStartTime = $activeMould->created_at ? $activeMould->created_at->toIso8601String() : null;
            $opUser = OperatorUser::where('name', $activeMould->pic)->first();
            $activeOperatorProfile = $opUser && $opUser->profile_picture
                ? asset('storage/' . $opUser->profile_picture)
                : asset('images/default_profile.jpg');
        } elseif ($activeAdjust) {
            $activeState = 'ADJUSTING';
            $activeOperatorName = $activeAdjust->pic;
            $activeStateStartTime = $activeAdjust->created_at ? $activeAdjust->created_at->toIso8601String() : null;
            $opUser = OperatorUser::where('name', $activeAdjust->pic)->first();
            $activeOperatorProfile = $opUser && $opUser->profile_picture
                ? asset('storage/' . $opUser->profile_picture)
                : asset('images/default_profile.jpg');
        } elseif ($activeRepair) {
            $activeState = 'REPAIRING';
            $activeOperatorName = $activeRepair->pic;
            $activeStateStartTime = $activeRepair->created_at ? $activeRepair->created_at->toIso8601String() : null;
            $opUser = OperatorUser::where('name', $activeRepair->pic)->first();
            $activeOperatorProfile = $opUser && $opUser->profile_picture
                ? asset('storage/' . $opUser->profile_picture)
                : asset('images/default_profile.jpg');
        }

        $checklistProdDate = \Carbon\Carbon::now('Asia/Jakarta')->subHours(7)->subMinutes(30)->format('Y-m-d');
        $hasMaintenanceChecklistToday = \App\Models\MaintenanceCheckHeader::where('machine_id', $userId)->where('date', $checklistProdDate)->exists();

        return view('dashboards.dashboard-operator', compact(
            'hasMaintenanceChecklistToday',
            'files',
            'datas',
            'itemCode',
            'uniquedata',
            'machineJobShift',
            'dataWithSpkNo',
            'machinejobid',
            'itemCollections',
            'mouldChangeLogs',
            'adjustMachineLogs',
            'repairMachineLogs',
            'zone',
            'pengawasName',
            'pengawasProfile',
            'activeDIC',
            'totalScannedQuantity',
            'scannedCount',
            'hourlyRemarksActiveDIC',
            'hourlyRemarks',
            'spkData',
            'todayitems',
            'ngData',
            'outputLogs',
            'activeState',
            'activeOperatorName',
            'activeOperatorProfile',
            'activeStateStartTime',
            'defaultNextItemCode',
            'setupMolders',
            'adjusters',
            'allOperators',
            'assignedOperators'
        ));
    }


    // function untuk add NG (operator)
    public function addNg(Request $request, $id)
    {
        $request->validate([
            'ng_quantity' => 'required|integer|min:1',
            'ng_type_id'  => 'required|integer',
            'ng_remarks'  => 'nullable|string'
        ]);

        ProductionNgDetail::create([
            'hourly_remark_id' => $id,
            'ng_type_id'       => $request->ng_type_id,
            'ng_quantity'      => $request->ng_quantity,
            'ng_remarks'       => $request->ng_remarks,
        ]);

        // Hitung ulang total NG
        $totalNg = ProductionNgDetail::where('hourly_remark_id', $id)
                                    ->sum('ng_quantity');

        // Update kolom NG di hourly remarks
        HourlyRemark::where('id', $id)
                    ->update(['NG' => $totalNg]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'NG added successfully!'
            ]);
        }

        return back()->with('success', 'NG added successfully!');
    }

    // function untuk show ng detail (operator)
    public function showNgDetail($id)
    {
        try {
            $ng = ProductionNgDetail::with('ngType')->findOrFail($id);
            return response()->json($ng);
        } catch (\Exception $e) {
            return response()->json(['error' => 'NG not found'], 404);
        }
    }

    // function untuk update ng detail (operator)
    public function updateNgDetail(Request $request, $id)
    {
        try {
            $ng = ProductionNgDetail::findOrFail($id);

            $ng->update([
                'ng_type_id'  => $request->ng_type_id,
                'ng_quantity' => $request->ng_quantity,
                'ng_remarks'  => $request->ng_remarks,
            ]);

            // Update total NG di hourly remark
            $ng->hourlyRemark->update([
                'NG' => $ng->hourlyRemark->ngDetails()->sum('ng_quantity')
            ]);

            // Return JSON response bukan back()
            return response()->json([
                'success' => true,
                'message' => 'NG updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update NG'
            ], 500);
        }
    }

    // function untuk delete ng detail (operator)
    public function destroyNgDetail($id)
    {
        try {
            $ng = ProductionNgDetail::findOrFail($id);
            $hourly = $ng->hourlyRemark;

            $ng->delete();

            // Update total
            $hourly->update([
                'NG' => $hourly->ngDetails()->sum('ng_quantity')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'NG deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete NG'
            ], 500);
        }
    }

    // function untuk update remark & operator pic & actual (operator)
    public function updateRemark(Request $request, $id)
    {
        $request->validate([
            'remark'            => 'nullable|string',
            'actual_production' => 'nullable|integer|min:0',
            'pic'               => 'nullable|string',
            'pic_2'             => 'nullable|string',
            'pic_3'             => 'nullable|string',
        ]);

        $remark = HourlyRemark::findOrFail($id);
        if ($request->has('remark')) $remark->remark = $request->remark;
        if ($request->has('actual_production')) $remark->actual_production = $request->actual_production;
        if ($request->has('pic')) $remark->pic = $request->pic;
        if ($request->has('pic_2')) $remark->pic_2 = $request->pic_2;
        if ($request->has('pic_3')) $remark->pic_3 = $request->pic_3;
        $remark->save();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Remark updated successfully']);
        }

        return back()->with('success', 'Remark updated successfully');
    }

    // function untuk update employee name di table machine job (operator)
    public function updateEmployeeName(Request $request)
    {
        $machineJob = MachineJob::where('user_id', auth()->user()->id)->first();

        // Update the employee_name
        $machineJob->employee_name = $request->input('employee_name');
        $machineJob->save();

        // Redirect back or wherever needed
        return redirect()->back()->with('success', 'Employee name updated successfully.');
    }

    // function untuk update item code di table machine job (operator)
    public function updateMachineJob(Request $request)
    {
        // Validate the input
        $request->validate([
            'dic_id' => 'required|integer',
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Get the DailyItemCode ID from the form input
        $dicId = $request->input('dic_id');

        // Find the specific DailyItemCode for the user
        $targetDIC = DailyItemCode::where('id', $dicId)
            ->where('user_id', $user->id)
            ->whereNull('is_done')
            ->first();

        if ($targetDIC) {
            // Find the machine job record related to the user
            $machineJob = MachineJob::where('user_id', $user->id)->first();

            if ($machineJob) {
                // Update the machine job with the new item_code, dic_id, and shift
                $machineJob->item_code = $targetDIC->item_code;
                $machineJob->dic_id = $targetDIC->id;
                $machineJob->shift = $targetDIC->shift;
                $machineJob->save();

                return redirect()->back()->with('success', 'Machine job updated successfully.');
            } else {
                return redirect()->back()->with('error', 'Machine job record not found.');
            }
        } else {
            return redirect()
                ->back()
                ->withErrors(['dic_id' => 'Jadwal kerja tidak ditemukan atau sudah selesai.'])
                ->withInput()
                ->with('error', 'Jadwal kerja tidak ditemukan atau sudah selesai.');
        }
    }

    // UNUSED | function untuk generate barcode berdasarkan spk dan item code yang ada (UNSTABLE)
    public function itemCodeBarcode($item_code, $quantity)
    {
        try {
            // Fetch SPK data for the given item code
            $datas = SpkMaster::where('item_code', $item_code) // atau 'created_at', tergantung nama kolomnya
                ->get();

            if ($datas->isEmpty()) {
                return redirect()->back()->with('error', 'No SPK data found for the given item code.');
            }

            // Fetch master item data
            $masteritem = MasterListItem::where('item_code', $item_code)->first();

            if (!$masteritem) {
                return redirect()->back()->with('error', 'No master item found for the given item code.');
            }

            // Get the standard packaging list value
            $perpack = $masteritem->standart_packaging_list;

            if (!$perpack || $perpack == 0) {
                return redirect()->back()->with('error', 'Standard packaging list (per pack) is invalid or zero.');
            }

            // Calculate the number of labels needed
            $label = (int) ceil($quantity / $perpack);
            $uniquedata = [];
            $previous_spk = null; // Variable to track the previous SPK
            $start_label = null; // Variable to store start_label for each SPK

            $labels = []; // Initialize labels array

            foreach ($datas as $data) {
                $available_quantity = $data->planned_quantity - $data->completed_quantity;

                // Check if the available quantity is sufficient
                if ($available_quantity <= 0) {
                    continue; // Skip this SPK as there's no available quantity
                }

                if ($quantity <= $available_quantity) {
                    $available_quantity = $quantity;
                }

                $deficit = 0;
                if ($data->completed_quantity === 0) {
                    $labelstart = 0;
                } else {
                    $labelstart = ceil($data->completed_quantity / $perpack);
                }

                if ($deficit != 0) {
                    $available_quantity -= $deficit;
                    $deficit = 0;
                }

                while ($available_quantity > 0 && $quantity > 0) {
                    if ($available_quantity >= $perpack && $quantity >= $perpack) {
                        // Assign a full label to this SPK
                        $labelstart++;
                        $labels[] = [
                            'spk' => $data->spk_number,
                            'item_code' => $data->item_code,
                            'item_name' => $masteritem->item_name,
                            'warehouse' => 'FG',
                            'quantity' => $perpack,
                            'label' => $labelstart,
                        ];

                        // Check if SPK has changed
                        if ($previous_spk !== $data->spk_number) {
                            // If SPK has changed, set start_label and reset end_label
                            $start_label = $labelstart;
                            $previous_spk = $data->spk_number;
                        }

                        $key = $data->spk_number . '|' . $data->item_code;
                        if (isset($uniquedata[$key])) {
                            $uniquedata[$key]['count']++;
                            $uniquedata[$key]['end_label'] = $labelstart; // Update end_label as it progresses
                        } else {
                            $uniquedata[$key] = [
                                'spk' => $data->spk_number,
                                'item_code' => $data->item_code,
                                'item_name' => $masteritem->item_name,
                                'count' => 1,
                                'start_label' => $start_label, // Set start_label for this SPK
                                'end_label' => $labelstart, // Initially, end_label is the same as start_label
                            ];
                        }

                        $available_quantity -= $perpack;
                        $quantity -= $perpack;
                    } else {
                        // Assign a partial label to this SPK and move to the next
                        $labelstart++;
                        $labels[] = [
                            'spk' => $data->spk_number,
                            'item_code' => $data->item_code,
                            'item_name' => $masteritem->item_name,
                            'warehouse' => 'FG',
                            'quantity' => $available_quantity, // Use remaining available quantity
                            'label' => $labelstart,
                        ];

                        $key = $data->spk_number . '|' . $data->item_code;
                        if (isset($uniquedata[$key])) {
                            $uniquedata[$key]['count']++;
                            $uniquedata[$key]['end_label'] = $labelstart; // Update end_label for partial labels
                        } else {
                            $uniquedata[$key] = [
                                'spk' => $data->spk_number,
                                'item_code' => $data->item_code,
                                'item_name' => $masteritem->item_name,
                                'count' => 1,
                                'start_label' => $start_label,
                                'end_label' => $labelstart,
                            ];
                        }
                        $deficit = $available_quantity;
                        $quantity -= $available_quantity;
                        $available_quantity = 0;
                    }
                }

                if ($quantity <= 0) {
                    break; // Exit the loop if the required quantity has been processed
                }
            }

            if (empty($labels)) {
                return redirect()->back()->with('error', 'No labels were generated. Please check the available quantity and try again.');
            }

            // Convert uniquedata to array format
            $uniquedata = array_values($uniquedata);

            // Generate barcodes
            $barcodeGenerator = new DNS1D();
            $qrCodeWriter = new PngWriter();
            $barcodes = [];
            $qrcodes = [];
            foreach ($labels as $labelData) {
                // First barcode with all data
                $barcodeData1 = implode("\t", [$labelData['spk'], $labelData['item_code'], $labelData['warehouse'], $labelData['quantity'], $labelData['label']]);
                
                // Second barcode with subset of data
                $barcodeData2 = implode("\t", [$labelData['item_code'], $labelData['warehouse'], $labelData['quantity'], $labelData['label']]);

                //BARCODE SIZE IS 1 , 25

                $barcodes[] = [
                    'first' => $barcodeGenerator->getBarcodeHTML($barcodeData1, 'C128', 1, 50),
                    'second' => $barcodeGenerator->getBarcodeHTML($barcodeData2, 'C128', 1, 55),
                ];
                $qrCodeData = implode("\t", [$labelData['spk'], $labelData['quantity'], $labelData['warehouse'], $labelData['label']]);
                // dd($qrCodeData);
                $qrCode = new QrCode(data: $qrCodeData, errorCorrectionLevel: ErrorCorrectionLevel::Medium, size: 70,
                margin: 5);
              
                
    
                // Create the QR code image with PngWriter
                $writer = new PngWriter();
                $qrCodeResult = $writer->write($qrCode);
              
                // Get the PNG image as a string
                $qrCodeImage = $qrCodeResult->getString();
                
                // Base64 encode the image to embed in HTML
                $qrcodes[] = base64_encode($qrCodeImage);

            }

            return view('barcodeMachineJob', compact('labels', 'barcodes', 'qrcodes'));
        } catch (\Exception $e) {
            // Optionally log the error
            // Log::error('Error generating barcodes: ' . $e->getMessage());

            // Return error message to the user
            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    // function untuk proses barcode produk per box (operator)
    public function procesProductionBarcodes(Request $request)
    {
        $datas = json_decode($request->input('datas'), true);
        $uniquedata = json_decode($request->input('uniqueData'));
        $activeDIC = json_decode($request->input('activedic'));
        // dd($activeDIC);

        $spk_code = $request->input('spk_code_auto');
        $quantity = $request->input('quantity_auto');
        $warehouse = $request->input('warehouse_auto');
        $label = $request->input('label_auto');
        $user = $request->input('nik') ?? session('verifiedNIK');
        $now = Carbon::now('Asia/Jakarta');

        $loggedInUserId = auth()->user()->id;

        // Ambil MachineJob aktif milik user/mesin yang sedang login
        $machineJob = MachineJob::where('user_id', $loggedInUserId)->first();
        $activeDicId = $machineJob?->dic_id ?? null;

        // Cari DailyItemCode berdasarkan dic_id tersebut
        $resolvedDIC = null;
        if ($activeDicId) {
            $resolvedDIC = DailyItemCode::where('id', $activeDicId)
                ->where('user_id', $loggedInUserId)
                ->first();
        }

        // Jika tidak ditemukan dic_id di machine_job (misal operator belum meng-assign job tapi langsung scan),
        // maka fallback mencari DIC aktif yang belum selesai hari ini
        if (!$resolvedDIC) {
            $isOvernightWindow = $now->hour < 7 || ($now->hour === 7 && $now->minute < 30);
            $shiftDateA = $isOvernightWindow
                ? Carbon::yesterday('Asia/Jakarta')->toDateString()
                : Carbon::today('Asia/Jakarta')->toDateString();
            $shiftDateB = Carbon::parse($shiftDateA)->addDay()->toDateString();

            $resolvedDIC = DailyItemCode::where('user_id', $loggedInUserId)
                ->whereIn('schedule_date', [$shiftDateA, $shiftDateB])
                ->whereNull('is_done')
                ->first();
        }

        if ($resolvedDIC) {
            $activeDIC = $resolvedDIC;
        } else {
            $errorMessage = 'Error: Tidak ada jadwal produksi (DailyItemCode) aktif yang ditugaskan ke mesin Anda (' . auth()->user()->name . '). Harap pilih Pekerjaan terlebih dahulu di Dashboard.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 422);
            }
            return redirect()->back()->withErrors(['error' => $errorMessage]);
        }

        // ✅ Perbaikan logika slot waktu
        if ($now->lt(Carbon::createFromTime(7, 30, 0, 'Asia/Jakarta'))) {
            $base = Carbon::yesterday('Asia/Jakarta')->setTime(7, 30, 0);
        } else {
            $base = Carbon::today('Asia/Jakarta')->setTime(7, 30, 0);
        }

        $diffMinutes = $base->diffInMinutes($now);
        $slotIndex = floor($diffMinutes / 60);
        $startTime = $base->copy()->addMinutes($slotIndex * 60)->format('H:i:s');
        $endTime = $base->copy()->addMinutes(($slotIndex + 1) * 60)->format('H:i:s');

        $cycletime = 0;
        if (!empty($activeDIC->temporal_cycle_time)) {
            $cycletime = $activeDIC->temporal_cycle_time; // sudah dalam detik
        } elseif (!empty($activeDIC->master_fg?->cycle_time)) {
            $cycletime = $activeDIC->master_fg->cycle_time * 60; // dari menit ke detik
        }
        
        if ($cycletime > 0) {
            $target = floor(3600 / $cycletime); // 1 jam = 3600 detik
        } else {
            $target = 0;
        }

        if (
            !empty($activeDIC->master_item?->pair) || 
            !empty($activeDIC->master_fg?->pair)
        ) {
            $target *= 2;
        }

        // if ($activeDIC->master_item?->cavity == 2) {
        //     $target *= 2;
        // }

        // Validasi dasar inputan
        $request->validate([
            'spk_code_auto' => 'required|string',
            'warehouse_auto' => 'required|string',
            'quantity_auto' => 'required|integer',
            'label_auto' => 'required|string',
        ]);

        $rawLabel = trim((string) $request->input('label_auto'));
        $spkCodeClean = trim((string) $request->input('spk_code_auto'));
        $originalRawLabel = $rawLabel;
        $wasRecovered = false;

        // 1. Auto-recovery: Jika nomor label tertempel dengan kode SPK di belakangnya (misal '30026026744' dari label 300 + SPK 26026744 karena scan terlalu cepat)
        if (!empty($spkCodeClean) && str_ends_with($rawLabel, $spkCodeClean) && strlen($rawLabel) > strlen($spkCodeClean)) {
            $candidateLabel = substr($rawLabel, 0, -strlen($spkCodeClean));
            if (is_numeric($candidateLabel) && (int) $candidateLabel > 0) {
                $rawLabel = (string) (int) $candidateLabel;
                $wasRecovered = true;
            }
        }

        // 2. Proteksi Stutter / Repetisi: Jika ada pengulangan digit >= 5 kali (misal '36666666666666666')
        if (preg_match('/(\d)\1{4,}/', $rawLabel)) {
            $errMsg = "Nomor label '{$rawLabel}' tidak valid karena scanner mengalami stutter / tombol macet. Harap periksa scanner dan scan ulang.";
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'status' => 'stutter',
                    'label' => $rawLabel,
                    'message' => $errMsg
                ], 422);
            }
            return redirect()->back()->withErrors(['error' => $errMsg]);
        }

        // 3. Validasi batas wajar: nomor urut label box harus berupa angka 1 s/d 6 digit (1 - 999999)
        if (!preg_match('/^[1-9]\d{0,5}$/', $rawLabel)) {
            $errMsg = "Format nomor label '{$rawLabel}' tidak valid. Nomor label harus berupa 1 s/d 6 digit angka.";
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'status' => 'invalid_format',
                    'label' => $rawLabel,
                    'message' => $errMsg
                ], 422);
            }
            return redirect()->back()->withErrors(['error' => $errMsg]);
        }

        $label = $rawLabel;

        $dicId = $activeDIC->id;

        $hourlyRemark = HourlyRemark::where('dic_id', $dicId)
            ->where('start_time', $startTime)
            ->where('end_time', $endTime)
            ->first();

        $existingSpk = SpkMaster::where('spk_number', $spk_code)->first();

        if (!$existingSpk) {
            $errMsg = "Kode SPK '{$spk_code}' (Label #{$label}) tidak ditemukan dalam master SPK.";
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'status' => 'spk_not_found',
                    'label' => $label,
                    'spk_code' => $spk_code,
                    'message' => $errMsg
                ], 422);
            }
            return redirect()->back()->withErrors(['error' => $errMsg]);
        }

        try {
            DB::beginTransaction();

            $existingScan = ProductionScannedData::where('spk_code', $spk_code)
                ->where('label', $label)
                ->lockForUpdate()
                ->first();

            if ($existingScan) {
                DB::rollBack();
                $errMsg = "Label #{$label} pada SPK {$spk_code} sudah pernah discan sebelumnya!";
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'status' => 'duplicate',
                        'label' => $label,
                        'spk_code' => $spk_code,
                        'message' => $errMsg
                    ], 422);
                }
                return redirect()->back()->withErrors(['error' => $errMsg]);
            }

            $trueItemcode = SpkMaster::where('spk_number', $spk_code)->first()?->item_code ?? $activeDIC->item_code;

            ProductionScannedData::create([
                'spk_code' => $spk_code,
                'dic_id' => $dicId,
                'item_code' => $trueItemcode,
                'quantity' => $quantity,
                'warehouse' => $warehouse,
                'label' => $label,
                'user' => $user,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'label' => $label,
                    'message' => 'Gagal memproses scan barcode: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->withErrors(['error' => 'Gagal memproses scan barcode: ' . $e->getMessage()]);
        }

        // ✅ PERBAIKAN: Hitung total actual dengan datetime range yang tepat
        $slotStart = $base->copy()->addMinutes($slotIndex * 60);
        $slotEnd = $base->copy()->addMinutes(($slotIndex + 1) * 60);
        
        // Jika endTime lebih kecil dari startTime, berarti slot melewati midnight
        if ($endTime < $startTime) {
            // Contoh: startTime = 23:30:00, endTime = 00:30:00
            // Query split jadi 2 bagian: 23:30-23:59 dan 00:00-00:30
            $totalActual = ProductionScannedData::where('dic_id', $dicId)
                ->where(function($query) use ($startTime, $endTime) {
                    $query->whereRaw("TIME(CONVERT_TZ(created_at, '+00:00', '+07:00')) >= ?", [$startTime])
                        ->whereRaw("TIME(CONVERT_TZ(created_at, '+00:00', '+07:00')) <= '23:59:59'");
                })
                ->orWhere(function($query) use ($dicId, $endTime) {
                    $query->where('dic_id', $dicId)
                        ->whereRaw("TIME(CONVERT_TZ(created_at, '+00:00', '+07:00')) >= '00:00:00'")
                        ->whereRaw("TIME(CONVERT_TZ(created_at, '+00:00', '+07:00')) < ?", [$endTime]);
                })
                ->sum('quantity');
        } else {
            // Slot normal dalam satu hari
            $totalActual = ProductionScannedData::where('dic_id', $dicId)
                ->whereRaw("TIME(CONVERT_TZ(created_at, '+00:00', '+07:00')) >= ?", [$startTime])
                ->whereRaw("TIME(CONVERT_TZ(created_at, '+00:00', '+07:00')) < ?", [$endTime])
                ->sum('quantity');
        }

        $pic2 = $request->input('pic_2');
        $pic3 = $request->input('pic_3');

        $isAchieve = 0;

        if ($hourlyRemark) {
            $updateData = [
                'actual' => $totalActual,
                'is_achieve' => $isAchieve,
                'updated_at' => now(),
            ];
            if ($pic2 !== null) $updateData['pic_2'] = $pic2;
            if ($pic3 !== null) $updateData['pic_3'] = $pic3;
            $hourlyRemark->update($updateData);
        } else {
            HourlyRemark::create([
                'dic_id' => $dicId,
                'start_time' => $startTime,
                'end_time' => $endTime, 
                'target' => $target,
                'actual' => $totalActual,
                'is_achieve' => $isAchieve,
                'pic' => $user,
                'pic_2' => $pic2,
                'pic_3' => $pic3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $successMsg = $wasRecovered
            ? "Label #{$label} berhasil dicatat (Otomatis dikoreksi dari '{$originalRawLabel}')."
            : "Label #{$label} berhasil dicatat.";

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $wasRecovered ? 'recovered' : 'success',
                'was_recovered' => $wasRecovered,
                'original_label' => $originalRawLabel,
                'label' => $label,
                'spk_code' => $spk_code,
                'quantity' => $quantity,
                'item_code' => $trueItemcode,
                'time' => now('Asia/Jakarta')->format('H:i:s'),
                'message' => $successMsg
            ]);
        }

        return redirect()->route('dashboard')->with('deactivateScanMode', false);
    }

    // function untuk submit spk jika shift sudah selesai (operator)
    public function submitSPK(Request $request)
    {
        $all = $request->all(); // atau dd($request->all());
        $datas = json_decode($request->input('datas'), true);
        $uniquedata = json_decode($request->input('uniqueData'));
        $dic = json_decode($request->input('activedic'));
        $loggedInUserId = auth()->user()->id;

        // Validasi: pastikan DIC yang disubmit adalah milik mesin yang sedang login (auth()->user()) dan belum selesai (is_done is null)
        if (!$dic || $dic->user_id != $loggedInUserId || !is_null($dic->is_done)) {
            $now = Carbon::now('Asia/Jakarta');
            $isOvernightWindow = $now->hour < 7 || ($now->hour === 7 && $now->minute < 30);
            $shiftDateA = $isOvernightWindow
                ? Carbon::yesterday('Asia/Jakarta')->toDateString()
                : Carbon::today('Asia/Jakarta')->toDateString();
            $shiftDateB = Carbon::parse($shiftDateA)->addDay()->toDateString();

            // Cari DIC aktif milik user yang login untuk mengamankan data (hanya pada rentang tanggal jadwal hari ini/besok)
            $resolvedDIC = DailyItemCode::where('user_id', $loggedInUserId)
                ->whereIn('schedule_date', [$shiftDateA, $shiftDateB])
                ->whereNull('is_done')
                ->first();
                
            if (!$resolvedDIC) {
                $resolvedDIC = DailyItemCode::where('user_id', $loggedInUserId)
                    ->whereIn('schedule_date', [$shiftDateA, $shiftDateB])
                    ->orderBy('schedule_date', 'desc')
                    ->first();
            }

            if ($resolvedDIC) {
                $dic = $resolvedDIC;
            } else {
                return redirect()->back()->withErrors(['error' => 'Error: Jadwal aktif untuk mesin Anda (' . auth()->user()->name . ') pada tanggal hari ini tidak ditemukan. Tidak dapat melakukan submit.']);
            }
        }
        $dicId = $dic->id;

        // Catatan: completed_quantity di spk_masters tidak di-increment manual di sini,
        // melainkan tersinkronisasi murni dari SAP (Receipt Production / SAP Production Order Sync).

        // Update status DIC jadi done
        DailyItemCode::where('id', $dicId)->update(['is_done' => 1]);

        $machineJob = MachineJob::where('user_id', auth()->user()->id)->first();
        if ($machineJob) {
            $machineJob->update([
                'item_code' => null,
                'shift' => null,
                'dic_id' => null,
            ]);
        }

        return redirect()->back()->with('success', 'Shift SPK berhasil diselesaikan.');
    }

    // function untuk reset jobs manual (operator)
    public function resetJobs(Request $request)
    {
        $uniquedata = json_decode($request->input('uniqueData'), true);
        $datas = json_decode($request->input('datas'));

        $reportUpdated = false;
        $updatedSpkNo = null;

        if (!empty($uniquedata)) {
            foreach ($uniquedata as $uniquedatum) {
                $targetQuantity = $uniquedatum['count'] ?? 0;
                $actualProductionQuantity = $uniquedatum['scannedData'] ?? 0;

                if ($actualProductionQuantity < $targetQuantity) {
                    $dataSendToPpic = [
                        'machine_id' => auth()->user()->id,
                        'spk_no' => $uniquedatum['spk'],
                        'target' => $targetQuantity,
                        'scanned' => $actualProductionQuantity,
                        'outstanding' => $targetQuantity - $actualProductionQuantity,
                    ];

                    $dataWithSpkNo = ProductionReport::where('spk_no', $uniquedatum['spk'])->first();
                    if ($dataWithSpkNo) {
                        $dataWithSpkNo->update($dataSendToPpic);
                        $reportUpdated = true;
                        $updatedSpkNo = $dataWithSpkNo->spk_no;
                    } else {
                        ProductionReport::create($dataSendToPpic);
                        // Send mail notification
                        $ppicUser = User::where('name', 'budiman')->first();
                        if ($ppicUser) {
                            $ppicUser->notify(new \App\Notifications\ProductionReportCreated($dataSendToPpic));
                        }
                    }
                }
            }
        }

        // Reset the machine job
        $machineJob = MachineJob::where('user_id', auth()->user()->id)->first();
        if ($machineJob) {
            $machineJob->update([
                'item_code' => null,
                'shift' => null,
                'dic_id' => null,
            ]);
        }

        if ($reportUpdated) {
            return redirect()
                ->back()
                ->with([
                    'success' => "Successfully updating spk number " . $updatedSpkNo,
                    'deactivateScanMode' => true,
                ]);
        }

        return redirect()
            ->back()
            ->with([
                'success' => 'Data sent to PPIC!',
                'deactivateScanMode' => true, // Add this flag
            ]);

        // Find all jobs related to the user
        $jobs = MachineJob::where('user_id', auth()->user()->id)->get();

        // Loop through the jobs and reset the item_code (or other relevant fields)
        foreach ($jobs as $job) {
            $job->item_code = null; // Or any default value you'd like
            $job->save(); // Save the changes to the database
        }
        // Optionally return a message or redirect the user
        return redirect()->back()->with('success', 'Jobs have been reset successfully.');
    }


    
    public function dashboardPlastic()
    {
        $datas = DailyItemCode::with('machinerelation', 'user', 'scannedData')->get();
        //    dd($datas);

        return view('dashboard_plasticinjection', compact('datas'));
    }


    public function startMouldChange(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::now()->format('Y-m-d');

        $request->validate([
            'pic_name' => 'required|string|max:255',
            'item_code' => 'nullable|string|max:255',
        ]);

        $currentItemCode = MachineJob::where('user_id', $userId)->value('item_code');
        $operatorUser = OperatorUser::where('name', $request->pic_name)->first();

        $nextItemCode = $request->item_code;

        if (!$nextItemCode) {
            // Ambil semua item hari ini (pakai schedule_date karena beda field)
            $dailyItems = DailyItemCode::where('user_id', $userId)
                ->whereDate('schedule_date', $today)
                ->orderBy('start_time', 'asc')
                ->pluck('item_code')
                ->toArray();

            $currentIndex = array_search($currentItemCode, $dailyItems);

            if ($currentIndex !== false && isset($dailyItems[$currentIndex + 1])) {
            // Masih ada item berikutnya hari ini
            $nextItemCode = $dailyItems[$currentIndex + 1];
        } else {
            // Kalau current item gak ada di hari ini atau sudah di akhir
            if ($currentIndex === false) {
                // Coba ambil item pertama hari ini
                $nextItemCode = $dailyItems[0] ?? null;
            }

            // Kalau tetap null, coba ambil item pertama besok
            if (!$nextItemCode) {
                $nextDay = Carbon::tomorrow()->format('Y-m-d');
                $nextDayItem = DailyItemCode::where('user_id', $userId)
                    ->whereDate('schedule_date', $nextDay)
                    ->orderBy('start_time', 'asc')
                    ->value('item_code');
                $nextItemCode = $nextDayItem ?? null;
            }

            // 🔥 Fallback terakhir: cari item yang belum selesai (is_done = null)
            if (!$nextItemCode) {
                $undoneItem = DailyItemCode::where('user_id', $userId)
                    ->whereNull('is_done')
                    ->orderBy('start_time', 'asc')
                    ->value('item_code');
                $nextItemCode = $undoneItem ?? null;
            }
        }
        }

        // Kalau tetap gak ada, berarti belum ada job yang bisa diassign
        if (!$nextItemCode) {
            return response()->json(['message' => 'Belum ada item yang diassign atau belum ada item yang belum selesai.']);
        }

        // Guard: cek apakah sudah ada mould change aktif (belum selesai)
        $activeMouldChange = MouldChangeLog::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();

        if ($activeMouldChange) {
            return response()->json([
                'message' => 'Mould change sudah berjalan',
                'log_id' => $activeMouldChange->id,
                'operator' => [
                    'name' => $operatorUser ? $operatorUser->name : $activeMouldChange->pic,
                    'profile_path' => $operatorUser && $operatorUser->profile_picture
                        ? asset('storage/' . $operatorUser->profile_picture)
                        : asset('images/default_profile.jpg'),
                ],
            ]);
        }

        // Buat log mould change baru
        $mouldChange = MouldChangeLog::create([
            'user_id' => $userId,
            'pic' => $request->pic_name,
            'item_code' => $nextItemCode,
            'created_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Mould change started', 'log_id' => $mouldChange->id, 'operator' => [
            'name' => $operatorUser->name,
            'profile_path' => $operatorUser->profile_picture
                ? asset('storage/' . $operatorUser->profile_picture)
                : asset('images/default_profile.jpg'),
        ]]);
    }

    public function startAdjustMachine(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::now()->format('Y-m-d');

        $request->validate([
            'pic_name' => 'required|string|max:255',
            'item_code' => 'nullable|string|max:255',
        ]);

        $karawangAdjusters = [
            'Haerul Anwar',
            'Rudi Siswanto',
            'Rodi Khayrudin',
            'Agung Setyawan',
        ];

        $user = Auth::user();
        $machineName = strtoupper(trim($user->name ?? ''));
        $isKarawangMachine = str_starts_with($machineName, 'K');

        if ($isKarawangMachine && !in_array($request->pic_name, $karawangAdjusters)) {
            return response()->json([
                'error' => 'Mesin Karawang (' . ($user->name ?? 'K') . ') hanya boleh di-adjust oleh adjuster Karawang: ' . implode(', ', $karawangAdjusters) . '.'
            ], 422);
        }

        if (!$isKarawangMachine && in_array($request->pic_name, $karawangAdjusters)) {
            return response()->json([
                'error' => 'Adjuster Karawang (' . $request->pic_name . ') tidak diizinkan melakukan adjust pada mesin KBN (' . ($user->name ?? 'KBN') . ').'
            ], 422);
        }

        $currentItemCode = MachineJob::where('user_id', $userId)->value('item_code');
        $operatorUser = OperatorUser::where('name', $request->pic_name)->first();

        $nextItemCode = $request->item_code;

        if (!$nextItemCode) {
            if ($currentItemCode) {
                $nextItemCode = $currentItemCode;
            } else {
                // Ambil daftar item hari ini
                $dailyItems = DailyItemCode::where('user_id', $userId)
                    ->whereDate('start_date', $today)
                    ->orderBy('start_time', 'asc')
                    ->pluck('item_code')
                    ->toArray();

                $currentIndex = array_search($currentItemCode, $dailyItems);

                if ($currentIndex !== false && isset($dailyItems[$currentIndex + 1])) {
                    // Masih ada item berikutnya di hari ini
                    $nextItemCode = $dailyItems[$currentIndex + 1];
                } else {
                    // Kalau current item gak ada di hari ini atau sudah di akhir
                    if ($currentIndex === false) {
                        // Coba ambil item pertama hari ini
                        $nextItemCode = $dailyItems[0] ?? null;
                    }

                    // Kalau tetap null, ambil item pertama besok
                    if (!$nextItemCode) {
                        $nextDay = Carbon::tomorrow()->format('Y-m-d');
                        $nextDayItem = DailyItemCode::where('user_id', $userId)
                            ->whereDate('start_date', $nextDay)
                            ->orderBy('start_time', 'asc')
                            ->value('item_code');
                        $nextItemCode = $nextDayItem ?? null;
                    }

                    // 🔥 Fallback terakhir: ambil item_code yang belum selesai (is_done null)
                    if (!$nextItemCode) {
                        $undoneItem = DailyItemCode::where('user_id', $userId)
                            ->whereNull('is_done')
                            ->orderBy('start_time', 'asc')
                            ->value('item_code');

                        $nextItemCode = $undoneItem ?? null;
                    }
                }
            }
        }

        if (!$nextItemCode) {
            return response()->json(['message' => 'Belum ada item yang diassign atau belum ada item yang belum selesai.']);
        }

        // Guard: cek apakah sudah ada adjust machine aktif (belum selesai)
        $activeAdjustMachine = AdjustMachineLog::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();

        if ($activeAdjustMachine) {
            return response()->json([
                'message' => 'Adjust machine sudah berjalan',
                'log_id' => $activeAdjustMachine->id,
                'operator' => [
                    'name' => $operatorUser ? $operatorUser->name : $activeAdjustMachine->pic,
                    'profile_path' => $operatorUser && $operatorUser->profile_picture
                        ? asset('storage/' . $operatorUser->profile_picture)
                        : asset('images/default_profile.jpg'),
                ],
            ]);
        }

        // Simpan log
        $adjustMachine = AdjustMachineLog::create([
            'user_id' => $userId,
            'pic' => $request->pic_name,
            'item_code' => $nextItemCode,
            'created_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Adjust Machine started', 'log_id' => $adjustMachine->id, 'operator' => [
            'name' => $operatorUser->name,
            'profile_path' => $operatorUser->profile_picture
                ? asset('storage/' . $operatorUser->profile_picture)
                : asset('images/default_profile.jpg'),
        ]]);
    }

    public function startRepairMachine(Request $request)
    {
        $userId = Auth::id();
        $today = Carbon::now()->format('Y-m-d');

        $request->validate([
            'pic_name' => 'required|string|max:255',
        ]);

        $operatorUser = OperatorUser::where('name', $request->pic_name)->first();

        // Guard: cek apakah sudah ada repair machine aktif (belum selesai)
        $activeRepairMachine = RepairMachineLog::where('user_id', $userId)
            ->whereNull('finish_repair')
            ->first();

        if ($activeRepairMachine) {
            return response()->json([
                'message' => 'Repair machine sudah berjalan',
                'repair_id' => $activeRepairMachine->id,
                'log_id' => $activeRepairMachine->id,
                'operator' => [
                    'name' => $operatorUser ? $operatorUser->name : $activeRepairMachine->pic,
                    'profile_path' => $operatorUser && $operatorUser->profile_picture
                        ? asset('storage/' . $operatorUser->profile_picture)
                        : asset('images/default_profile.jpg'),
                ],
            ]);
        }

        // Create a new repair machine log entry
        $repairmachine = RepairMachineLog::create([
            'user_id' => $userId,
            'pic' => $request->pic_name,
            'created_at' => Carbon::now(), // Start time
        ]);

        // Set machine job user_id to NULL (machine is inactive)
        MachineJob::where('user_id', $userId)->update(['item_code' => null, 'shift' => null, 'dic_id' => null]);

        return response()->json(['message' => 'Repair Machine started', 'repair_id' => $repairmachine->id, 'log_id' => $repairmachine->id, 'operator' => [
            'name' => $operatorUser ? $operatorUser->name : $request->pic_name,
            'profile_path' => $operatorUser && $operatorUser->profile_picture 
                ? asset('storage/' . $operatorUser->profile_picture)  // Convert to full URL
                : asset('images/default_profile.jpg'),  // Default profile image
        ],]);
    }

    public function endMouldChange(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();

        // Update the last mould change log where user_id matches
        $mouldChange = MouldChangeLog::where('user_id', $userId)
            ->whereNull('end_time') // Find an ongoing mould change
            ->latest()
            ->first();

        if ($mouldChange) {
            $mouldChange->update(['end_time' => Carbon::now(),
        'remark' => $request->remarks,]);

            return response()->json(['message' => 'Mould change completed']);
        }

        return response()->json(['error' => 'No active mould change found'], 400);
    }

    private function resetUserJob($userId)
    {
        // Shift hanya bisa berubah lewat manual submit oleh operator (updateMachineJob)
        MachineJob::where('user_id', $userId)->update([
            'item_code' => null,
            'shift' => null,
            'dic_id' => null,
        ]);
    }

    public function resetJob()
    {
        $this->resetUserJob(auth()->id());
        return redirect()->back()->with('success', 'Job has been resetted!');
    }

    public function endAdjustMachine(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();

        // Update the last mould change log where user_id matches
        $AdjustMachine = AdjustMachineLog::where('user_id', $userId)
            ->whereNull('end_time') // Find an ongoing mould change
            ->latest()
            ->first();

        if ($AdjustMachine) {
            $AdjustMachine->update([
                'end_time' => Carbon::now(),
                'remark' => $request->remarks,
            ]);

            return response()->json(['message' => 'Adjust Machine completed']);
        }

        return response()->json(['error' => 'No active mould change found'], 400);
    }
    
    public function endRepairMachine(Request $request)
    {
        $request->validate([
            'problem' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();

        $repairLog = RepairMachineLog::where('user_id', $userId)
            ->whereNull('finish_repair')
            ->latest()
            ->first();

        if ($repairLog) {
            $repairLog->update([
                'finish_repair' => Carbon::now(),
                'problem' => $request->problem,
                'remark' => $request->remarks,
            ]);

            // Reset machine job langsung di sini
            $this->resetUserJob($userId);

            return response()->json(['message' => 'Repair Machine completed']);
        }

        return response()->json(['error' => 'No active repair process found'], 400);
    }

    //verify untuk login operator 
    public function verifyNIKPassword(Request $request)
    {
        $nik = $request->input('nik');
        $password = $request->input('password');

        // Validate the incoming data
        $validated = $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        // Attempt to find the operator user by NIK
        $operatorUser = OperatorUser::where('name', $nik)->first();

        if ($operatorUser && $password === $operatorUser->password) {
            $profilePicture = $operatorUser->profile_picture ? asset('storage/' . $operatorUser->profile_picture) : asset('default-avatar.png');
            // If user exists and password matches, return success
            return response()->json(['success' => true, 'message' => 'NIK and password are verified','profile_picture' => $profilePicture,
            'operator_name' => $operatorUser->name,]);
        }

        // If the NIK or password doesn't match, return an error
        return response()->json(['success' => false, 'message' => 'Invalid NIK or Password'], 400);
    }

    ////verify untuk adjuster, dan change moulder saat scan id card 
    public function verifyNik(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = OperatorUser::where('name', $request->nik)->first();

        if (!$user || $user->password !== $request->password) { // Direct string check
            return response()->json(['error' => 'Invalid NIK or password'], 401);
        }

        return response()->json([
            'message' => 'NIK Verified Successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name, // Pass the username
                'profile_path' => $user->profile_path, // Include profile picture
            ]
        ]);
    }

    // Update Mould Change Log
    public function updateMouldChangeLog(Request $request, $id)
    {
        $request->validate([
            'created_at' => 'required|date',
            'end_time'   => 'required|date',
            'remark'     => 'nullable|string',
        ]);

        $log = MouldChangeLog::findOrFail($id);
        $log->created_at = Carbon::parse($request->created_at, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        $log->end_time   = Carbon::parse($request->end_time, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        $log->remark     = $request->remark;
        $log->save();

        return response()->json(['success' => true, 'message' => 'Mould Change Log updated successfully']);
    }

    // Update Adjust Machine Log
    public function updateAdjustMachineLog(Request $request, $id)
    {
        $request->validate([
            'created_at' => 'required|date',
            'end_time'   => 'required|date',
            'remark'     => 'nullable|string',
        ]);

        $log = AdjustMachineLog::findOrFail($id);
        $log->created_at = Carbon::parse($request->created_at, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        $log->end_time   = Carbon::parse($request->end_time, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        $log->remark     = $request->remark;
        $log->save();

        return response()->json(['success' => true, 'message' => 'Adjust Machine Log updated successfully']);
    }

    // Update Repair Machine Log
    public function updateRepairMachineLog(Request $request, $id)
    {
        $request->validate([
            'created_at'    => 'required|date',
            'finish_repair' => 'required|date',
            'remark'        => 'nullable|string',
        ]);

        $log = RepairMachineLog::findOrFail($id);
        $log->created_at    = Carbon::parse($request->created_at, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        $log->finish_repair = Carbon::parse($request->finish_repair, 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        $log->remark        = $request->remark;
        $log->save();

        return response()->json(['success' => true, 'message' => 'Repair Machine Log updated successfully']);
    }

    //function untuk auto-login semua user dengan link/auto-login/{user}
    public function autoLogin($user, Request $request)
    {
        // Find the user in the database
        $userData = User::where('name', $user)->first();

        if (!$userData) {
            return redirect('/login')->with('error', 'User not found.');
        }

        // Log in the user
        Auth::login($userData);

        // Redirect to dashboard
        return redirect()->route('dashboard');
    }

    //route untuk delete scan data operator
    public function deleteScanData($id)
    {
        $scan = ProductionScannedData::find($id);
        if (!$scan) {
            return back()->with('error', 'Data scan tidak ditemukan');
        }

        if ($scan->processed) {
            return back()->with('error', 'Data scan tidak bisa dihapus karena sudah diproses ke Summary / SAP.');
        }

        DB::beginTransaction();

        try {
            // Ambil informasi dasar
            $dicId = $scan->dic_id;
            $scanTime = Carbon::parse($scan->created_at)->timezone('Asia/Jakarta');
            $scanHour = $scanTime->format('H:i:s');
            $userId = $scan->user;
        

            // Cari entri hourly yang cocok
            $hourly = HourlyRemark::where('dic_id', $dicId)
                ->where('pic', $userId)
                ->where('start_time', '<=',  $scanHour)
                ->where('end_time', '>',  $scanHour)
                ->first();
            
            if ($hourly) {
                $hourly->actual = max(0, $hourly->actual - $scan->quantity); // hindari negatif
                // Update is_achieve jika perlu
                if ($hourly->actual < $hourly->target) {
                    $hourly->is_achieve = 0;
                }
                $hourly->save();
            }

            $scan->delete();

            DB::commit();

            return back()->with('success', 'Scan berhasil dihapus dan actual diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }


    public function showROPData()
    {
        $today = Carbon::today(); // ambil tanggal hari ini tanpa jam

        $data = ProductionScannedData::whereDate('created_at', $today)
            ->select('spk_code', 'item_code', 'warehouse', 'quantity', 'label')
            ->get();

        return response()->json($data);
    }

    //function untuk update cycletime per shift ( per item code)
    public function updateCycleTime(Request $request, $id)
    {
        $request->validate([
            'temporal_cycle_time' => 'required|string|max:255',
        ]);

        $record = DailyItemCode::findOrFail($id);
        $record->temporal_cycle_time = $request->temporal_cycle_time;
        $record->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Temporal cycle time updated successfully.'
            ]);
        }

        return back()->with('success', 'Temporal cycle time updated successfully.');
    }

    // function untuk update lot material per shift (per item code)
    public function updateMaterialLot(Request $request, $id)
    {
        $request->validate([
            'material_lot' => 'nullable|string|max:255',
        ]);

        $record = DailyItemCode::findOrFail($id);
        $record->material_lot = $request->material_lot ? strtoupper(trim($request->material_lot)) : null;
        $record->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lot Material updated successfully.',
                'material_lot' => $record->material_lot,
            ]);
        }

        return back()->with('success', 'Lot Material updated successfully.');
    }

    // Get accessory lots for a DIC
    public function getAccessoryLots($dicId)
    {
        $lots = DicAccessoryLot::where('dic_id', $dicId)->orderBy('created_at', 'asc')->get();
        return response()->json([
            'success' => true,
            'accessory_lots' => $lots,
        ]);
    }

    // Store accessory lot for a DIC
    public function storeAccessoryLot(Request $request, $dicId)
    {
        $request->validate([
            'accessory_name' => 'required|string|max:255',
            'accessory_lot' => 'required|string|max:255',
        ]);

        $dic = DailyItemCode::findOrFail($dicId);

        $lot = DicAccessoryLot::create([
            'dic_id' => $dic->id,
            'accessory_name' => trim($request->accessory_name),
            'accessory_lot' => trim($request->accessory_lot),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lot Accessories berhasil ditambahkan.',
                'accessory_lot' => $lot,
            ]);
        }

        return back()->with('success', 'Lot Accessories berhasil ditambahkan.');
    }

    // Delete accessory lot
    public function deleteAccessoryLot($id)
    {
        $lot = DicAccessoryLot::findOrFail($id);
        $lot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lot Accessories berhasil dihapus.',
        ]);
    }

    // function untuk update actual produksi yang didapat operator perjam 
    public function updateActualProduction(Request $request, $id)
    {
        $request->validate([
            'actual_production' => 'required|integer|min:0',
        ]);

        $slot = HourlyRemark::findOrFail($id);
        $slot->actual_production = $request->actual_production;
        $slot->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Actual Production updated successfully.'
            ]);
        }

        return back()->with('success', 'Actual Production updated successfully.');
    }

    // function untuk update ng produksi yang didapat operator perjam 
    public function updateNgProduction(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'NG' => 'required|integer|min:0',
        ]);

        $slot = HourlyRemark::findOrFail($id);
        $slot->NG = $request->NG;
        $slot->save();

        return back()->with('success', 'NG updated successfully.');
    }

    // function untuk update remark dic perjam
    public function updateRemarkDIC(Request $request, $id)
    {
        $validated = $request->validate([
            'remark' => 'nullable|string|max:255'
        ]);

        $item = DailyItemCode::findOrFail($id);
        $item->remark = $validated['remark'];
        $item->save();

        return response()->json(['message' => 'Remark updated']);
    }

    // function untuk store hourly remark perjam 
    public function storeHourlyRemark(Request $request)
    {
        $activedic = json_decode($request->activedic, true);
        $dicId = $activedic['id'];
        $itemCode = $activedic['item_code'];
  
   
    
        $startTime = Carbon::parse($request->start_time);
        $endTime = (clone $startTime)->addHour(); // 1 jam interval
    
        // Priority 1: Get from daily_item_codes.temporal_cycle_time
        $daily = DailyItemCode::where('id', $dicId)->first();
        $temporal = $daily?->temporal_cycle_time;
        
        if ($temporal && is_numeric($temporal) && $temporal > 0) {
            $target = floor(3600 / $temporal);
        } else {
            // Priority 2: Get from sap_inventory_fg
            $sap = sapInventoryFg::where('item_code', $itemCode)->first();
            $cycle = $sap?->cycle_time;
    
            if ($cycle && is_numeric($cycle) && $cycle > 0) {
                $target = floor(3600 / ($cycle * 60));
            } else {
                $target = 0; // fallback jika semua kosong
            }
        }

        $exists = HourlyRemark::where('dic_id',  $activedic['id'])
        ->where('start_time', $startTime)
        ->exists();

        if ($exists) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dengan jam tersebut sudah ada untuk DIC yang sama.'
                ], 422);
            }
            return back()->with('error', 'Data dengan jam tersebut sudah ada untuk DIC yang sama.');
        }
    
        // Insert hourly remark
        HourlyRemark::create([
            'dic_id' => $dicId,
            'start_time' => $startTime->format('H:i:s'),
            'end_time' => $endTime->format('H:i:s'),
            'target' => $target,
            'pic' => $request->nik ?: $request->pic,
            'pic_2' => $request->pic_2,
            'pic_3' => $request->pic_3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Hourly Remark berhasil ditambahkan!'
            ]);
        }

        return back()->with('success', 'Hourly Remark berhasil ditambahkan!');
    }

    // view untuk lihat log api yang berjalan di website 
    public function apiLog()
    {
        $logs = ApiLog::latest()->take(150)->get(); 
        return view('Log.api_logs', compact('logs'));
    }

    // function untuk melihat semua operator 
    public function operatoradmin()
    {
        $user = auth()->user();

        if (!$user->hasRole('OPERATOR')) {
            return redirect()->back()->with('error', 'Akses ditolak: hanya operator yang bisa membuka halaman ini.');
        }

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

         $datas = DailyItemCode::where('user_id', $user->id)
                ->whereIn('start_date', [$yesterday, $today, $tomorrow])
                ->with('hourlyRemarks','scannedData')
                ->orderBy('start_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();
        
        // lanjut kalau operator
        // dd($datas);
        return view('dashboards.adminoperator', compact('datas')); // atau halaman kamu
    }

    // function untuk membuat data operator baru 
    public function createFromAdmin(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'item_code' => 'required|string|max:255',
            'shift' => 'required|integer|in:1,2,3',
            'start_schedule_date' => 'required|date',
            'end_schedule_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'quantity' => 'required|integer|min:0',
        ]);

        // Masukin ke tabel DailyItemCode
        $daily = new DailyItemCode();
        $daily->user_id = $validated['user_id'];
        $daily->item_code = $validated['item_code'];
        $daily->quantity = $validated['quantity'];
        $daily->final_quantity = null; 
        // $cavity = $remark->dailyItemCode->masterItem->cavity;
                    // dd($cavity);
        $daily->loss_package_quantity = null;
        $daily->actual_quantity = 0;
        $daily->shift = $validated['shift'];
        $daily->start_date = $validated['start_schedule_date'];
        $daily->start_time = $validated['start_time'];
        $daily->end_date = $validated['end_schedule_date'];
        $daily->end_time = $validated['end_time'];
        $daily->schedule_date = $validated['start_schedule_date'];
        $daily->is_done = null;
        $daily->remark = null;
        $daily->temporal_cycle_time = null;
        $daily->save();

        return redirect()->back()->with('message', 'Daily Item Code berhasil ditambahkan!');
    }


    // function untuk menghapus remark dic 
    public function deleteremark($id)
    {
        //works

        $remark = HourlyRemark::findOrFail($id);

        // Ambil range waktu
        $start = Carbon::parse($remark->start_time);
        $end   = Carbon::parse($remark->end_time);

        // Cek apakah sudah ada yang diproses
        $hasProcessed = ProductionScannedData::where('dic_id', $remark->dic_id)
             ->whereRaw('CONVERT_TZ(created_at, "+00:00", "+07:00") BETWEEN ? AND ?', [$start, $end])
             ->where('processed', true)
             ->exists();

        if ($hasProcessed) {
            return redirect()->back()->with('error', 'Hourly Remark tidak bisa dihapus karena sebagian/seluruh data scan di jam ini sudah diproses ke Summary / SAP.');
        }

        // Hapus scanned data dalam rentang waktu itu
        $test = ProductionScannedData::where('dic_id', $remark->dic_id)
             ->whereRaw('CONVERT_TZ(created_at, "+00:00", "+07:00") BETWEEN ? AND ?', [$start, $end])
            // ->get();
            ->delete();
        // dd($test);

        // Hapus hourly remark
        $remark->delete();

        return redirect()->back()->with('message', 'Hourly Remark dan data terkait berhasil dihapus.');
    }

    // function untuk menghapus daily item code oleh operator
    public function deletedic($id)
    {
        $item = DailyItemCode::findOrFail($id);

        $hasProcessed = $item->scannedData()->where('processed', true)->exists();
        if ($hasProcessed) {
            return redirect()->back()->with('error', 'Daily Item Code tidak bisa dihapus karena sudah ada data scan yang diproses ke Summary / SAP.');
        }

        // Hapus juga relasi kalau ada
        $item->hourlyRemarks()->delete();
        $item->scannedData()->delete();

        $item->delete();

        return redirect()->back()->with('message', 'Daily Item Code dan data terkait berhasil dihapus.');
    }

    // function untuk update temporal cavity oleh operator per shift per itemcode 
    public function updateTemporalCavity(Request $request, $id)
    {
        // Validasi input
        $validated = $request->validate([
            'temporal_cavity' => 'nullable|integer|min:0',
        ]);

        // Ambil record
        $dailyItemCode = DailyItemCode::findOrFail($id);
        
        // Update field temporal_cavity
        $dailyItemCode->temporal_cavity = $validated['temporal_cavity'];
        $dailyItemCode->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Temporal cavity berhasil diperbarui.'
            ]);
        }

        return redirect()->back()->with('success', 'Temporal cavity berhasil diperbarui.');
    }

    // function untuk update resin usage oleh operator per shift per itemcode
    public function updateResinUsage(Request $request, $id)
    {
        // Validasi input
        $validated = $request->validate([
            'resin_usage' => 'nullable|numeric|min:0',
        ]);

        // Ambil record
        $dailyItemCode = DailyItemCode::findOrFail($id);
        
        // Update field resin_usage
        $dailyItemCode->resin_usage = $validated['resin_usage'];
        $dailyItemCode->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Resin usage berhasil diperbarui.'
            ]);
        }

        return redirect()->back()->with('success', 'Resin usage berhasil diperbarui.');
    }

    public function storeOutputLog(Request $request)
    {
        $user = auth()->user();
        
        $itemCode = $user->jobs->item_code ?? null;
        
        $todayDIC = DailyItemCode::where('user_id', $user->id)
            ->whereDate('schedule_date', Carbon::today())
            ->where('item_code', $itemCode)
            ->with('masterItem')
            ->whereNull('is_done')
            ->orderBy('shift')
            ->first();

        $previousDIC = DailyItemCode::where('user_id', $user->id)
            ->where('schedule_date', '<', Carbon::today())
            ->where('item_code', $itemCode)
            ->with('masterItem')
            ->whereNull('is_done')
            ->orderByDesc('schedule_date')
            ->first();

        $activeDIC = $previousDIC ?? $todayDIC;

        if (!$activeDIC) {
            $activeDIC = DailyItemCode::where('user_id', $user->id)
                ->where('item_code', $itemCode)
                ->with('masterItem')
                ->whereNull('is_done')
                ->first();
        }

        if (!$activeDIC) {
            return redirect()->back()->with('error', 'Tidak ada Daily Item Code aktif untuk mesin/user ini.');
        }

        $quantity = 1;
        if (!empty($activeDIC->temporal_cavity) && $activeDIC->temporal_cavity > 0) {
            $quantity = $activeDIC->temporal_cavity;
        } elseif ($activeDIC->masterItem && !empty($activeDIC->masterItem->cavity) && $activeDIC->masterItem->cavity > 0) {
            $quantity = $activeDIC->masterItem->cavity;
        }

        $operatorName = $request->input('operator_name') ?: (session('verifiedNIK') ?: 'Unknown Operator');

        $log = ProductionOutputLog::create([
            'dic_id' => $activeDIC->id,
            'operator_name' => strtoupper($operatorName),
            'quantity' => $quantity,
            'logged_at' => Carbon::now('Asia/Jakarta'),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Log output berhasil ditambahkan.',
                'log_id' => $log->id,
                'log' => [
                    'id' => $log->id,
                    'time' => $log->logged_at->format('H:i:s'),
                    'operator_name' => $log->operator_name,
                    'quantity' => $log->quantity
                ]
            ]);
        }

        return redirect()->back()
            ->with('success', 'Log output berhasil ditambahkan.')
            ->with('print_log_id', $log->id);
    }

    // public function printOutputLog($id)
    // {
    //     $log = ProductionOutputLog::with('dailyItemCode.masterItem')->findOrFail($id);
        
    //     // QR Code content: [Item Code] \t [Operator Name] \t [Logged At]
    //     $qrData = implode("\t", [
    //         $log->dailyItemCode->item_code,
    //         $log->operator_name,
    //         $log->logged_at->format('Y-m-d H:i:s')
    //     ]);

    //     $qrCode = new QrCode(
    //         data: $qrData,
    //         errorCorrectionLevel: ErrorCorrectionLevel::Medium,
    //         size: 60,
    //         margin: 0
    //     );
        
    //     $writer = new PngWriter();
    //     $qrCodeResult = $writer->write($qrCode);
    //     $qrCodeBase64 = base64_encode($qrCodeResult->getString());

    //     return view('dashboards.print_output_log', compact('log', 'qrCodeBase64'));
    // }

    // public function printOutputLog($id)
    // {
    //     $log = ProductionOutputLog::with(
    //         'dailyItemCode.masterItem'
    //     )->findOrFail($id);

    //     $masterItem = $log->dailyItemCode->masterItem;
    //     $temporalCavity = $log->dailyItemCode?->temporal_cavity;

    //     $itemCodes = [
    //         $log->dailyItemCode->item_code
    //     ];

    //     $pair = trim((string)($masterItem->pair ?? '0'));

    //     $hasPair = (
    //         $pair !== '' &&
    //         $pair !== '0'
    //     );

    //     // Jika ada pair -> cetak item utama + pair
    //     if ($hasPair) {

    //         $itemCodes[] = $pair;

    //     }
    //     // Jika tidak ada pair dan temporal cavity = 2
    //     elseif ((int)$temporalCavity === 2) {

    //         $itemCodes[] = $log->dailyItemCode->item_code;
    //     }

    //     $barcodes = [];

    //     foreach ($itemCodes as $itemCode) {

    //         $qrData = implode("\t", [
    //             $itemCode,
    //             $log->operator_name,
    //             $log->logged_at->format('Y-m-d H:i:s')
    //         ]);

    //         $qrCode = new QrCode(
    //             data: $qrData,
    //             errorCorrectionLevel: ErrorCorrectionLevel::Medium,
    //             size: 60,
    //             margin: 0
    //         );

    //         $writer = new PngWriter();

    //         $result = $writer->write($qrCode);

    //         $barcodes[] = [
    //             'item_code' => $itemCode,
    //             'qrCodeBase64' => base64_encode(
    //                 $result->getString()
    //             )
    //         ];
    //     }

    //     return view(
    //         'dashboards.print_output_log',
    //         compact(
    //             'log',
    //             'barcodes'
    //         )
    //     );
    // }

    public function printOutputLog($id)
    {
        $totalStart = microtime(true);

        // =========================
        // QUERY
        // =========================
        $queryStart = microtime(true);

        $log = ProductionOutputLog::with(
            'dailyItemCode.masterItem'
        )->findOrFail($id);

        $masterItem = $log->dailyItemCode->masterItem;
        $temporalCavity = $log->dailyItemCode->temporal_cavity ?? 1;

        // =========================
        // BUILD ITEM CODE
        // =========================
        $buildStart = microtime(true);

        $itemCodes = [];

        $pair = trim((string)($masterItem->pair ?? ''));

        $hasPair = (
            $pair !== '' &&
            $pair !== '0'
        );

        if ($hasPair) {

            $itemCodes[] = $log->dailyItemCode->item_code;
            $itemCodes[] = $pair;

        } else {

            $cavity = (int)$temporalCavity;

            if ($cavity < 1) {
                $cavity = 1;
            }

            if ($cavity > 2) {
                $cavity = 2;
            }

            for ($i = 0; $i < $cavity; $i++) {
                $itemCodes[] = $log->dailyItemCode->item_code;
            }
        }

        // =========================
        // QR GENERATION
        // =========================
        $qrStart = microtime(true);

        $barcodes = [];

        foreach ($itemCodes as $itemCode) {

            $singleQrStart = microtime(true);

            $qrData = implode("\t", [
                $itemCode,
                $log->operator_name,
                $log->logged_at->format('Y-m-d H:i:s')
            ]);

            $qrCode = new QrCode(
                data: $qrData,
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 90,
                margin: 0
            );

            $writer = new PngWriter();

            $result = $writer->write($qrCode);

            $barcodes[] = [
                'item_code' => $itemCode,
                'qrCodeBase64' => base64_encode(
                    $result->getString()
                )
            ];

  
        }



        // =========================
        // TOTAL
        // =========================

        return view(
            'dashboards.print_output_log',
            compact(
                'log',
                'barcodes'
            )
        );
    }

}
