<?php

namespace App\Http\Controllers\Production;

ini_set('memory_limit', '1024M');

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Production\PRD_BillOfMaterialChild;
use App\Models\Production\PRD_BillOfMaterialParent;
use App\Models\Production\PRD_MaterialLog;
use App\Models\Production\PRD_MouldingUserLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function determineShift($clockIn): ?int
    {
        $clockIn = Carbon::parse($clockIn);

        $today = $clockIn->format('Y-m-d');
        // Define shift start and end times
        $shiftStartTimes = [
            1 => Carbon::parse("$today 07:30:00"),
            2 => Carbon::parse("$today 15:30:00"),
            3 => Carbon::parse("$today 23:30:00"),
        ];

        // Define shift end times
        $shiftEndTimes = [
            1 => Carbon::parse("$today 15:30:00"),
            2 => Carbon::parse("$today 23:30:00"),
            3 => Carbon::parse("$today 07:30:00")->addDay(), // Shift 3 ends at 07:30 AM next day
        ];

        // // If the clock-in time is before 07:30 AM today, it's part of Shift 3

        foreach ($shiftStartTimes as $shift => $startTime) {
            $endTime = $shiftEndTimes[$shift];
            if ($clockIn->between($startTime, $endTime, true)) { // 'true' makes the comparison inclusive
                return $shift;
            }
        }

        if ($clockIn->lt($shiftStartTimes[1])) {
            return 3; // Shift 3
        }

        return null; // Return null if no shift matches
    }

    public function updateUsername(Request $request)
    {
        // Validate the input
        $request->validate([
            'username' => 'required|string|max:255',
        ]);

        // Update the username for the currently logged-in user
        $user = auth()->user();
        $user->username = $request->input('username');
        $user->save();

        // Redirect back with a success message
        return redirect()->route('workshop.main.menu')->with('success', 'Your username has been updated successfully.');
    }

    public function handleScanStart(Request $request)
    {
        // dd($request->all());

        $clockIn = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $barcode = $request->input('barcode');

        // Split the barcode using '-' as the separator
        if (strpos($barcode, '~') !== false) {
            [$item_code, $item_id] = explode('~', $barcode, 2);
        } else {
            return back()->with('error', 'Invalid barcode format.');
        }
        $scanTime = now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $user = auth()->user();

        $data = PRD_BillOfMaterialChild::with('materialProcess', 'parent')->where('id', $item_id)->first();

        if (! $data) {
            return redirect()->back()->withErrors(['error' => 'Barcode data not found or invalid.']);
        }

        if ($data->status === 'Canceled') {
            return redirect()->back()->withErrors(['error' => 'Item sudah di cancel']);
        }

        $materialProcess = $data->materialProcess()
            ->where('process_name', $user->name)
            ->whereNull('scan_in')  // Ensure scan_in is null
            ->orderBy('created_at', 'asc')  // Order by created_at to get the earliest
            ->first();  // Get the first match

        // If a matching material process is found, update its scan_in field with the scan time
        if ($materialProcess) {
            $materialProcess->scan_in = $scanTime;
            $materialProcess->pic = $user->username;
            $materialProcess->status = 1;
            $materialProcess->save();  // Save the changes
            $anyScanIn = $data->materialProcess()->whereNotNull('scan_in')->exists();

            // If any materialProcess has scan_in not null, update the status to 'started'
            if ($anyScanIn) {
                $data->status = 'Started';
                $data->save();  // Save the status update
            }
        } else {
            $barcodeElements = explode('~', $barcode); // Split barcode into elements
            $childId = $barcodeElements[1] ?? null; // Get the second element (child ID)
            if ($childId) {
                // Create a new material process using PRD_MaterialLog model
                $newMaterialProcess = new \App\Models\Production\PRD_MaterialLog;
                $newMaterialProcess->child_id = $childId;
                $newMaterialProcess->process_name = auth()->user()->name; // Use the authenticated user's name
                $newMaterialProcess->scan_in = $scanTime; // Use the current scan time
                $newMaterialProcess->scan_start = null;
                $newMaterialProcess->scan_out = null;
                $newMaterialProcess->status = 0; // Default status
                $newMaterialProcess->pic = $user->username; // Assign the user's username
                $newMaterialProcess->remark = 'Proses Tambahan'; // Optional remark
                $newMaterialProcess->is_draft = true; // Set is_draft to true
                $newMaterialProcess->save(); // Save the new record

                return redirect()->route('workshop.main.menu');
            } else {
                return back()->with('error', 'Invalid barcode format.');
            }
        }

        return redirect()->route('workshop.main.menu');
    }

    public function updateMaterialChildStatus($materialChildId)
    {
        // Retrieve all processes associated with the given MaterialChild ID where status is 1 (finished)
        $allProcessesFinished = PRD_MaterialLog::where('child_id', $materialChildId)
            ->where('status', 2) // Only check for finished status
            ->count() === PRD_MaterialLog::where('child_id', $materialChildId)->count(); // Check if all related processes are finished

        // If all processes are finished, update the MaterialChild status
        if (! $allProcessesFinished) {
            return; // Skip the update and exit early
        }

        // If all processes are finished, update the MaterialChild status
        PRD_BillOfMaterialChild::where('id', $materialChildId)
            ->update(['status' => 'Finished']);
    }

    public function updateAllMaterialChildrenStatus()
    {
        // Retrieve material children with the status 'Not Finished' or something that needs updating
        $materialChildren = PRD_BillOfMaterialChild::where('status', '=', 'Started')->pluck('id');

        foreach ($materialChildren as $materialChildId) {
            $this->updateMaterialChildStatus($materialChildId);
        }
    }

    public function handeScanOut(Request $request)
    {
        $logId = $request->input('log_id');
        $childId = $request->input('child_id');
        $verifydata = PRD_BillOfMaterialChild::find($childId);
        $verifiedbarcode = $verifydata->item_code . '~' . $verifydata->id;
        $barcode = $request->input('scan_out');

        if ($verifiedbarcode !== $barcode) {
            return redirect()->back()->with('error', 'Barcode salah brow');
        }

        $scanOutTime = now('Asia/Jakarta')->format('Y-m-d H:i:s'); // Current timestamp for scan out

        $log = PRD_MaterialLog::find($logId);

        $log->scan_out = $scanOutTime;
        $log->status = '2';
        $log->save();

        $this->updateAllMaterialChildrenStatus();

        return redirect()->route('workshop.main.menu');
    }

    public function index($id)
    {

        $user = auth()->user();

        $log = PRD_MaterialLog::with('childData', 'childData.parent', 'childData.materialProcess')->find($id);
        // Initialize an array to hold the process names and statuses

        $allprocess = $log->childData->materialProcess;

        $image = File::where('item_code', $log->childData->item_code)->get();
        $workers = PRD_MouldingUserLog::where('material_log_id', $id)->get();

        return view('production.workshop.index', compact('log', 'workers', 'allprocess', 'image'));

    }

    public function mainMenuByWorkshop()
    {
        $user = auth()->user();
        $codeFilter = request('code');

        // Ambil daftar BOM/Project Code unik dari relasi
        $distinctCodes = PRD_MaterialLog::with(['childData.parent'])
            ->where('process_name', $user->name)
            ->whereNotNull('scan_in')
            ->get()
            ->pluck('childData.parent.code')
            ->filter() // buang null
            ->unique()
            ->values();

        $logsQuery = PRD_MaterialLog::with(['childData.parent'])
            ->where('process_name', $user->name)
            ->whereNotNull('scan_in');

        if ($codeFilter) {
            $logsQuery->whereHas('childData.parent', function ($q) use ($codeFilter) {
                $q->where('code', $codeFilter);
            });
        }

        $logs = $logsQuery->get();

        $this->updateAllMaterialChildrenStatus();

        if ($user->username === null) {
            return redirect()->route('dashboard');
        } else {
            return view('production.workshop.mainmenu', compact('logs', 'user', 'distinctCodes', 'codeFilter'));
        }
    }

    public function addWorker(Request $request)
    {
        //test
        // $clockIn = Carbon::parse('23:40:00', 'Asia/Jakarta')->format('Y-m-d H:i:s');
        // dd($clockIn);
        $clockIn = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
        // Validate the incoming request data
        $request->validate([
            'username' => 'required|string|max:255', // Adjust validation as needed
            'log_id' => 'required|exists:prd_material_logs,id', // Ensure the job exists in the jobs table
            'job' => 'nullable|string|max:255', // Validate the job field (you can adjust this if you want to enforce specific jobs)
            'remark_worker' => 'nullable|string|max:500', // Validate the remark_worker (optional field)
        ]);

        // Create a new worker and associate it with the job
        PRD_MouldingUserLog::create([
            'material_log_id' => $request->input('log_id'),
            'username' => $request->input('username'),
            'shift' => $this->determineShift($clockIn),
            'jobs' => $request->input('job'), // Save the job
            'remark' => $request->input('remark_worker'),
        ]);

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Worker added successfully.');
    }

    public function summaryDashboard()
    {
        $datas = PRD_MaterialLog::with('childData', 'workers', 'childData.parent')->paginate(10);

        return view('production.workshop.summarydashboard', compact('datas'));
    }

    public function setScanStart(Request $request)
    {
        $log = PRD_MaterialLog::find($request->log_id);

        // Set the scan_start to current Jakarta time (timezone 'Asia/Jakarta')
        if (is_null($log->scan_start)) {
            $log->scan_start = Carbon::now('Asia/Jakarta');
            $log->save();
        }

        return redirect()->route('workshop.index', ['id' => $log->id]); // Redirect to the same page (or another route you prefer)
    }

    public function storeRemark(Request $request, $log_id)
    {
        // Validate input
        $request->validate([
            'remark' => 'required|string|max:255',
        ]);

        // Find the log and update its remark
        $log = PRD_MaterialLog::findOrFail($log_id);
        $log->remark = $request->remark;
        $log->save();

        // Redirect back to the log show page
        return redirect()->route('workshop.index', ['id' => $log->id]);
    }

    public function updateWorker(Request $request)
    {
        // Validate the input data
        $request->validate([
            'worker_id' => 'required|exists:prd_moulding_user_logs,id', // Ensure worker exists
            'username' => 'nullable|string|max:255',
            'job' => 'nullable|string|max:255',
            'remark' => 'nullable|string|max:255',
        ]);

        // Find the worker by ID and update the details
        $worker = PRD_MouldingUserLog::find($request->worker_id);
        $worker->username = $request->username;
        $worker->jobs = $request->job;
        $worker->remark = $request->remark;
        $worker->save();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Worker details updated successfully.');
    }

    public function removeScanIn($id)
    {
        $log = PRD_MaterialLog::find($id);

        // Your logic to remove the scan_in goes here
        // For example:
        $log->scan_in = null;  // Remove scan_in or reset as needed
        $log->pic = null;
        $log->remark = null;
        $log->save();

        return redirect()->route('workshop.main.menu')->with('status', 'Scan In removed successfully!');
    }

    public function addManualWorkshop()
    {
        $parents = PRD_BillOfMaterialParent::all();

        return view('production.workshop.add_manual', compact('parents'));
    }

    public function getChildren($parentId)
    {
        return PRD_BillOfMaterialChild::where('parent_id', $parentId)->get();
    }

    public function handleScanManual(Request $request)
    {

        $clockIn = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $barcode = $request->child;

        // Split the barcode using '-' as the separator
        if (strpos($barcode, '~') !== false) {
            [$item_code, $item_id] = explode('~', $barcode, 2);
        } else {
            return back()->with('error', 'Invalid barcode format.');
        }
        $scanTime = now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $user = auth()->user();

        $data = PRD_BillOfMaterialChild::with('materialProcess', 'parent')->where('id', $item_id)->first();

        if (! $data) {
            return redirect()->back()->withErrors(['error' => 'Barcode data not found or invalid.']);
        }

        if ($data->status === 'Canceled') {
            return redirect()->back()->withErrors(['error' => 'Item sudah di cancel']);
        }

        $materialProcess = $data->materialProcess()
            ->where('process_name', $user->name)
            ->whereNull('scan_in')  // Ensure scan_in is null
            ->orderBy('created_at', 'asc')  // Order by created_at to get the earliest
            ->first();  // Get the first match

        // If a matching material process is found, update its scan_in field with the scan time
        if ($materialProcess) {
            $materialProcess->scan_in = $scanTime;
            $materialProcess->pic = $user->username;
            $materialProcess->status = 1;
            $materialProcess->save();  // Save the changes
            $anyScanIn = $data->materialProcess()->whereNotNull('scan_in')->exists();

            // If any materialProcess has scan_in not null, update the status to 'started'
            if ($anyScanIn) {
                $data->status = 'Started';
                $data->save();  // Save the status update
            }
        } else {
            $barcodeElements = explode('~', $barcode); // Split barcode into elements
            $childId = $barcodeElements[1] ?? null; // Get the second element (child ID)
            if ($childId) {
                // Create a new material process using PRD_MaterialLog model
                $newMaterialProcess = new \App\Models\Production\PRD_MaterialLog;
                $newMaterialProcess->child_id = $childId;
                $newMaterialProcess->process_name = auth()->user()->name; // Use the authenticated user's name
                $newMaterialProcess->scan_in = $scanTime; // Use the current scan time
                $newMaterialProcess->scan_start = null;
                $newMaterialProcess->scan_out = null;
                $newMaterialProcess->status = 0; // Default status
                $newMaterialProcess->pic = $user->username; // Assign the user's username
                $newMaterialProcess->remark = 'Proses Tambahan'; // Optional remark
                $newMaterialProcess->is_draft = true; // Set is_draft to true
                $newMaterialProcess->save(); // Save the new record

                return redirect()->route('workshop.main.menu');
            } else {
                return back()->with('error', 'Invalid barcode format.');
            }
        }

        return redirect()->route('workshop.main.menu');
    }
}
