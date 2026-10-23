<?php

namespace App\Http\Controllers;

use App\Models\AlcPeMasterData;
use App\Models\BarcodePackagingDetail;
use App\Models\BarcodePackagingMaster;
use App\Models\Customer;
use App\Models\MasterDataRogPartName;
use App\Models\StoreBoxData;
use App\Models\StoreBoxDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;

class BarcodeController extends Controller
{
    //index barcode store untuk scan box datang dan box keluar
    public function index()
    {
        return view('barcodeinandout.index');
    }

    public function indexBarcode()
    {
        $datas = StoreBoxData::orderBy('part_no')->get();
        $recentLabels = StoreBoxDetail::with('boxData')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        return view('barcodeinandout.indexbarcode', compact('datas', 'recentLabels'));
    }

    // unused | index untuk create barcode yang missing (notstable)
    public function missingbarcodeindex()
    {
        $datas = MasterDataRogPartName::get();

        return view('barcodeinandout.missingbarcodeindex', compact('datas'));
    }

    // unused | function untuk create barcode yang missing (notstable)
    public function missingbarcodegenerator(Request $request)
    {
        $partNo = $request->input('partNo');
        $partDetails = preg_split('/\//', $partNo, 2);
        $partNumber = $partDetails[0];
        $partName = $partDetails[1] ?? '';

        $barcodesFolder = public_path('barcodes');
        File::cleanDirectory($barcodesFolder);

        // Retrieve and convert missing numbers to an array
        $missingNumbers = explode(',', $request->input('missingnumber'));

        // Count the number of missing numbers
        $missingNumbersCount = count($missingNumbers);

        // dd($missingNumbersCount);

        foreach ($missingNumbers as $missingNumber) {

            $barcodeData = $partNumber . "\t" . $missingNumber;

            $barcode = new DNS1D;

            // Use $spkNumber in the filename
            $filename = $partNumber . '-' . $missingNumber . '.png';

            // Save the barcode as a PNG image inside the barcodes folder
            $barcode->getBarcodePNGPath($barcodeData, 'C128', 2, 70, [0, 0, 0], false, $filename);

            // Generate the HTML for the barcode
            $barcodeHtml = $barcode->getBarcodeHTML($barcodeData, 'C128');
            // URL to the saved barcode image
            $barcodeUrl = asset('barcodes/' . $filename);
            //    dd($barcodeUrl);

            // Generate the HTML for the barcode
            $barcodeHtml = $barcode->getBarcodeHTML($barcodeData, 'C128', 2, 70);

            $barcodes[] = [
                'partno' => $partNumber,
                'partname' => $partName,
                'missingNumber' => $missingNumber,
                'barcodeHtml' => $barcodeHtml,
                'barcodeUrl' => $barcodeUrl,
            ];

        }

        return view('barcodeinandout.missingbarcode', ['barcodes' => $barcodes, 'partno' => $partNo]);

    }

    // unused |function untuk generate barcode store in and out di box
    public function generateBarcode(Request $request)
    {
        $partno = $request->partNo; // Expected format "PartNo" or "PartNo/PartName"
        $print_quantity = (int) $request->print_quantity;

        // Split partno and partname if it comes from the master list string
        $partDetails = preg_split('/\//', $partno, 2);
        $partNumber = trim($partDetails[0]);
        $partName = isset($partDetails[1]) ? trim($partDetails[1]) : '';

        // AUTO-INITIALIZATION: Check and create in StoreBoxData Part Master
        $masterBox = StoreBoxData::firstOrCreate(
            ['part_no' => $partNumber],
            ['part_name' => $partName]
        );

        // SMART CALCULATION: Find last label number from individual box master
        $lastLabel = StoreBoxDetail::where('part_no', $partNumber)->max('label') ?? 0;
        $startnum = $lastLabel + 1;

        $barcodes = [];

        for ($i = 0; $i < $print_quantity; $i++) {
            $currentLabelNo = $startnum + $i;

            // QR FORMAT: partbox [TAB] nolabel [TAB] TRIAL
            $qrData = $partNumber . "\t" . $currentLabelNo . "\tTRIAL";

            // Generate QR Code in memory as Base64 Data URI (Zero disk write)
            $barcode = new DNS2D;
            $barcodeBase64 = $barcode->getBarcodePNG($qrData, 'QRCODE', 6, 6);
            $barcodeUrl = 'data:image/png;base64,' . $barcodeBase64;

            // Store each generated box in the individual master data
            StoreBoxDetail::create([
                'part_no' => $partNumber,
                'label' => $currentLabelNo,
                'status' => 'active',
                'remark' => $request->remark ?? null,
            ]);

            $barcodes[] = [
                'partno' => $partNumber,
                'partname' => $masterBox->part_name,
                'label' => $currentLabelNo,
                'barcodeUrl' => $barcodeUrl,
                'qrData' => $qrData,
            ];
        }

        return view('barcodeinandout.barcode', [
            'barcodes' => $barcodes,
            'partno' => $partNumber,
            'quantity' => $print_quantity,
            'startnum' => $startnum,
            'endnum' => $startnum + $print_quantity - 1,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function generateLabelZebra(Request $request)
    {
        $partno = $request->partNo;
        $print_quantity = (int) $request->print_quantity;

        // Split partno and partname if it comes from the master list string
        $partDetails = preg_split('/\//', $partno, 2);
        $partNumber = trim($partDetails[0]);
        $partName = isset($partDetails[1]) ? trim($partDetails[1]) : '';

        // AUTO-INITIALIZATION: Check and create in StoreBoxData Part Master
        $masterBox = StoreBoxData::firstOrCreate(
            ['part_no' => $partNumber],
            ['part_name' => $partName]
        );

        // SMART CALCULATION: Find last label number from individual box master
        $lastLabel = StoreBoxDetail::where('part_no', $partNumber)->max('label') ?? 0;
        $startnum = $lastLabel + 1;

        $barcodes = [];

        for ($i = 0; $i < $print_quantity; $i++) {
            $currentLabelNo = $startnum + $i;
            $qrData = $partNumber . "\t" . $currentLabelNo . "\tTRIAL";

            // Generate QR Code in memory as Base64 Data URI (Zero disk write)
            $barcode = new DNS2D;
            $barcodeBase64 = $barcode->getBarcodePNG($qrData, 'QRCODE', 6, 6);
            $barcodeUrl = 'data:image/png;base64,' . $barcodeBase64;

            StoreBoxDetail::create([
                'part_no' => $partNumber,
                'label' => $currentLabelNo,
                'status' => 'active',
                'remark' => $request->remark ?? null,
            ]);

            $barcodes[] = [
                'partno' => $partNumber,
                'partname' => $masterBox->part_name,
                'label' => $currentLabelNo,
                'barcodeUrl' => $barcodeUrl,
                'qrData' => $qrData,
            ];
        }

        return view('barcodeinandout.zebra_label', [
            'barcodes' => $barcodes,
            'partno' => $partNumber,
            'quantity' => $print_quantity,
            'startnum' => $startnum,
            'endnum' => $startnum + $print_quantity - 1,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function generateLabelZebra40x15(Request $request)
    {
        $partno = $request->partNo;
        $print_quantity = (int) $request->print_quantity;

        // Split partno and partname if it comes from the master list string
        $partDetails = preg_split('/\//', $partno, 2);
        $partNumber = trim($partDetails[0]);
        $partName = isset($partDetails[1]) ? trim($partDetails[1]) : '';

        // AUTO-INITIALIZATION: Check and create in StoreBoxData Part Master
        $masterBox = StoreBoxData::firstOrCreate(
            ['part_no' => $partNumber],
            ['part_name' => $partName]
        );

        // SMART CALCULATION: Find last label number from individual box master
        $lastLabel = StoreBoxDetail::where('part_no', $partNumber)->max('label') ?? 0;
        $startnum = $lastLabel + 1;

        $barcodes = [];

        for ($i = 0; $i < $print_quantity; $i++) {
            $currentLabelNo = $startnum + $i;
            $qrData = $partNumber . "\t" . $currentLabelNo . "\tTRIAL";

            // Generate QR Code in memory as Base64 Data URI (Zero disk write)
            $barcode = new DNS2D;
            $barcodeBase64 = $barcode->getBarcodePNG($qrData, 'QRCODE', 5, 5);
            $barcodeUrl = 'data:image/png;base64,' . $barcodeBase64;

            StoreBoxDetail::create([
                'part_no' => $partNumber,
                'label' => $currentLabelNo,
                'status' => 'active',
                'remark' => $request->remark ?? null,
            ]);

            $barcodes[] = [
                'partno' => $partNumber,
                'partname' => $masterBox->part_name,
                'label' => $currentLabelNo,
                'barcodeUrl' => $barcodeUrl,
                'qrData' => $qrData,
            ];
        }

        return view('barcodeinandout.zebra_label_40x15', [
            'barcodes' => $barcodes,
            'partno' => $partNumber,
            'quantity' => $print_quantity,
            'startnum' => $startnum,
            'endnum' => $startnum + $print_quantity - 1,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function generateLabelZebra30x20(Request $request)
    {
        $partno = $request->partNo;
        $print_quantity = (int) $request->print_quantity;

        // Split partno and partname if it comes from the master list string
        $partDetails = preg_split('/\//', $partno, 2);
        $partNumber = trim($partDetails[0]);
        $partName = isset($partDetails[1]) ? trim($partDetails[1]) : '';

        // AUTO-INITIALIZATION: Check and create in StoreBoxData Part Master
        $masterBox = StoreBoxData::firstOrCreate(
            ['part_no' => $partNumber],
            ['part_name' => $partName]
        );

        // SMART CALCULATION: Find last label number from individual box master
        $lastLabel = StoreBoxDetail::where('part_no', $partNumber)->max('label') ?? 0;
        $startnum = $lastLabel + 1;

        $barcodes = [];

        for ($i = 0; $i < $print_quantity; $i++) {
            $currentLabelNo = $startnum + $i;
            $qrData = $partNumber . "\t" . $currentLabelNo . "\tTRIAL";

            // Generate QR Code in memory as Base64 Data URI (Zero disk write)
            $barcode = new DNS2D;
            $barcodeBase64 = $barcode->getBarcodePNG($qrData, 'QRCODE', 5, 5);
            $barcodeUrl = 'data:image/png;base64,' . $barcodeBase64;

            StoreBoxDetail::create([
                'part_no' => $partNumber,
                'label' => $currentLabelNo,
                'status' => 'active',
                'remark' => $request->remark ?? null,
            ]);

            $barcodes[] = [
                'partno' => $partNumber,
                'partname' => $masterBox->part_name,
                'label' => $currentLabelNo,
                'barcodeUrl' => $barcodeUrl,
                'qrData' => $qrData,
            ];
        }

        return view('barcodeinandout.zebra_label_30x20', [
            'barcodes' => $barcodes,
            'partno' => $partNumber,
            'quantity' => $print_quantity,
            'startnum' => $startnum,
            'endnum' => $startnum + $print_quantity - 1,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    //index page untuk scan box datang dan box keluar dengan memilih spesifikasi masuk atau keluar dan customer
    public function inandoutpage()
    {
        // Bersihkan draft master kosong yang ditinggalkan lebih dari 24 jam dalam 1 query cepat
        BarcodePackagingMaster::doesntHave('detailBarcode')
            ->where('created_at', '<', now()->subHours(24))
            ->delete();

        $customers = Customer::orderBy('name')->get();
        $recentMasters = BarcodePackagingMaster::withCount('detailBarcode')
            ->orderBy('created_at', 'desc')
            ->take(25)
            ->get();

        return view('barcodeinandout.inandoutpage', compact('customers', 'recentMasters'));
    }

    //function untuk process in and out box store -> generate view untuk scan barocodenya
    public function processInAndOut(Request $request)
    {
        // dd($request->all());
        $barcodePackagingMaster = new BarcodePackagingMaster;
        $tanggalScanFull = Carbon::now('Asia/Bangkok')->format('Y-m-d H:i:s');
        $barcodePackagingMaster->dateScan = $tanggalScanFull;
        $warehouseType = $request->input('warehouseType');
        $location = $request->input('location');
        $customer = $request->input('customer_name');

        // Define prefix based on warehouse type and location
        switch ($warehouseType) {
            case 'in':
                $prefix = 'IN';
                break;
            case 'out':
                $prefix = 'OUT';
                break;
            default:
                $prefix = '';
                break;
        }

        // Add location suffix based on location
        switch ($location) {
            case 'jakarta':
                $suffix = 'JKT';
                break;
            case 'karawang':
                $suffix = 'KRW';
                break;
            default:
                $suffix = '';
                break;
        }

        // Merge prefix and suffix
        $prefixSuffix = $prefix . '/' . $suffix;

        // Validate and set position based on the merged prefix and suffix
        switch ($prefixSuffix) {
            case 'IN/JKT':
                $position = 'Jakarta';
                $HeaderScan = 'IN JAKARTA';
                break;
            case 'IN/KRW':
                $position = 'Karawang';
                $HeaderScan = 'IN KARAWANG';
                break;
            case 'OUT/JKT':
                $position = 'CustomerJakarta';
                $HeaderScan = 'OUT JAKARTA';
                break;
            case 'OUT/KRW':
                $position = 'CustomerKarawang';
                $HeaderScan = 'OUT KARAWANG';
                break;
            default:
                $position = 'Unknown'; // Or handle default case as needed
                break;
        }

        $barcodePackagingMaster->tipeBarcode = $warehouseType;
        $barcodePackagingMaster->location = $location;
        $barcodePackagingMaster->customer = $customer;

        $barcodePackagingMaster->save();

        // Retrieve the id of the newly created entry
        $id = $barcodePackagingMaster->id;
        $currentDate = date('Ymd');

        // Combine prefix, suffix, and random string to form the document number
        $noDokumen = 'PKG' . '/' . $prefixSuffix . '/' . $id . '/' . $currentDate;

        $barcodePackagingMaster->noDokumen = $noDokumen;
        // Output the generated document number for testing purposes

        $barcodePackagingMaster->save();

        return view('barcodeinandout.scanpage', compact('noDokumen', 'tanggalScanFull', 'position', 'HeaderScan', 'customer'));
    }

    // function untuk memproses data dari processInAndOut masuk ke dalam database
    public function storeInAndOut(Request $request)
    {
        $data = $request->all();
        // dd($request->all());
        // dd($data);
        $docnum = $request->noDokumen;

        $position = $request->input('position');

        $master = BarcodePackagingMaster::where('noDokumen', $docnum)->first();

        $idmaster = $master->id;

        $counter = 1;
        $successfulLabels = [];
        while (isset($data['partno' . $counter])) {

            $partNo = $data['partno' . $counter];
            $label = $data['label' . $counter];

            // Check for duplicates
            // Ambil position terbaru dari kombinasi partNo + label
            $latestPosition = BarcodePackagingDetail::where('partNo', $partNo)
                ->where('label', $label)
                ->latest('created_at') // atau ->latest('id') kalau pakai id auto increment
                ->value('position');

            // Cek apakah posisi terbaru sama dengan yang sekarang di-scan
            $exists = ($latestPosition === $position);

            if (! $exists) {
                $scanTime = $data['scantime' . $counter];
                // dd($scanTime);
                // Replace koma dengan spasi dan titik dengan colon
                $scanTime = str_replace(['.', ','], [':', ' '], $scanTime);

                // Parse format waktu
                $formattedScanTime = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $scanTime)
                    ->format('Y-m-d H:i:s');

                // Cek apakah partNo valid di tabel store_box_data
                $isValidPart = \App\Models\StoreBoxData::where('part_no', $partNo)->exists();

                if ($isValidPart) {
                    BarcodePackagingDetail::create([
                        'masterId' => $idmaster,
                        'noDokumen' => $data['noDokumen'],
                        'partNo' => $partNo,
                        'quantity' => null,
                        'label' => $label,
                        'position' => $data['position'],
                        'scantime' => $formattedScanTime,
                    ]);

                    $successfulLabels[] = $label;
                }
            }
            $counter++;
        }

        if (count($successfulLabels) > 0) {
            $labelsList = implode(', ', $successfulLabels);
            $successMessage = 'BERHASIL SCAN ' . count($successfulLabels) . ' DATA dengan label: ' . $labelsList;
        } else {
            $successMessage = 'Tidak ada data baru yang berhasil discan (mungkin sudah ada atau part number tidak valid)';
        }

        return redirect()->route('inandout.index')->with('success', $successMessage);
    }

    // view summary barcode store in and out
    public function barcodelist()
    {
        $items = BarcodePackagingMaster::with('detailbarcode')
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];

        foreach ($items as $item) {
            $masterId = $item->id;
            $dateScan = $item->dateScan;
            $noDokumen = $item->noDokumen;
            $finishDokumen = $item->finishDokumen;

            // Initialize the structure for this master record
            $result[$masterId] = [
                'dateScan' => $dateScan,
                'noDokumen' => $noDokumen,
                'tipeBarcode' => $item->tipeBarcode, // Add tipeBarcode here
                'location' => $item->location,
                'customer' => $item->customer,
            ];

            // Initialize arrays for noDokumen and finishDokumen if not already set
            if (! isset($result[$masterId][$noDokumen])) {
                $result[$masterId][$noDokumen] = [];
            }

            // Process detail records for noDokumen
            foreach ($item->detailbarcode as $detail) {
                if ($detail->noDokumen === $noDokumen) {
                    $result[$masterId][$noDokumen][] = [
                        'partNo' => $detail->partNo,
                        'quantity' => $detail->quantity,
                        'label' => $detail->label,
                        'scantime' => $detail->scantime,
                        'position' => $detail->position,
                    ];
                }
            }

        }

        // Convert associative array to simple array
        $result = array_values($result);

        return view('barcodeinandout.listfinishbarcode', compact('result'));
    }

    // function untuk filter view summary barcode store in and out
    public function filter(Request $request)
    {
        $query = BarcodePackagingMaster::with('detailbarcode');

        if ($request->filled('tipeBarcode')) {
            $query->where('tipeBarcode', $request->tipeBarcode);
        }

        if ($request->filled('location')) {
            $query->where('location', $request->location);
        }

        if ($request->filled('dateScan')) {
            $query->whereDate('dateScan', $request->dateScan);
        }

        $items = $query->orderBy('created_at', 'desc')->get();

        // Proses data sama seperti di barcodelist()
        $result = [];
        foreach ($items as $item) {
            $masterId = $item->id;
            $dateScan = $item->dateScan;
            $noDokumen = $item->noDokumen;
            $finishDokumen = $item->finishDokumen;

            $result[$masterId] = [
                'dateScan' => $dateScan,
                'noDokumen' => $noDokumen,
                'tipeBarcode' => $item->tipeBarcode,
                'location' => $item->location,
                'customer' => $item->customer,
            ];

            if (! isset($result[$masterId][$noDokumen])) {
                $result[$masterId][$noDokumen] = [];
            }

            foreach ($item->detailbarcode as $detail) {
                if ($detail->noDokumen === $noDokumen) {
                    $result[$masterId][$noDokumen][] = [
                        'partNo' => $detail->partNo,
                        'quantity' => $detail->quantity,
                        'label' => $detail->label,
                        'scantime' => $detail->scantime,
                        'position' => $detail->position,
                    ];
                }
            }
        }

        $result = array_values($result);

        return view('barcodeinandout.partials.barcode_table', compact('result'));
    }

    // view summary barcode store in and out(2)
    public function latestitemdetails(Request $request)
    {
        $partNumbers = BarcodePackagingDetail::select('partNo')->distinct()->orderBy('partNo')->get();

        $query = DB::table('barcode_packaging_details as d')
            ->joinSub(
                DB::table('barcode_packaging_details')
                    ->select('partNo', 'label', DB::raw('MAX(id) as max_id'))
                    ->where('label', '!=', 'ADJUST')
                    ->groupBy('partNo', 'label'),
                'latest',
                'd.id',
                '=',
                'latest.max_id'
            )
            ->leftJoin('barcode_packaging_master as m', 'd.masterId', '=', 'm.id')
            ->select('d.partNo', 'd.label', 'd.position', 'd.scantime', 'd.noDokumen', 'm.customer');

        if ($request->filled('partNo')) {
            $query->where('d.partNo', $request->input('partNo'));
        }

        if ($request->filled('scantime')) {
            $query->whereDate('d.scantime', $request->input('scantime'));
        }

        if ($request->filled('position')) {
            $query->where('d.position', $request->input('position'));
        }

        $sortedItems = $query->orderBy('d.partNo')->orderBy('d.label')->paginate(50)->withQueryString();

        return view('barcodeinandout.latestbarcodeitem', compact('sortedItems', 'partNumbers'));
    }

    // view summary barcode store in and out (3)
    public function historybarcodelist(Request $request)
    {
        $query = BarcodePackagingMaster::with(['detailbarcode' => function ($query) use ($request) {
            if ($request->has('partNo') && $request->partNo != '') {
                $query->where('partNo', $request->partNo);
            }
        }]);

        // Apply filters
        if ($request->has('datescan') && $request->datescan != '') {
            $query->whereDate('dateScan', $request->datescan);
        }

        if ($request->has('barcode_type') && $request->barcode_type != '') {
            $query->where('tipeBarcode', $request->barcode_type);
        }

        if ($request->has('location') && $request->location != '') {
            $query->where('location', $request->location);
        }

        $items = $query->get();

        $distinctPartNos = BarcodePackagingDetail::select('partNo')->distinct()->get();

        return view('barcodeinandout.historylisttable', compact('items', 'distinctPartNos'));
    }

    // view stock all barcode store in and out (4)
    public function stockall($location = 'Jakarta')
    {
        // Define position mappings based on location
        $positionMapping = [
            'Jakarta' => ['position' => 'Jakarta', 'customerPosition' => 'CustomerJakarta'],
            'Karawang' => ['position' => 'Karawang', 'customerPosition' => 'CustomerKarawang'],
        ];

        // Check if the location exists in the mapping
        if (! array_key_exists($location, $positionMapping)) {
            abort(404, 'Location not found');
        }

        // Retrieve data based on location mapping
        $locationData = $positionMapping[$location];
        $datas = BarcodePackagingDetail::where('position', $locationData['position'])
            ->orWhere('position', $locationData['customerPosition'])
            ->get();

        $names = MasterDataRogPartName::get();

        $partNos = $datas->pluck('partNo')->unique();
        $balances = [];

        foreach ($partNos as $partNo) {
            // Calculate quantities based on location
            $locationQuantity = $datas->where('partNo', $partNo)
                ->where('position', $locationData['position'])
                ->count();

            $customerQuantity = $datas->where('partNo', $partNo)
                ->where('position', $locationData['customerPosition'])
                ->count();

            $balance = max($locationQuantity - $customerQuantity, 0);

            // Find the corresponding name data and extract the description
            $nameData = $names->first(function ($item) use ($partNo) {
                return strpos($item->name, "{$partNo}/") === 0;
            });

            // Extract description or provide default message
            $description = $nameData ? explode('/', $nameData->name, 2)[1] : 'No description available';

            // Add the partNo, description, and balance to the balances array
            $balances[] = [
                'partNo' => $partNo,
                'description' => $description,
                'balance' => $balance,
            ];
        }

        return view('barcodeinandout.stockallbarcode', compact('balances', 'location'));
    }

    // function untuk add customer barcode store in and out
    public function addCustomer()
    {
        $customers = Customer::latest()->get();

        return view('barcodeinandout.customeradd', compact('customers'));
    }

    // function untuk store customer barcode store in and out
    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Customer::create([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Customer berhasil ditambahkan!');
    }

    // function untuk destroy customer barcode store in and out
    public function destroyCustomer($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->back()->with('success', 'Customer berhasil dihapus!');
    }

    // function untuk summary dashboard barcode store in and out
    public function summaryDashboard()
    {
        // 1. Ambil master box data
        $storeBoxes = StoreBoxData::orderBy('part_no')->get();
        $boxNames = $storeBoxes->pluck('part_name', 'part_no')->toArray();

        // 2. Query snapshot posisi TERAKHIR tiap box via Subquery DB (Zero PHP memory bottleneck)
        $latestPositions = DB::table('barcode_packaging_details as d')
            ->joinSub(
                DB::table('barcode_packaging_details')
                    ->select('partNo', 'label', DB::raw('MAX(id) as max_id'))
                    ->where('label', '!=', 'ADJUST')
                    ->groupBy('partNo', 'label'),
                'latest',
                'd.id',
                '=',
                'latest.max_id'
            )
            ->leftJoin('barcode_packaging_master as m', 'd.masterId', '=', 'm.id')
            ->select('d.partNo', 'd.label', 'd.position', 'd.scantime', 'd.noDokumen', 'm.customer')
            ->get();

        $summaryByPart = [];
        $customerBalances = [];
        $totalDaijo = 0;
        $totalKiic = 0;
        $totalCustomer = 0;

        foreach ($latestPositions as $row) {
            $pos = strtolower(trim($row->position ?? ''));
            $part = $row->partNo;

            if (! isset($summaryByPart[$part])) {
                $summaryByPart[$part] = [
                    'part_no' => $part,
                    'part_name' => $boxNames[$part] ?? '-',
                    'quantity_daijo' => 0,
                    'quantity_kiic' => 0,
                    'quantity_customer' => 0,
                    'total' => 0,
                    'details' => [],
                ];
            }

            $isDaijo = ($pos === 'jakarta');
            $isKiic = ($pos === 'karawang');
            $isCust = in_array($pos, ['customerjakarta', 'customerkarawang', 'customer']);

            if ($isDaijo) {
                $summaryByPart[$part]['quantity_daijo']++;
                $totalDaijo++;
            } elseif ($isKiic) {
                $summaryByPart[$part]['quantity_kiic']++;
                $totalKiic++;
            } elseif ($isCust) {
                $summaryByPart[$part]['quantity_customer']++;
                $totalCustomer++;

                $custName = $row->customer ?: 'Customer Lain';
                if (! isset($customerBalances[$custName])) {
                    $customerBalances[$custName] = [
                        'customer' => $custName,
                        'total_boxes' => 0,
                        'parts' => [],
                    ];
                }
                $customerBalances[$custName]['total_boxes']++;
                $customerBalances[$custName]['parts'][$part] = ($customerBalances[$custName]['parts'][$part] ?? 0) + 1;
            }

            $summaryByPart[$part]['total']++;
            $summaryByPart[$part]['details'][] = [
                'label' => $row->label,
                'position' => $row->position,
                'last_transaction' => $row->scantime,
                'quantity' => 1,
                'customer' => $row->customer,
                'no_dokumen' => $row->noDokumen,
            ];
        }

        // Pastikan semua part di store_box_data tetap tampil meski belum ada record scan
        foreach ($storeBoxes as $box) {
            if (! isset($summaryByPart[$box->part_no])) {
                $summaryByPart[$box->part_no] = [
                    'part_no' => $box->part_no,
                    'part_name' => $box->part_name ?: '-',
                    'quantity_daijo' => 0,
                    'quantity_kiic' => 0,
                    'quantity_customer' => 0,
                    'total' => 0,
                    'details' => [],
                ];
            }
        }

        ksort($summaryByPart);
        $summaryData = array_values($summaryByPart);

        // Sort customer balances descending
        uasort($customerBalances, function ($a, $b) {
            return $b['total_boxes'] <=> $a['total_boxes'];
        });

        $kpi = [
            'total_types' => count($summaryData),
            'total_physical' => $totalDaijo + $totalKiic + $totalCustomer,
            'total_daijo' => $totalDaijo,
            'total_kiic' => $totalKiic,
            'total_customer' => $totalCustomer,
        ];

        return view('barcode.store_summary', [
            'summaryData' => $summaryData,
            'customerBalances' => array_values($customerBalances),
            'kpi' => $kpi,
            'latestPositions' => $latestPositions,
        ]);
    }

    public function getLabelHistory(Request $request)
    {
        $partNo = $request->input('partNo');
        $label = $request->input('label');

        $history = BarcodePackagingDetail::where('partNo', $partNo)
            ->where('label', $label)
            ->orderBy('scantime', 'desc')
            ->get(['scantime', 'position', 'label', 'noDokumen']);

        return response()->json($history);
    }

    // section barcode plastic injection

    // view untuk pilih part dan no label barcode alc (plastic mba intan) untuk customer yanfeng
    public function alcindex()
    {
        $items = AlcPeMasterData::orderBy('part_code')->get();

        return view('barcode.alcindex', compact('items'));
    }

    // function untuk generate label yangeng 40x15 (plastic mba intan) untuk customer yanfeng
    public function generateLabelYangeng40x15(Request $request)
    {
        $RS = chr(30); // ␝
        $GS = chr(29); // ␟
        $EOT = chr(4);  // ␄

        // 2️⃣ Ambil data part yang dipilih
        $part = AlcPeMasterData::where('part_code', $request->part_code)->firstOrFail();
        // dd($part);
        $truepartNumber = $part->part_code;
        $sequenceCode = $part->alc_code;
        $projectCode = $part->project_code;
        // dd($sequenceCode);
        // dd($projectCode);

        $identifier1 = 'PEA';
        if ($request->filled('production_date')) {
            // production_date format: YYYY-MM-DD
            $date1 = \Carbon\Carbon::createFromFormat('Y-m-d', $request->production_date)
                ->format('ymd'); // jadi yymmdd
        } else {
            $date1 = date('ymd'); // default: tanggal hari ini
        } // Format tanggal saat ini ddmmyy
        $sequence1 = '0000'; // Mulai dari 0000
        $baseIdentifier = $identifier1 . $date1 . $sequence1;
        // dd($baseIdentifier);

        $labels = collect();

        $code = substr($baseIdentifier, 0, 3); // PEA
        $date = substr($baseIdentifier, 3, 6); // 251009
        $sequence = substr($baseIdentifier, 9); // 0000

        $sequenceLength = strlen($sequence); // simpan panjang sequence (4 digit)
        $startSequence = (int) $sequence; // convert ke integer

        $supplierCode = 'HAWX';
        // $identifier = 'PEA2510090000';
        $partNumber = $part->part_code;
        $truepartNumber = str_replace('-', '', $part->part_code);
        $sequenceCode = $part->alc_code;
        $engineeringOrderNumber = '';
        $trackingCode1 = $date . 'Y111@QAD:';
        $trackingCode2 = $part->qad;
        $trackingCode = $trackingCode1 . $trackingCode2;
        $specificInfo = '12345'; // optional
        $initialProductClassification = 'N'; // optional
        $businessUnit = 'ABC'; // optional

        //tambahin project code di sebelah PEA
        // Bersihin Part number dari -

        // Loop untuk generate multiple labels
        $labels = [];
        for ($i = $request->label_start; $i <= $request->label_end; $i++) {
            $currentSequence = $startSequence + $i;
            $identifier = $code . $date . str_pad($currentSequence, $sequenceLength, '0', STR_PAD_LEFT);

            // Format sesuai standar ISO/IEC 15434
            $dataMatrixString = '[)>'
            . $RS . '06'
            . $GS . 'V' . $supplierCode
            // . $GS . "E" . $engineeringOrderNumber
            . $GS . 'P' . $truepartNumber
            . $GS . 'S' . $sequenceCode
            . $GS . 'T' . $trackingCode
            . $GS . '1A' . $specificInfo
            . $GS . 'M' . $initialProductClassification
            . $GS . 'C' . $businessUnit
            . $GS . $RS . $EOT;

            // Generate DataMatrix
            $barcode = new DNS2D;
            $image = $barcode->getBarcodePNG($dataMatrixString, 'DATAMATRIX');
            // dd($dataMatrixString);

            $labels[] = [
                'identifier' => $identifier,
                'supplierCode' => $supplierCode,
                'sequenceCode' => $sequenceCode,
                'partNumber' => $partNumber,
                'projectCode' => $projectCode,
                'image' => $image,
            ];
        }

        return view('labelyanfeng40x15', compact('image', 'engineeringOrderNumber', 'sequenceCode', 'supplierCode', 'partNumber', 'trackingCode', 'identifier', 'labels'));
    }

    // function untuk generate label yangeng 50x20 (plastic mba intan) untuk customer yanfeng
    public function generateLabelYangeng50x20(Request $request)
    {
        $RS = chr(30); // ␝
        $GS = chr(29); // ␟
        $EOT = chr(4);  // ␄

        // 2️⃣ Ambil data part yang dipilih
        $part = AlcPeMasterData::where('part_code', $request->part_code)->firstOrFail();
        // dd($part);
        $truepartNumber = $part->part_code;
        $sequenceCode = $part->alc_code;
        $projectCode = $part->project_code;
        // dd($sequenceCode);
        // dd($projectCode);

        $identifier1 = 'PEA';
        if ($request->filled('production_date')) {
            // production_date format: YYYY-MM-DD
            $date1 = \Carbon\Carbon::createFromFormat('Y-m-d', $request->production_date)
                ->format('ymd'); // jadi yymmdd
        } else {
            $date1 = date('ymd'); // default: tanggal hari ini
        } // Format tanggal saat ini ddmmyy
        $sequence1 = '0000'; // Mulai dari 0000
        $baseIdentifier = $identifier1 . $date1 . $sequence1;
        // dd($baseIdentifier);

        $labels = collect();

        $code = substr($baseIdentifier, 0, 3); // PEA
        $date = substr($baseIdentifier, 3, 6); // 251009
        $sequence = substr($baseIdentifier, 9); // 0000

        $sequenceLength = strlen($sequence); // simpan panjang sequence (4 digit)
        $startSequence = (int) $sequence; // convert ke integer

        $supplierCode = 'HAWX';
        // $identifier = 'PEA2510090000';
        $partNumber = $part->part_code;
        $truepartNumber = str_replace('-', '', $part->part_code);
        $sequenceCode = $part->alc_code;
        $engineeringOrderNumber = '';
        $trackingCode1 = $date . 'Y111@QAD:';
        $trackingCode2 = $part->qad;
        $trackingCode = $trackingCode1 . $trackingCode2;
        $specificInfo = '12345'; // optional
        $initialProductClassification = 'N'; // optional
        $businessUnit = 'ABC'; // optional

        //tambahin project code di sebelah PEA
        // Bersihin Part number dari -

        // Loop untuk generate multiple labels
        $labels = [];
        for ($i = $request->label_start; $i <= $request->label_end; $i++) {
            $currentSequence = $startSequence + $i;
            $identifier = $code . $date . str_pad($currentSequence, $sequenceLength, '0', STR_PAD_LEFT);

            // Format sesuai standar ISO/IEC 15434
            $dataMatrixString = '[)>'
            . $RS . '06'
            . $GS . 'V' . $supplierCode
            // . $GS . "E" . $engineeringOrderNumber
            . $GS . 'P' . $truepartNumber
            . $GS . 'S' . $sequenceCode
            . $GS . 'T' . $trackingCode
            . $GS . '1A' . $specificInfo
            . $GS . 'M' . $initialProductClassification
            . $GS . 'C' . $businessUnit
            . $GS . $RS . $EOT;

            // Generate DataMatrix
            $barcode = new DNS2D;
            $image = $barcode->getBarcodePNG($dataMatrixString, 'DATAMATRIX');
            // dd($dataMatrixString);

            $labels[] = [
                'identifier' => $identifier,
                'supplierCode' => $supplierCode,
                'sequenceCode' => $sequenceCode,
                'partNumber' => $partNumber,
                'projectCode' => $projectCode,
                'image' => $image,
            ];
        }

        return view('labelyanfeng50x20', compact('image', 'engineeringOrderNumber', 'sequenceCode', 'supplierCode', 'partNumber', 'trackingCode', 'identifier', 'labels'));
    }

    // function untuk generate label yangeng 25x10 (plastic mba intan) untuk customer yanfeng
    public function generateLabelYangeng25x10(Request $request)
    {
        $RS = chr(30); // ␝
        $GS = chr(29); // ␟
        $EOT = chr(4);  // ␄

        // 2️⃣ Ambil data part yang dipilih
        $part = AlcPeMasterData::where('part_code', $request->part_code)->firstOrFail();
        // dd($part);
        $truepartNumber = $part->part_code;
        $sequenceCode = $part->alc_code;
        $projectCode = $part->project_code;
        // dd($sequenceCode);
        // dd($projectCode);

        $identifier1 = 'PEA';
        if ($request->filled('production_date')) {
            // production_date format: YYYY-MM-DD
            $date1 = \Carbon\Carbon::createFromFormat('Y-m-d', $request->production_date)
                ->format('ymd'); // jadi yymmdd
        } else {
            $date1 = date('ymd'); // default: tanggal hari ini
        } // Format tanggal saat ini ddmmyy
        $sequence1 = '0000'; // Mulai dari 0000
        $baseIdentifier = $identifier1 . $date1 . $sequence1;
        // dd($baseIdentifier);

        $labels = collect();

        $code = substr($baseIdentifier, 0, 3); // PEA
        $date = substr($baseIdentifier, 3, 6); // 251009
        $sequence = substr($baseIdentifier, 9); // 0000

        $sequenceLength = strlen($sequence); // simpan panjang sequence (4 digit)
        $startSequence = (int) $sequence; // convert ke integer

        $supplierCode = 'HAWX';
        // $identifier = 'PEA2510090000';
        $partNumber = $part->part_code;
        $truepartNumber = str_replace('-', '', $part->part_code);
        $sequenceCode = $part->alc_code;
        $engineeringOrderNumber = '';
        $trackingCode1 = $date . 'Y111@QAD:';
        $trackingCode2 = $part->qad;
        $trackingCode = $trackingCode1 . $trackingCode2;
        $specificInfo = '12345'; // optional
        $initialProductClassification = 'N'; // optional
        $businessUnit = 'ABC'; // optional

        //tambahin project code di sebelah PEA
        // Bersihin Part number dari -

        // Loop untuk generate multiple labels
        $labels = [];
        for ($i = $request->label_start; $i <= $request->label_end; $i++) {
            $currentSequence = $startSequence + $i;
            $identifier = $code . $date . str_pad($currentSequence, $sequenceLength, '0', STR_PAD_LEFT);

            // Format sesuai standar ISO/IEC 15434
            $dataMatrixString = '[)>'
            . $RS . '06'
            . $GS . 'V' . $supplierCode
            // . $GS . "E" . $engineeringOrderNumber
            . $GS . 'P' . $truepartNumber
            . $GS . 'S' . $sequenceCode
            . $GS . 'T' . $trackingCode
            . $GS . '1A' . $specificInfo
            . $GS . 'M' . $initialProductClassification
            . $GS . 'C' . $businessUnit
            . $GS . $RS . $EOT;

            // Generate DataMatrix
            $barcode = new DNS2D;
            $image = $barcode->getBarcodePNG($dataMatrixString, 'DATAMATRIX');
            // dd($dataMatrixString);

            $labels[] = [
                'identifier' => $identifier,
                'supplierCode' => $supplierCode,
                'sequenceCode' => $sequenceCode,
                'partNumber' => $partNumber,
                'projectCode' => $projectCode,
                'image' => $image,
            ];
        }

        return view('labelyanfeng25x10', compact('image', 'engineeringOrderNumber', 'sequenceCode', 'supplierCode', 'partNumber', 'trackingCode', 'identifier', 'labels'));
    }

    // function untuk generate label yangeng 50x35 (plastic mba intan) untuk customer yanfeng
    public function generateLabelYangeng50x35(Request $request)
    {
        $RS = chr(30); // ␝
        $GS = chr(29); // ␟
        $EOT = chr(4);  // ␄

        // 2️⃣ Ambil data part yang dipilih
        $part = AlcPeMasterData::where('part_code', $request->part_code)->firstOrFail();
        // dd($part);
        $truepartNumber = $part->part_code;
        $sequenceCode = $part->alc_code;
        $projectCode = $part->project_code;
        // dd($sequenceCode);
        // dd($projectCode);

        $identifier1 = 'PEA';
        if ($request->filled('production_date')) {
            // production_date format: YYYY-MM-DD
            $date1 = \Carbon\Carbon::createFromFormat('Y-m-d', $request->production_date)
                ->format('ymd'); // jadi yymmdd
        } else {
            $date1 = date('ymd'); // default: tanggal hari ini
        } // Format tanggal saat ini ddmmyy
        $sequence1 = '0000'; // Mulai dari 0000
        $baseIdentifier = $identifier1 . $date1 . $sequence1;
        // dd($baseIdentifier);

        $labels = collect();

        $code = substr($baseIdentifier, 0, 3); // PEA
        $date = substr($baseIdentifier, 3, 6); // 251009
        $sequence = substr($baseIdentifier, 9); // 0000

        $sequenceLength = strlen($sequence); // simpan panjang sequence (4 digit)
        $startSequence = (int) $sequence; // convert ke integer

        $supplierCode = 'HAWX';
        // $identifier = 'PEA2510090000';
        $partNumber = $part->part_code;
        $truepartNumber = str_replace('-', '', $part->part_code);
        $sequenceCode = $part->alc_code;
        $engineeringOrderNumber = '';
        $trackingCode1 = $date . 'Y111@QAD:';
        $trackingCode2 = $part->qad;
        $trackingCode = $trackingCode1 . $trackingCode2;
        $specificInfo = '12345'; // optional
        $initialProductClassification = 'N'; // optional
        $businessUnit = 'ABC'; // optional

        //tambahin project code di sebelah PEA
        // Bersihin Part number dari -

        // Loop untuk generate multiple labels
        $labels = [];
        for ($i = $request->label_start; $i <= $request->label_end; $i++) {
            $currentSequence = $startSequence + $i;
            $identifier = $code . $date . str_pad($currentSequence, $sequenceLength, '0', STR_PAD_LEFT);

            // Format sesuai standar ISO/IEC 15434
            $dataMatrixString = '[)>'
            . $RS . '06'
            . $GS . 'V' . $supplierCode
            // . $GS . "E" . $engineeringOrderNumber
            . $GS . 'P' . $truepartNumber
            . $GS . 'S' . $sequenceCode
            . $GS . 'T' . $trackingCode
            . $GS . '1A' . $specificInfo
            . $GS . 'M' . $initialProductClassification
            . $GS . 'C' . $businessUnit
            . $GS . $RS . $EOT;

            // Generate DataMatrix
            $barcode = new DNS2D;
            $image = $barcode->getBarcodePNG($dataMatrixString, 'DATAMATRIX');
            // dd($dataMatrixString);

            $labels[] = [
                'identifier' => $identifier,
                'supplierCode' => $supplierCode,
                'sequenceCode' => $sequenceCode,
                'partNumber' => $partNumber,
                'projectCode' => $projectCode,
                'image' => $image,
            ];
        }

        return view('labelyanfeng50x35', compact('image', 'engineeringOrderNumber', 'sequenceCode', 'supplierCode', 'partNumber', 'trackingCode', 'identifier', 'labels'));
    }

    // Testing | Semi Used |function untuk generate all label yangeng (plastic mba intan) untuk customer yanfeng
    public function generateAllLabelYangeng(Request $request)
    {
        $RS = chr(30); // ␝
        $GS = chr(29); // ␟
        $EOT = chr(4);  // ␄

        // ✅ Ambil semua part
        // $parts = AlcPeMasterData::all();

        // $parts = AlcPeMasterData::where('ukuran_label', '!=', '25 x 10')->get();

        // $parts = AlcPeMasterData::where('ukuran_label', '25 x 10')->get();

        $partCodes = [
            '83930-I6000NNB',
            '83930-I6000PPX',
            '83940-I6000NNB',
            '83940-I6000PPX',
        ];

        $parts = AlcPeMasterData::where('ukuran_label', '!=', '25 x 10')
            ->whereIn('part_code', $partCodes)
            ->get();

        if ($parts->isEmpty()) {
            return back()->with('error', 'Tidak ada data part di database!');
        }

        $labels = [];

        // Base config
        $identifier1 = 'PEA';
        if ($request->filled('production_date')) {
            // production_date format: YYYY-MM-DD
            $date1 = \Carbon\Carbon::createFromFormat('Y-m-d', $request->production_date)
                ->format('ymd'); // jadi yymmdd
        } else {
            $date1 = date('ymd'); // default: tanggal hari ini
        } // Format tanggal saat ini ddmmyy
        $supplierCode = 'HAWX';
        $specificInfo = '12345';
        $initialProductClassification = 'N';
        $businessUnit = 'ABC';
        $date = $date1;

        // ✅ Loop semua part
        foreach ($parts as $part) {
            $truepartNumber = str_replace('-', '', $part->part_code);
            $sequenceCode = $part->alc_code;
            $projectCode = $part->project_code;
            $partNumber = $part->part_code;
            $trackingCode = $date . 'Y111@QAD:' . $part->qad;

            // 🔹 Buat 6 label untuk tiap part
            for ($i = 1; $i <= 4; $i++) {
                // Format urutan label 0001, 0002, dst
                $sequenceFormatted = str_pad($i, 4, '0', STR_PAD_LEFT);
                $identifier = $identifier1 . $date1 . $sequenceFormatted;

                // Format GS1 DataMatrix
                $dataMatrixString = '[)>'
                    . $RS . '06'
                    . $GS . 'V' . $supplierCode
                    . $GS . 'P' . $truepartNumber
                    . $GS . 'S' . $sequenceCode
                    . $GS . 'T' . $trackingCode
                    . $GS . '1A' . $specificInfo
                    . $GS . 'M' . $initialProductClassification
                    . $GS . 'C' . $businessUnit
                    . $GS . $RS . $EOT;

                // Generate DataMatrix
                $barcode = new DNS2D;
                $image = $barcode->getBarcodePNG($dataMatrixString, 'DATAMATRIX');

                $labels[] = [
                    'identifier' => $identifier,
                    'supplierCode' => $supplierCode,
                    'sequenceCode' => $sequenceCode,
                    'partNumber' => $partNumber,
                    'projectCode' => $projectCode,
                    'image' => $image,
                    'part_id' => $part->id,
                    'label_no' => $i, // Tambahan: urutan label 1–6
                ];
            }
        }

        return view('alllabelyanfeng', compact('labels'));
    }

    // ============================================
    // MASTER BOX DATA CRUD (PART MASTER)
    // ============================================

    public function indexStoreBoxData()
    {
        $boxes = StoreBoxData::withCount('details')->orderBy('part_no')->get();

        return view('barcode.store_box_index', compact('boxes'));
    }

    public function storeStoreBoxData(Request $request)
    {
        $request->validate([
            'part_no' => 'required|string|unique:store_box_data,part_no',
            'part_name' => 'nullable|string',
        ]);

        StoreBoxData::create($request->all());

        return redirect()->back()->with('success', 'Master Box berhasil ditambahkan!');
    }

    public function updateStoreBoxData(Request $request, $id)
    {
        $request->validate([
            'part_no' => 'required|string|unique:store_box_data,part_no,' . $id,
            'part_name' => 'nullable|string',
        ]);

        $box = StoreBoxData::findOrFail($id);
        $box->update($request->all());

        return redirect()->back()->with('success', 'Master Box berhasil diperbarui!');
    }

    public function destroyStoreBoxData($id)
    {
        $box = StoreBoxData::findOrFail($id);
        $box->delete();

        return redirect()->back()->with('success', 'Master Box berhasil dihapus!');
    }

    // ============================================
    // BARCODE HISTORY & REPRINT
    // ============================================

    public function historyStoreBoxDetails()
    {
        return view('barcode.store_box_history');
    }

    public function indexStoreBoxDetail(Request $request)
    {
        $query = StoreBoxDetail::with('boxData');

        if ($request->filled('part_no')) {
            $query->where('part_no', 'like', '%' . $request->part_no . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $details = $query->orderBy('part_no', 'asc')->orderBy('label', 'asc')->paginate(50)->withQueryString();

        return view('barcode.store_box_detail', compact('details'));
    }

    public function updateStoreBoxDetail(Request $request, $id)
    {
        $detail = StoreBoxDetail::findOrFail($id);

        $request->validate([
            'status' => 'required|in:active,non-active',
            'remark' => 'nullable|string|max:255',
        ]);

        $detail->update([
            'status' => $request->status,
            'remark' => $request->remark,
        ]);

        return redirect()->back()->with('success', 'Detail box berhasi diupdate!');
    }

    public function reprintBarcode(Request $request)
    {
        $ids = $request->ids;

        if (! $ids || count($ids) == 0) {
            return redirect()->back()->with('error', 'Pilih barcode yang ingin di-print ulang!');
        }

        $records = StoreBoxDetail::whereIn('id', $ids)->orderBy('part_no')->orderBy('label')->get();
        $barcodes = [];

        foreach ($records as $record) {
            $partNumber = $record->part_no;
            $currentLabelNo = $record->label;
            $qrData = $partNumber . "\t" . $currentLabelNo . "\tTRIAL";

            $barcodeObj = new DNS2D;
            $barcodeBase64 = $barcodeObj->getBarcodePNG($qrData, 'QRCODE', 6, 6);
            $barcodeUrl = 'data:image/png;base64,' . $barcodeBase64;

            // Fetch master part name for display
            $master = StoreBoxData::where('part_no', $partNumber)->first();

            $barcodes[] = [
                'partno' => $partNumber,
                'partname' => $master->part_name ?? '-',
                'label' => $currentLabelNo,
                'barcodeUrl' => $barcodeUrl,
                'qrData' => $qrData,
            ];
        }

        if ($request->format == 'zebra30x20') {
            $view = 'barcodeinandout.zebra_label_30x20';
        } elseif ($request->format == 'zebra40x15') {
            $view = 'barcodeinandout.zebra_label_40x15';
        } elseif ($request->format == 'zebra') {
            $view = 'barcodeinandout.zebra_label';
        } else {
            $view = 'barcodeinandout.barcode';
        }

        return view($view, [
            'barcodes' => $barcodes,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
