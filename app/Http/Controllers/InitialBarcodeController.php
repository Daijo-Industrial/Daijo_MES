<?php

namespace App\Http\Controllers;

use App\Models\CustomBarcodeLog;
use App\Models\MasterListItem;
use App\Models\SpkItemHistory;
use Illuminate\Http\Request;
use Milon\Barcode\DNS1D;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class InitialBarcodeController extends Controller
{
    public function index()
    {
        $tipeMesins = MasterListItem::distinct()->pluck('tipe_mesin');
        // dd($tipeMesins);

        return view('barcode.index', compact('tipeMesins'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'tipe_mesin' => 'required',
        ]);

        // Fetch items with the selected tipe_mesin
        $items = MasterListItem::where('tipe_mesin', $request->tipe_mesin)->get();
        $labelCount = 1;

        return view('barcode.generate', compact('items', 'labelCount'));
    }

    public function manualgenerate()
    {
        return view('barcode.generatemanualbarcode');
    }

    public function generateBarcode(Request $request)
    {
        $request->validate([
            'item_code' => 'required|string',
            'quantity' => 'required|integer',
            'warehouse' => 'required|string',
            'label' => 'required|integer',
        ]);

        $item_code = $request->input('item_code');
        $quantity = $request->input('quantity');
        $warehouse = $request->input('warehouse');
        $labelCount = $request->input('label');

        $barcodes = [];

        $barcodeGenerator = new DNS1D;

        for ($i = 1; $i <= $labelCount; $i++) {
            $barcodeData = "{$item_code}\t{$quantity}\t{$warehouse}\t{$i}";
            $barcode = $barcodeGenerator->getBarcodeHTML($barcodeData, 'C128');

            $barcodes[] = [
                'barcode' => $barcode,
                'item_code' => $item_code,
                'quantity' => $quantity,
                'label' => $i,
            ];
        }

        return view('barcode.barcode_result', compact('barcodes'));
    }

    public function customGenerateForm(Request $request)
    {
        $branch = $request->query('branch', 'all'); // 'all', 'karawang', 'kbn'

        $items = MasterListItem::orderBy('item_code')->get();

        $logsQuery = CustomBarcodeLog::query();
        if ($branch === 'karawang') {
            $logsQuery->where('item_code', 'LIKE', 'K-%');
        } elseif ($branch === 'kbn') {
            $logsQuery->where('item_code', 'NOT LIKE', 'K-%');
        }
        $logs = $logsQuery->latest()->take(20)->get();

        $branchCounts = [
            'all' => CustomBarcodeLog::count(),
            'karawang' => CustomBarcodeLog::where('item_code', 'LIKE', 'K-%')->count(),
            'kbn' => CustomBarcodeLog::where('item_code', 'NOT LIKE', 'K-%')->count(),
        ];

        return view('barcode.custom_generate_form', compact('items', 'logs', 'branch', 'branchCounts'));
    }

    public function customGenerateLogs(Request $request)
    {
        $branch = $request->input('branch', 'all'); // 'all', 'karawang', 'kbn'
        $query = CustomBarcodeLog::query();

        if ($branch === 'karawang') {
            $query->where('item_code', 'LIKE', 'K-%');
        } elseif ($branch === 'kbn') {
            $query->where('item_code', 'NOT LIKE', 'K-%');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('spk_number', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%")
                    ->orWhere('operator', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        $baseStatsQuery = CustomBarcodeLog::query();
        if ($branch === 'karawang') {
            $baseStatsQuery->where('item_code', 'LIKE', 'K-%');
        } elseif ($branch === 'kbn') {
            $baseStatsQuery->where('item_code', 'NOT LIKE', 'K-%');
        }

        $stats = [
            'total_print_jobs' => (clone $baseStatsQuery)->count(),
            'total_labels_printed' => (clone $baseStatsQuery)->sum('total_labels'),
            'today_print_jobs' => (clone $baseStatsQuery)->whereDate('created_at', today())->count(),
            'today_labels_printed' => (clone $baseStatsQuery)->whereDate('created_at', today())->sum('total_labels'),
        ];

        $branchCounts = [
            'all' => CustomBarcodeLog::count(),
            'karawang' => CustomBarcodeLog::where('item_code', 'LIKE', 'K-%')->count(),
            'kbn' => CustomBarcodeLog::where('item_code', 'NOT LIKE', 'K-%')->count(),
        ];

        return view('barcode.custom_generate_logs', compact('logs', 'stats', 'branch', 'branchCounts'));
    }

    public function getSpksByItem(Request $request)
    {
        $itemCode = $request->input('item_code');
        
        $spks = SpkItemHistory::where('item_code', $itemCode)
            ->whereNotNull('spk_number')
            ->distinct()
            ->pluck('spk_number')
            ->toArray();

        return response()->json($spks);
    }

    public function customGeneratePrint(Request $request)
    {
        $request->validate([
            'item_code' => 'required|string',
            'spk_number' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'warehouse' => 'required|string',
            'start_label' => 'required|integer|min:1',
            'end_label' => 'required|integer|min:1|gte:start_label',
            'shift' => 'required|string|in:I,II,III',
            'prod_date' => 'nullable|date',
            'operator' => 'nullable|string',
            'customer' => 'nullable|string',
            'barcode_type' => 'nullable|string|in:default,sharp,yanfeng,itsp',
            'qad' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'is_sp' => 'nullable|boolean',
            'is_trial' => 'nullable|boolean',
            'remark' => 'nullable|string|max:1000',
        ]);

        $itemCode = $request->input('item_code');
        $spkNumber = $request->input('spk_number');
        $quantity = (int) $request->input('quantity');
        $warehouse = $request->input('warehouse');
        $startLabel = (int) $request->input('start_label');
        $endLabel = (int) $request->input('end_label');
        $shift = $request->input('shift');
        $prodDate = $request->input('prod_date') ?: null;
        $operator = $request->input('operator') ?: '-';
        $customer = $request->input('customer') ?: '-';
        $barcodeType = $request->input('barcode_type', 'default');
        $isTrial = $request->boolean('is_trial');
        $isSp = $request->boolean('is_sp');
        $remark = $request->input('remark');
        $totalLabels = ($endLabel - $startLabel) + 1;

        $item = MasterListItem::where('item_code', $itemCode)->first();
        $itemName = $item?->item_name ?? $itemCode;

        // Log the print action
        CustomBarcodeLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'Guest',
            'item_code' => $itemCode,
            'item_name' => $itemName,
            'spk_number' => $spkNumber,
            'quantity' => $quantity,
            'warehouse' => $warehouse,
            'shift' => $shift,
            'start_label' => $startLabel,
            'end_label' => $endLabel,
            'total_labels' => $totalLabels,
            'prod_date' => $prodDate,
            'operator' => $operator,
            'customer' => $customer,
            'barcode_type' => $barcodeType,
            'is_trial' => $isTrial,
            'remark' => $remark,
        ]);

        $labels = $this->buildLabelsData(
            $itemCode,
            $spkNumber,
            $quantity,
            $warehouse,
            $startLabel,
            $endLabel,
            $shift,
            $prodDate,
            $operator,
            $customer,
            $barcodeType,
            $isTrial,
            $isSp,
            $request->input('qad'),
            $request->input('model'),
            $request->input('color'),
            $request->input('position')
        );

        $logoBase64 = $this->getLogoBase64();

        return view('barcode.custom_generate_print', compact('labels', 'barcodeType', 'logoBase64'));
    }

    public function customGenerateReprint($id)
    {
        $log = CustomBarcodeLog::findOrFail($id);

        $itemCode = $log->item_code;
        $spkNumber = $log->spk_number;
        $quantity = (int) $log->quantity;
        $warehouse = $log->warehouse;
        $startLabel = (int) $log->start_label;
        $endLabel = (int) $log->end_label;
        $shift = $log->shift;
        $prodDate = $log->prod_date ?: null;
        $operator = $log->operator ?: '-';
        $customer = $log->customer ?: '-';
        $barcodeType = $log->barcode_type ?: 'default';
        $isTrial = (bool) $log->is_trial;
        $isSp = false;

        $labels = $this->buildLabelsData(
            $itemCode,
            $spkNumber,
            $quantity,
            $warehouse,
            $startLabel,
            $endLabel,
            $shift,
            $prodDate,
            $operator,
            $customer,
            $barcodeType,
            $isTrial,
            $isSp
        );

        $logoBase64 = $this->getLogoBase64();

        return view('barcode.custom_generate_print', compact('labels', 'barcodeType', 'logoBase64'));
    }

    protected function buildLabelsData(
        string $itemCode,
        string $spkNumber,
        int $quantity,
        string $warehouse,
        int $startLabel,
        int $endLabel,
        string $shift,
        ?string $prodDate,
        string $operator,
        string $customer,
        string $barcodeType,
        bool $isTrial,
        bool $isSp = false,
        ?string $customQad = null,
        ?string $customModel = null,
        ?string $customColor = null,
        ?string $customPosition = null
    ): array {
        $item = MasterListItem::where('item_code', $itemCode)->first();
        $itemName = $item?->item_name ?? $itemCode;
        $qad = $customQad ?: (($item?->description_in_foreign_lang && $item->description_in_foreign_lang !== '0') ? $item->description_in_foreign_lang : '');
        $model = $customModel ?: (($item?->family && $item->family !== '0') ? $item->family : '');
        $color = $customColor ?: (($item?->color && $item->color !== '0') ? $item->color : '');
        
        // Position format: 'right' -> 'RH', 'left' -> 'LH'
        $positionInput = $customPosition ?: (($item?->position && $item->position !== '0') ? $item->position : '');
        $posRaw = strtolower(trim((string) $positionInput));
        if ($posRaw === 'right' || $posRaw === 'rh' || $posRaw === 'r') {
            $position = 'RH';
            $enlargedHalfCode = 2;
        } elseif ($posRaw === 'left' || $posRaw === 'lh' || $posRaw === 'l') {
            $position = 'LH';
            $enlargedHalfCode = 1;
        } else {
            $position = strtoupper($positionInput ?: '-');
            $enlargedHalfCode = (str_contains($posRaw, 'left') || str_contains($posRaw, 'lh')) ? 1 : 2;
        }

        // Half codes for ITSP
        $h1 = strtoupper(trim((string) ($item?->half_code_1 && $item->half_code_1 !== '0' ? $item->half_code_1 : '')));
        $h2 = strtoupper(trim((string) ($item?->half_code_2 && $item->half_code_2 !== '0' ? $item->half_code_2 : '')));
        $itspCode = ($h1 !== '' || $h2 !== '') ? "{$h1}{$h2}" : $itemCode;

        // Year and month codes for SHARP format
        if ($prodDate) {
            $year = date('Y', strtotime($prodDate));
            $month = date('n', strtotime($prodDate));
            $yearCode = $this->getSharpYearCode($year);
            $monthName = $this->getIndonesianMonthName($month);
            $prodDateFormatted = "{$monthName} {$year}";
        } else {
            $year = '';
            $month = '';
            $yearCode = '';
            $monthName = '';
            $prodDateFormatted = '';
        }

        $labels = [];
        $writer = new PngWriter();

        for ($i = $startLabel; $i <= $endLabel; $i++) {
            // Format: spkno(tab)quantity(tab)warehouse(tab)nolabel
            $qrData = "{$spkNumber}\t{$quantity}\t{$warehouse}\t{$i}";
            if ($isTrial) {
                $qrData .= "\tTRIAL";
            }
            
            $qrCode = new QrCode(
                data: $qrData,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 150,
                margin: 0
            );

            $qrResult = $writer->write($qrCode);
            $qrBase64 = base64_encode($qrResult->getString());

            $labels[] = [
                'label_no' => $i,
                'item_code' => $itemCode,
                'item_name' => $itemName,
                'qad' => $qad,
                'model' => $model,
                'color' => $color,
                'position' => $position,
                'half_code_1' => $h1,
                'half_code_2' => $h2,
                'itsp_code' => $itspCode,
                'enlarged_half_code' => $enlargedHalfCode,
                'is_sp' => $isSp,
                'spk_number' => $spkNumber,
                'warehouse' => $warehouse,
                'prod_date' => $prodDate,
                'prod_date_formatted' => $prodDateFormatted,
                'year_code' => $yearCode,
                'month_code' => $month,
                'barcode_type' => $barcodeType,
                'operator' => $operator,
                'quantity' => $quantity,
                'shift' => $shift,
                'customer' => $customer,
                'qr_code_base64' => $qrBase64,
                'is_trial' => $isTrial,
            ];
        }

        return $labels;
    }

    protected function getLogoBase64(): ?string
    {
        $logoPath = public_path('picture/logo-dj.png');
        if (!file_exists($logoPath)) {
            $logoPath = storage_path('app/public/picture/logo-dj.png');
        }
        return file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    }

    /**
     * Map Year to 2-letter Code for SHARP customer:
     * 1=A, 2=B, 3=C, 4=D, 5=E, 6=F, 7=G, 8=H, 9=I, 0=O
     * e.g. 2026 -> 26 -> BF, 2027 -> 27 -> BG, 2030 -> 30 -> CO, 2040 -> 40 -> DO
     */
    protected function getSharpYearCode($year)
    {
        $lastTwo = sprintf('%02d', (int) $year % 100);
        $map = [
            '0' => 'O', '1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D',
            '5' => 'E', '6' => 'F', '7' => 'G', '8' => 'H', '9' => 'I'
        ];
        $d1 = $lastTwo[0];
        $d2 = $lastTwo[1];
        return ($map[$d1] ?? '') . ($map[$d2] ?? '');
    }

    /**
     * Get Indonesian month name (UPPERCASE)
     */
    protected function getIndonesianMonthName($monthNumber)
    {
        $months = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
        ];
        return $months[(int) $monthNumber] ?? '';
    }
}
