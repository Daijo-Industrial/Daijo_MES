<?php

namespace App\Http\Controllers\Store;

use App\Exports\SoDataExport;
use App\Http\Controllers\Controller;
use App\Imports\SoImport;
use App\Models\ApiLog;
use App\Models\BarcodePackagingDetail;
use App\Models\BarcodePackagingMaster;
use App\Models\MasterItemPhoto;
use App\Models\ScannedData;
use App\Models\SoData;
use App\Models\SpkItemHistory;
use App\Models\SpkMaster;
use App\Models\UpdateLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SOController extends Controller
{
    public function index(Request $request)
    {
        $isDone = $request->query('is_done', 'all');
        $docNum = $request->query('doc_num', '');

        // Build the query based on filter
        $query = SoData::select('doc_num', 'is_done', 'create_date')->distinct();

        if (! empty($docNum)) {
            $query->where('doc_num', $docNum);
        }

        // Apply filter for `is_done` if specified
        if ($isDone !== 'all') {
            $query->where('is_done', $isDone);
        }

        // Paginate the results
        $docNums = $query->orderBy('doc_num', 'desc')->paginate(10);

        return view('store.soindex', compact('docNums'));
    }

    public function indexpegawai()
    {
        return view('store.sopegawaiindex');
    }

    public function process($docNum)
    {
        // Ambil customer dari data SO
        $soCustomer = SoData::where('doc_num', $docNum)->value('customer');

        // Additive: Ensure Store Out Header exists for this SO
        BarcodePackagingMaster::firstOrCreate(
            ['so_number' => $docNum],
            [
                'noDokumen' => 'OUT/SO/' . $docNum,
                'customer' => $soCustomer ?? 'UNKNOWN',
                'dateScan' => now(),
                'tipeBarcode' => 'out',
                'location' => 'Jakarta',
            ]
        );

        // 1. Ambil data mentah dari DB (termasuk is_done)
        $rawItems = SoData::where('doc_num', $docNum)->get();
        if ($rawItems->isEmpty()) {
            return view('store.soresults', [
                'data' => collect(),
                'docNum' => $docNum,
                'allFinished' => false,
                'allDone' => false,
            ]);
        }

        // Cek status is_done dari seluruh dokumen
        $allDone = $rawItems->every('is_done', 1);
        $date = $rawItems->first()->posting_date;
        $customer = $rawItems->first()->customer;

        // --- OPTIMASI: Ambil semua hitungan scan sekaligus (Single Query) ---
        $scanSummaries = ScannedData::where('doc_num', $docNum)
            ->select('item_code', DB::raw('count(*) as ctn_count'), DB::raw('sum(quantity) as total_qty'))
            ->groupBy('item_code')
            ->get()
            ->keyBy('item_code');

        // 2. Evaluasi status finish tiap item berdasarkan jumlah scan
        $data = $rawItems->groupBy('item_code')
            ->map(function ($group) use ($scanSummaries) {
                $itemCode = $group->first()->item_code;
                $totalQuantity = $group->sum('quantity');
                $entry = $group->first();
                $entry->quantity = $totalQuantity;

                // Ambil data dari summary yang sudah di-pre-fetch
                $summary = $scanSummaries->get($itemCode);
                $scannedCount = $summary ? $summary->ctn_count : 0;
                $scannedTotalQty = $summary ? $summary->total_qty : 0;

                $entry->scannedCount = $scannedCount;
                $entry->scannedTotalQuantity = $scannedTotalQty; // Field baru untuk dipakai di view
                $packagingQuantity = $entry->packaging_quantity;

                // Logika penentuan item selesai (is_finish)
                $requiredBoxes = ($packagingQuantity > 0) ? (int) ceil($totalQuantity / $packagingQuantity) : 0;
                $statusFinish = ($scannedCount >= $requiredBoxes && $requiredBoxes > 0) ? 1 : 0;

                // Hanya set jika berbeda agar tidak membebani memori/DB nanti
                $entry->is_finish = $statusFinish;

                return $entry;
            })
            ->values();

        // 3. Update status item ke DB secara spesifik (Hanya jika perlu perbaikan status)
        foreach ($data as $entry) {
            SoData::where('doc_num', $docNum)
                ->where('item_code', $entry->item_code)
                ->where('is_finish', '!=', $entry->is_finish) // Optimasi: update hanya jika berubah
                ->update(['is_finish' => $entry->is_finish]);
        }

        // 4. Kalkulasi Akhir untuk tampilan tombol
        $allFinished = $data->every('is_finish', 1);

        // Ambil data scan detail untuk tabel bawah (sudah include di scanSummaries tapi ini untuk log per baris)
        $scandatas = ScannedData::where('doc_num', $docNum)
            ->orderBy('label')
            ->get()
            ->groupBy('item_code');

        return view('store.soresults', compact('data', 'docNum', 'date', 'customer', 'scandatas', 'allFinished', 'allDone'));
    }

    public function scanBarcode(Request $request)
    {
        $request->validate([
            'spk_code' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'warehouse' => 'required|string',
            'label' => 'required_without:labels_bulk|nullable|string',
            'labels_bulk' => 'nullable|string',
            'packaging_name' => 'nullable|string',
            'packaging_label' => 'nullable|string',
            'packaging_warehouse' => 'nullable|string',
        ]);

        $doc_num = $request->input('so_number');
        $spk_code = trim($request->input('spk_code'));
        $quantity = (int) $request->input('quantity');
        $warehouse = trim($request->input('warehouse'));

        // Parse labels (either single or bulk)
        $labels = [];
        if ($request->filled('labels_bulk')) {
            $rawLabels = preg_split('/[\n\r,]+/', $request->input('labels_bulk'));
            foreach ($rawLabels as $rl) {
                $trimmed = trim($rl);
                if ($trimmed !== '') {
                    $labels[] = $trimmed;
                }
            }
            $labels = array_unique($labels);
        } else {
            $lbl = trim($request->input('label') ?? '');
            if ($lbl !== '') {
                $labels[] = $lbl;
            }
        }

        if (empty($labels)) {
            $msg = 'Please enter or scan at least one label.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }

            return redirect()->back()->withErrors(['error' => $msg]);
        }

        $item_code = SpkItemHistory::where('spk_number', $spk_code)
            ->value('item_code')
            ?? SpkMaster::where('spk_number', $spk_code)->value('item_code');

        if (! $item_code) {
            // Fallback: check if the user entered item_code directly that exists in SoData for this doc_num
            $existsInSo = SoData::where('item_code', $spk_code)
                ->where('doc_num', $doc_num)
                ->exists();
            if ($existsInSo) {
                $item_code = $spk_code;
            }
        }

        // Fetch the item data (only required fields)
        $item = SoData::where('item_code', $item_code)
            ->where('doc_num', $doc_num)
            ->first(['quantity', 'packaging_quantity', 'item_code', 'doc_num']);

        if (! $item) {
            $msg = 'Item not found for SPK ' . $spk_code;
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }

            return redirect()->back()->withErrors(['error' => $msg]);
        }

        // Fast SQL sum without instantiating all model rows into memory
        $currentScannedQty = (int) ScannedData::where('item_code', $item_code)
            ->where('doc_num', $doc_num)
            ->sum('quantity');

        $totalNewQty = count($labels) * $quantity;
        $scannedTotalQuantity = $currentScannedQty + $totalNewQty;

        if ($scannedTotalQuantity > $item->quantity) {
            $msg = 'All required CTN have been scanned / Quantity exceeds SO required quantity (' . $item->quantity . ')';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }

            return redirect()->back()->withErrors(['error' => $msg]);
        }

        // Tentukan dulu ini Step 1 atau Step 2 sebelum cek duplikat
        $packagingName = $request->input('packaging_name');
        $packagingLabel = $request->input('packaging_label');
        $packagingWhse = $request->input('packaging_warehouse');
        $newScans = [];

        // Cek duplikat HANYA untuk Step 1 (scan produk) via single WHERE IN query
        if (empty($packagingName)) {
            $existingDuplicate = ScannedData::where('spk_code', $spk_code)
                ->where('doc_num', $doc_num)
                ->whereIn('label', $labels)
                ->value('label');

            if ($existingDuplicate !== null) {
                $msg = 'Label "' . $existingDuplicate . '" already scanned for this SPK/SO';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg]);
                }

                return redirect()->back()->withErrors(['error' => $msg]);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($labels as $label) {
                if (empty($packagingName)) {
                    // STEP 1: Create Product Scan Record
                    $newScan = ScannedData::create([
                        'doc_num' => $doc_num,
                        'item_code' => $item_code,
                        'spk_code' => $spk_code,
                        'quantity' => $quantity,
                        'warehouse' => $warehouse,
                        'label' => $label,
                    ]);
                    $newScans[] = $newScan;

                    // Deduct from WMS Pallet if the box label exists in a pallet for this part and SPK
                    $wmsDetail = \App\Models\WmsPalletFormDetail::where('part_no', $item_code)
                        ->where('label', $label)
                        ->when(! empty($spk_code), fn ($q) => $q->where('spk_no', $spk_code))
                        ->first();
                    if ($wmsDetail) {
                        $pallet = \App\Models\WmsPalletForm::lockForUpdate()->find($wmsDetail->pallet_form_id);
                        if ($pallet) {
                            $oldPositionId = $pallet->position_id;
                            $boxQty = $wmsDetail->qty;

                            // Delete the detail record (Soft delete)
                            $wmsDetail->delete();

                            // Deduct qty and box count from the pallet header
                            $pallet->box_qty = max(0, $pallet->box_qty - 1);
                            $pallet->total_pallet_qty = max(0, $pallet->total_pallet_qty - $boxQty);

                            // Check if the pallet is now empty
                            if ($pallet->total_pallet_qty <= 0 || $pallet->box_qty <= 0) {
                                $pallet->status = 'OUT';
                                $pallet->position_id = null;
                                $pallet->box_qty = 0;
                                $pallet->total_pallet_qty = 0;
                                $notes = "Full Outbound via SO Scan (DocNum: {$doc_num}, Item: {$item_code}, Label: {$label}, Qty: {$boxQty} pcs)";
                            } else {
                                $notes = "Partial Outbound via SO Scan (DocNum: {$doc_num}, Item: {$item_code}, Label: {$label}, Qty: {$boxQty} pcs)";
                            }

                            $pallet->save();

                            // Log transaction OUT
                            $wmsService = app(\App\Services\WmsService::class);
                            $wmsService->logTransaction($pallet->pallet_id, 'OUT', $oldPositionId, $notes);

                            // Update position status
                            if ($oldPositionId) {
                                $wmsService->updatePositionStatus($oldPositionId);
                            }
                        }
                    }
                } else {
                    // STEP 2: Create Packaging Record only
                    $header = BarcodePackagingMaster::where('so_number', $doc_num)->first();
                    if ($header) {
                        $detail = BarcodePackagingDetail::create([
                            'masterId' => $header->id,
                            'noDokumen' => $header->noDokumen,
                            'partNo' => $packagingName,
                            'quantity' => null,
                            'label' => $packagingLabel ?? $label,
                            'position' => $packagingWhse,
                            'scantime' => now(),
                        ]);
                        $detail->update(['noDokumen' => $header->noDokumen . '/' . $detail->id]);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = 'Database insertion failed: ' . $e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }

            return redirect()->back()->withErrors(['error' => $msg]);
        }

        // --- AJAX RESPONSES ---
        if ($request->ajax() || $request->wantsJson()) {
            // Single aggregated query for count and sum
            $itemScans = ScannedData::where('doc_num', $doc_num)
                ->where('item_code', $item_code)
                ->selectRaw('COUNT(*) as ctn_count, COALESCE(SUM(quantity), 0) as total_qty')
                ->first();
            $count = (int) ($itemScans->ctn_count ?? 0);
            $totalQty = (int) ($itemScans->total_qty ?? 0);

            // Determine next step for focus management
            $scanMode = $request->input('scan_mode', 'OFF');
            $nextStep = 'reset';
            if ($scanMode === 'ON' && empty($packagingName)) {
                $nextStep = 'packaging';
            }

            // Cek apakah seluruh SO sudah selesai
            $rawItems = SoData::where('doc_num', $doc_num)
                ->select('item_code', 'quantity', 'packaging_quantity')
                ->get();
            $scanSummaries = ScannedData::where('doc_num', $doc_num)
                ->select('item_code', DB::raw('count(*) as count'))
                ->groupBy('item_code')
                ->pluck('count', 'item_code');

            $allFinished = $rawItems->groupBy('item_code')->every(function ($group) use ($scanSummaries) {
                $item = $group->first();
                $totalQtyPerItem = $group->sum('quantity');
                $reqBoxes = $item->packaging_quantity > 0 ? (int) ceil($totalQtyPerItem / $item->packaging_quantity) : 0;
                $currentBoxes = $scanSummaries->get($item->item_code, 0);

                return $currentBoxes >= $reqBoxes && $reqBoxes > 0;
            });

            $formattedNewScans = [];
            foreach ($newScans as $ns) {
                $formattedNewScans[] = [
                    'id' => $ns->id,
                    'quantity' => $ns->quantity,
                    'warehouse' => $ns->warehouse,
                    'label' => $ns->label,
                    'created_at' => $ns->created_at->format('Y-m-d H:i:s'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => count($labels) > 1
                    ? 'Bulk barcodes scanned successfully.'
                    : (! empty($packagingName)
                        ? 'Packaging Berhasil Disimpan.'
                        : ($scanMode === 'ON' ? 'SPK Terverifikasi. Silakan scan packaging.' : 'SPK/Label berhasil discan.')),
                'item_code' => $item_code,
                'scannedCount' => $count,
                'scannedTotalQuantity' => $totalQty,
                'newScan' => ! empty($formattedNewScans) ? $formattedNewScans[0] : null,
                'newScans' => $formattedNewScans,
                'next_step' => $nextStep,
                'allFinished' => $allFinished,
            ]);
        }

        $photo = MasterItemPhoto::where('item_code', $item_code)->first();

        return redirect()->back()->with([
            'success' => 'Barcode scanned successfully',
            'photo' => $photo ? $photo->photo_path : null,
        ]);
    }

    public function updateSoData($docNum)
    {
        // Update all SoData records with the given docNum
        SoData::where('doc_num', $docNum)
            ->update(['is_done' => true]);

        // Redirect back with a success message or to another route
        return redirect()->route('pegawai.scan')->with('status', 'All records updated successfully.');
    }

    public function import(Request $request)
    {
        set_time_limit(3000);
        // dd($request->file('import_file'));

        // $file = $request->file('import_file')->store('temp'); // Store temporarily

        // $filePath = $uploadedFile->getRealPath();

        // Store the uploaded file temporarily
        $uploadedFile = $request->file('import_file')->store('temp');
        $filePath = storage_path('app/' . $uploadedFile);

        // Read the Excel file and process the data
        $data = Excel::toArray([], $filePath)[0];

        // Remove the first row (header)
        array_shift($data);

        $uniqueRows = [];

        // Loop through the data to check for duplicates
        foreach ($data as &$row) {
            array_shift($row);
            // Format the date in column 3 (index 2) to yyyy-mm-dd
            if (isset($row[2]) && ! empty($row[2])) {
                $row[2] = \Carbon\Carbon::createFromFormat('d/m/Y', $row[2])->format('Y-m-d');
            }

            // Convert the number in column 6 (index 5) to integer (without decimal places)
            if (isset($row[5])) {
                // Remove any commas and decimals, then convert to integer
                $deliveryQuantity = str_replace(',', '', $row[5]); // Remove commas
                $deliveryQuantity = (int) number_format((float) $deliveryQuantity, 0, '.', ''); // Convert to integer
                $row[5] = $deliveryQuantity; // Update the row
            } else {
                $row[5] = 0; // Set to a default value if it's not a number
            }

            // Convert the number in column 8 (index 7) to integer (without decimal places)
            if (isset($row[7])) {
                // Remove any commas and decimals, then convert to integer
                $packagingQuantity = str_replace(',', '', $row[7]); // Remove commas
                $packagingQuantity = (int) number_format((float) $packagingQuantity, 0, '.', ''); // Convert to integer
                $row[7] = $packagingQuantity; // Update the row
            } else {
                $row[7] = 0; // Set to a default value if it's not a number
            }

            if (isset($row[9]) && ! empty($row[9])) {
                $row[9] = \Carbon\Carbon::createFromFormat('d/m/Y', $row[9])->format('Y-m-d');
            }

            if (isset($row[10]) && ! empty($row[10])) {
                $row[10] = \Carbon\Carbon::createFromFormat('d/m/Y', $row[10])->format('Y-m-d');
            }

            // Create a unique key based on columns 1 and 4 (doc_num and item_code)
            $uniqueKey = $row[0] . '-' . $row[3];

            // Check if this unique key already exists in the $uniqueRows array
            if (isset($uniqueRows[$uniqueKey])) {
                // If a duplicate is found, sum columns 6 and 8
                $uniqueRows[$uniqueKey][5] += $row[5]; // Sum the quantities
                // $uniqueRows[$uniqueKey][8] += $row[8];  Sum the packaging quantities
            } else {
                // If not a duplicate, store the row in the $uniqueRows array
                $uniqueRows[$uniqueKey] = $row;
            }
        }

        // After processing, $uniqueRows will contain only unique rows with summed quantities and packaging quantities
        $data = array_values($uniqueRows);

        // Store the processed file data into a CSV
        $excelFileName = 'sodata.csv';
        $excelFilePath = 'public/' . $excelFileName;

        $import = new SoImport;

        // Delete old data where is_finish and is_done are 0
        $import->deleteOldData();

        Excel::store(new SoDataExport($data), 'public/' . $excelFileName);
        // Import the processed file into the database
        Excel::import(new SoImport, storage_path('app/' . $excelFilePath));

        UpdateLog::updateOrCreate(
            [], // Empty array to match all records
            ['last_upload_time' => now()] // Set the current time
        );

        // Flash a success message to the session
        session()->flash('success', 'Excel file processed and imported successfully.');

        session(['last_upload_time' => now()]);

        // Redirect to the SO index route
        return redirect()->route('so.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $scan = ScannedData::findOrFail($id);
        $scan->quantity = $request->quantity;
        $scan->save();

        return back()->with('success', 'Quantity updated successfully');
    }

    public function destroy($id)
    {
        $scan = ScannedData::findOrFail($id);
        $scan->delete();

        return back()->with('success', 'Data deleted successfully');
    }

    public function storeFromSap(Request $request)
    {
        if (! is_array($request->all())) {
            return response()->json([
                'message' => 'Invalid payload format',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $payload = $request->all();

            // 1. Collect item_codes per doc_num from the SAP payload to handle deletions
            $itemsInPayload = [];
            foreach ($payload as $row) {
                if (isset($row['doc_num']) && isset($row['item_code'])) {
                    $itemsInPayload[$row['doc_num']][] = $row['item_code'];
                }
            }

            // 2. Delete local records that are no longer in SAP for these doc_nums
            foreach ($itemsInPayload as $docNum => $itemCodes) {
                SoData::where('doc_num', $docNum)
                    ->whereNotIn('item_code', $itemCodes)
                    ->delete();
            }

            // 3. Process each row (Update or Insert)
            foreach ($payload as $row) {

                $validator = Validator::make($row, [
                    'doc_num' => 'required|string',
                    'customer' => 'required|string',
                    'posting_date' => 'required|date',
                    'item_code' => 'required|string',
                    'item_name' => 'required|string',
                    'quantity' => 'required|integer',
                    'sales_uom' => 'nullable|string',
                    'packaging_quantity' => 'required|integer',
                    'sales_pack' => 'nullable|string',
                    'create_date' => 'required|date',
                    'update_date' => 'required|date',
                    'update_fulltime' => 'required|integer',
                ]);

                if ($validator->fails()) {
                    throw new \Exception($validator->errors()->first());
                }

                // Cari record, atau buat baru
                $soData = SoData::firstOrNew([
                    'doc_num' => $row['doc_num'],
                    'item_code' => $row['item_code'],
                ]);

                // Set data dari SAP
                $soData->customer = $row['customer'];
                $soData->posting_date = $row['posting_date'];
                $soData->item_name = $row['item_name'];
                $soData->quantity = $row['quantity'];
                $soData->sales_uom = $row['sales_uom'] ?? null;
                $soData->packaging_quantity = $row['packaging_quantity'];
                $soData->sales_pack = $row['sales_pack'] ?? null;
                $soData->create_date = $row['create_date'];
                $soData->update_date = $row['update_date'];
                $soData->update_fulltime = $row['update_fulltime'];

                // Hanya set 0 saat insert baru
                if (! $soData->exists) {
                    $soData->is_finish = 0;
                    $soData->is_done = 0;
                }

                $soData->save();
            }

            DB::commit();

            $responseData = [
                'message' => 'DO data processed successfully',
                'total' => count($request->all()),
            ];

            ApiLog::create([
                'api_name' => 'DO Sync',
                'method' => $request->method(),
                'endpoint' => $request->fullUrl(),
                'request_payload' => $request->all(),
                'response_payload' => $responseData,
                'status_code' => 200,
                'status' => 'success',
                'message' => 'DO data processed successfully',
            ]);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            $errorResponse = [
                'message' => 'Failed to process data',
                'error' => $e->getMessage(),
            ];

            ApiLog::create([
                'api_name' => 'DO Sync',
                'method' => $request->method(),
                'endpoint' => $request->fullUrl(),
                'request_payload' => $request->all(),
                'response_payload' => $errorResponse,
                'status_code' => 500,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            return response()->json($errorResponse, 500);
        }
    }

    public function storeSpkNew(Request $request)
    {
        if (! is_array($request->all())) {
            return response()->json([
                'message' => 'Invalid payload format',
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->all() as $row) {

                $validator = Validator::make($row, [
                    'spk_number' => 'required|string',
                    'item_code' => 'required|string',
                ]);

                if ($validator->fails()) {
                    throw new \Exception($validator->errors()->first());
                }

                SpkItemHistory::updateOrCreate(
                    [
                        'spk_number' => $row['spk_number'],
                        'item_code' => $row['item_code'],
                    ],
                    [
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();

            $responseData = [
                'message' => 'SPK data inserted successfully',
                'total' => count($request->all()),
            ];

            ApiLog::create([
                'api_name' => 'SPK Insert SAP',
                'method' => $request->method(),
                'endpoint' => $request->fullUrl(),
                'request_payload' => $request->all(),
                'response_payload' => $responseData,
                'status_code' => 200,
                'status' => 'success',
                'message' => 'SPK data inserted successfully',
            ]);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            $errorResponse = [
                'message' => 'Failed to insert SPK data',
                'error' => $e->getMessage(),
            ];

            ApiLog::create([
                'api_name' => 'SPK Insert SAP',
                'method' => $request->method(),
                'endpoint' => $request->fullUrl(),
                'request_payload' => $request->all(),
                'response_payload' => $errorResponse,
                'status_code' => 500,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);

            return response()->json($errorResponse, 500);
        }
    }

    public function dashboard(Request $request)
    {
        $selectedDate = $request->input('date', Carbon::today()->toDateString());
        $date = Carbon::parse($selectedDate);

        // --- WEEKLY HIGHLIGHTS ---
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weeklyScans = ScannedData::whereBetween('created_at', [$startOfWeek, $endOfWeek])->get();
        $weeklyTotalScans = $weeklyScans->count();
        $weeklyActiveDocsCount = $weeklyScans->pluck('doc_num')->unique()->count();

        // Hitung SO yang selesai minggu ini (status is_finish=1)
        $weeklyFinishedItems = SoData::where('is_finish', 1)
            ->whereBetween('update_date', [$startOfWeek, $endOfWeek])
            ->count();

        // --- DAILY VIEW ---
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        $selectedDateScans = ScannedData::whereBetween('created_at', [$dayStart, $dayEnd])
            ->with('soData')
            ->orderBy('created_at', 'desc')
            ->get();

        // Identifikasi unik DocNum yang ada aktivitas scanning pada TANGGAL TERPILIH
        $activeDocNums = $selectedDateScans->pluck('doc_num')->unique();

        // Hitung progress untuk setiap DocNum aktif pada tanggal tersebut
        $soProgress = [];
        foreach ($activeDocNums as $docNum) {
            $items = SoData::where('doc_num', $docNum)->get();
            if ($items->isEmpty()) {
                continue;
            }

            $totalItems = $items->count();

            // Hitung total label yang seharusnya di-scan untuk seluruh SO ini
            $totalLabelsRequired = $items->sum(function ($item) {
                return $item->packaging_quantity > 0 ? (int) ceil($item->quantity / $item->packaging_quantity) : 0;
            });

            // Hitung total label yang sudah benar-benar di-scan (akumulatif s/d sekarang)
            $totalLabelsScanned = ScannedData::where('doc_num', $docNum)->count();

            // Persentase progress
            $progressPercent = $totalLabelsRequired > 0
                ? round(($totalLabelsScanned / $totalLabelsRequired) * 100, 1)
                : 0;

            // Jumlah item yang statusnya sudah 'is_finish'
            $finishedItemsCount = $items->where('is_finish', 1)->count();

            $soProgress[$docNum] = [
                'doc_num' => $docNum,
                'customer' => $items->first()->customer ?? 'No Customer',
                'total_items' => $totalItems,
                'finished_items' => $finishedItemsCount,
                'progress' => ($progressPercent > 100) ? 100 : $progressPercent,
                'is_done' => $items->every('is_done', 1),
                'last_scan' => $selectedDateScans->where('doc_num', $docNum)->first()->created_at ?? null,
            ];
        }

        return view('store.sodashboard', compact(
            'selectedDateScans',
            'soProgress',
            'selectedDate',
            'weeklyTotalScans',
            'weeklyActiveDocsCount',
            'weeklyFinishedItems'
        ));
    }
}
