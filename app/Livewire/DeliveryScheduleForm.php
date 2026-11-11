<?php

namespace App\Livewire;

use App\Models\MasterCustomerDelivery;
use App\Models\MasterDeliverySchedule;
use App\Models\MasterListItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DeliveryScheduleForm extends Component
{
    use WithFileUploads;

    public $customerSearch = '';

    public $selectedCustomer = null;

    public $selectedMonth;

    public $selectedYear;

    public $itemSearch = '';

    public $selectedItem = null;

    public $soNum = '';

    public $schedules = [];

    public $showCustomerDropdown = false;

    public $showItemDropdown = false;

    // Excel Upload Properties
    public $showUploadModal = false;

    public $uploadFile = null;

    public $uploadMonth;

    public $uploadYear;

    public $importMode = 'append'; // 'append' or 'replace'

    public $parsedEntries = [];

    public $previewRows = [];

    public $previewSummary = [
        'total_rows' => 0,
        'total_qty' => 0,
        'unique_items' => 0,
        'unique_customers' => 0,
        'skipped_rows' => 0,
    ];

    public $uploadErrors = [];

    public $isProcessingUpload = false;

    protected $rules = [
        'selectedCustomer' => 'required',
        'selectedMonth' => 'required|integer|between:1,12',
        'selectedYear' => 'required|integer|min:2020',
        'selectedItem' => 'required',
    ];

    public function mount()
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
        $this->uploadMonth = now()->month;
        $this->uploadYear = now()->year;
        $this->generateScheduleTable();
    }

    public function openUploadModal()
    {
        $this->resetUploadState();
        $this->uploadMonth = $this->selectedMonth ?: now()->month;
        $this->uploadYear = $this->selectedYear ?: now()->year;
        $this->showUploadModal = true;
    }

    public function closeUploadModal()
    {
        $this->resetUploadState();
        $this->showUploadModal = false;
    }

    public function resetUploadState()
    {
        $this->uploadFile = null;
        $this->parsedEntries = [];
        $this->previewRows = [];
        $this->uploadErrors = [];
        $this->previewSummary = [
            'total_rows' => 0,
            'total_qty' => 0,
            'unique_items' => 0,
            'unique_customers' => 0,
            'skipped_rows' => 0,
        ];
        $this->isProcessingUpload = false;
    }

    /**
     * Download Excel template (.xlsx)
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Delivery Schedule');

        // Headers
        $headers = [
            'A1' => 'Customer Code',
            'B1' => 'Item Code',
            'C1' => 'Delivery Date',
            'D1' => 'Quantity',
            'E1' => 'SO Number',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // Header Styling
        $headerRange = 'A1:E1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2563EB'); // Royal Blue

        // Sample Data Rows
        $samples = [
            ['D0000047', '7217A197XA..', now()->format('Y-m-d'), 50, 'SO-20261101'],
            ['D0000047', '7217A198XA..', now()->addDays(2)->format('Y-m-d'), 100, 'SO-20261101'],
            ['D0000183', '401-27181M650A', now()->addDays(5)->format('Y-m-d'), 480, 'SO-20261102'],
        ];

        $rowNum = 2;
        foreach ($samples as $sample) {
            $sheet->setCellValue('A' . $rowNum, $sample[0]);
            $sheet->setCellValue('B' . $rowNum, $sample[1]);
            $sheet->setCellValue('C' . $rowNum, $sample[2]);
            $sheet->setCellValue('D' . $rowNum, $sample[3]);
            $sheet->setCellValue('E' . $rowNum, $sample[4]);
            $rowNum++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'template_delivery_schedule.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), 'tmpl_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Download CSV template
     */
    public function downloadTemplateCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_delivery_schedule.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Customer Code', 'Item Code', 'Delivery Date', 'Quantity', 'SO Number']);
            fputcsv($handle, ['D0000047', '7217A197XA..', now()->format('Y-m-d'), 50, 'SO-20261101']);
            fputcsv($handle, ['D0000047', '7217A198XA..', now()->addDays(2)->format('Y-m-d'), 100, 'SO-20261101']);
            fputcsv($handle, ['D0000183', '401-27181M650A', now()->addDays(5)->format('Y-m-d'), 480, 'SO-20261102']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Handle File Upload & Parse Preview
     */
    public function updatedUploadFile()
    {
        $this->validate([
            'uploadFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:20480',
        ]);

        $this->parsedEntries = [];
        $this->previewRows = [];
        $this->uploadErrors = [];

        try {
            $filePath = $this->uploadFile->getRealPath();
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, false);

            if (empty($rawRows) || count($rawRows) < 2) {
                $this->uploadErrors[] = 'File Excel kosong atau tidak memiliki baris data.';

                return;
            }

            // Pre-load reference maps untuk resolusi cepat
            $customers = MasterCustomerDelivery::all(['customer_code', 'customer_name']);
            $custByCode = $customers->keyBy(fn ($c) => strtoupper(trim($c->customer_code)));
            $custByName = $customers->keyBy(fn ($c) => strtoupper(trim($c->customer_name)));

            $items = MasterListItem::all(['item_code', 'item_name', 'customer_code']);
            $itemByCode = $items->keyBy(fn ($i) => strtoupper(trim($i->item_code)));
            $itemByClean = $items->keyBy(fn ($i) => strtoupper(rtrim(trim($i->item_code), '.')));

            // 1. Deteksi Kolom Header
            $headerRow = $rawRows[0] ?? [];
            $headerMap = [];
            $dayColumns = []; // [colIndex => dayNumber]

            foreach ($headerRow as $colIdx => $colName) {
                if ($colName === null || $colName === '') {
                    continue;
                }
                $clean = strtolower(trim((string) $colName));
                $cleanNoSpace = str_replace(['_', '-', '.', ' '], '', $clean);

                if (in_array($cleanNoSpace, ['customercode', 'customer', 'kodecustomer', 'kodecust', 'custcode', 'cust'])) {
                    $headerMap['customer_code'] = $colIdx;
                } elseif (in_array($cleanNoSpace, ['itemcode', 'item', 'kodeitem', 'kodepart', 'partnumber', 'partno', 'part'])) {
                    $headerMap['item_code'] = $colIdx;
                } elseif (in_array($cleanNoSpace, ['deliverydate', 'date', 'tanggal', 'tgl', 'tgldelivery'])) {
                    $headerMap['tanggal'] = $colIdx;
                } elseif (in_array($cleanNoSpace, ['quantity', 'qty', 'deliveryquantity', 'deliveryqty', 'jumlah', 'target', 'pcs'])) {
                    $headerMap['quantity'] = $colIdx;
                } elseif (in_array($cleanNoSpace, ['sonumber', 'sonum', 'so', 'noso'])) {
                    $headerMap['so_num'] = $colIdx;
                } elseif (is_numeric($clean) && (int) $clean >= 1 && (int) $clean <= 31) {
                    $dayColumns[$colIdx] = (int) $clean;
                }
            }

            $isMatrix = ! empty($dayColumns);
            $parsed = [];
            $skipped = 0;
            $rowErrors = [];

            // 2. Loop Baris Data
            for ($i = 1; $i < count($rawRows); $i++) {
                $row = $rawRows[$i];

                // Cek baris kosong
                $nonEmpty = array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== '');
                if (empty($nonEmpty)) {
                    continue;
                }

                $rawItem = isset($headerMap['item_code']) ? trim((string) ($row[$headerMap['item_code']] ?? '')) : '';
                if ($rawItem === '' && $this->selectedItem) {
                    $rawItem = $this->selectedItem->item_code;
                }

                if ($rawItem === '') {
                    $skipped++;
                    $rowErrors[] = "Baris " . ($i + 1) . ": Item Code kosong.";
                    continue;
                }

                // Resolusi Item Code & Name
                $matchedItem = $itemByCode->get(strtoupper($rawItem)) ?? $itemByClean->get(strtoupper(rtrim($rawItem, '.')));
                $itemCode = $matchedItem ? $matchedItem->item_code : $rawItem;
                $itemName = $matchedItem ? $matchedItem->item_name : $itemCode;

                // Resolusi Customer
                $rawCust = isset($headerMap['customer_code']) ? trim((string) ($row[$headerMap['customer_code']] ?? '')) : '';
                if ($rawCust === '' && $matchedItem && $matchedItem->customer_code) {
                    $rawCust = $matchedItem->customer_code;
                }
                if ($rawCust === '' && $this->selectedCustomer) {
                    $rawCust = $this->selectedCustomer->customer_code;
                }

                $matchedCust = $custByCode->get(strtoupper($rawCust)) ?? $custByName->get(strtoupper($rawCust));
                $custCode = $matchedCust ? $matchedCust->customer_code : ($rawCust ?: 'N/A');
                $custName = $matchedCust ? $matchedCust->customer_name : $custCode;

                $soNum = isset($headerMap['so_num']) ? trim((string) ($row[$headerMap['so_num']] ?? '')) : ($this->soNum ?: null);

                if ($isMatrix) {
                    // MODE MATRIKS: Kolom 1..31
                    foreach ($dayColumns as $colIdx => $day) {
                        $rawQty = $row[$colIdx] ?? 0;
                        if (! is_numeric($rawQty) || (float) $rawQty <= 0) {
                            continue;
                        }

                        $qty = (int) round((float) $rawQty);
                        $tanggal = sprintf('%04d-%02d-%02d', (int) $this->uploadYear, (int) $this->uploadMonth, $day);

                        $parsed[] = [
                            'customer_code' => $custCode,
                            'customer_name' => $custName,
                            'item_code' => $itemCode,
                            'item_name' => $itemName,
                            'tanggal' => $tanggal,
                            'quantity' => $qty,
                            'so_num' => $soNum ?: null,
                        ];
                    }
                } else {
                    // MODE LIST STANDAR
                    $rawDate = isset($headerMap['tanggal']) ? ($row[$headerMap['tanggal']] ?? null) : null;
                    $rawQty = isset($headerMap['quantity']) ? ($row[$headerMap['quantity']] ?? 0) : 0;

                    if (! is_numeric($rawQty) || (float) $rawQty <= 0) {
                        $skipped++;
                        continue;
                    }

                    $qty = (int) round((float) $rawQty);
                    $tanggal = $this->normalizeDate($rawDate, $this->uploadYear, $this->uploadMonth);

                    if (! $tanggal) {
                        $skipped++;
                        $rowErrors[] = "Baris " . ($i + 1) . ": Format tanggal '{$rawDate}' tidak valid.";
                        continue;
                    }

                    $parsed[] = [
                        'customer_code' => $custCode,
                        'customer_name' => $custName,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'tanggal' => $tanggal,
                        'quantity' => $qty,
                        'so_num' => $soNum ?: null,
                    ];
                }
            }

            if (empty($parsed)) {
                $this->uploadErrors[] = 'Tidak ditemukan jadwal dengan quantity > 0 yang valid pada file.';
                if (! empty($rowErrors)) {
                    $this->uploadErrors = array_merge($this->uploadErrors, array_slice($rowErrors, 0, 5));
                }

                return;
            }

            $this->parsedEntries = $parsed;
            $this->previewRows = array_slice($parsed, 0, 15);
            $this->previewSummary = [
                'total_rows' => count($parsed),
                'total_qty' => array_sum(array_column($parsed, 'quantity')),
                'unique_items' => count(array_unique(array_column($parsed, 'item_code'))),
                'unique_customers' => count(array_unique(array_column($parsed, 'customer_code'))),
                'skipped_rows' => $skipped,
            ];

            if (! empty($rowErrors)) {
                $this->uploadErrors = array_slice($rowErrors, 0, 3);
            }

        } catch (\Throwable $e) {
            $this->uploadErrors[] = 'Gagal memproses file Excel: ' . $e->getMessage();
        }
    }

    /**
     * Simpan Data Excel ke Database (master_delivery_schedule)
     */
    public function processImport()
    {
        if (empty($this->parsedEntries)) {
            session()->flash('error', 'Tidak ada data jadwal valid untuk diimpor.');

            return;
        }

        $this->isProcessingUpload = true;
        DB::beginTransaction();

        try {
            $inserted = 0;
            $updated = 0;

            // Jika mode replace, hapus data lama untuk item & rentang tanggal di file
            if ($this->importMode === 'replace') {
                $itemCodes = collect($this->parsedEntries)->pluck('item_code')->unique()->all();
                $dates = collect($this->parsedEntries)->pluck('tanggal')->unique()->all();
                $minDate = min($dates);
                $maxDate = max($dates);

                MasterDeliverySchedule::whereIn('item_code', $itemCodes)
                    ->whereBetween('tanggal', [$minDate, $maxDate])
                    ->delete();
            }

            foreach ($this->parsedEntries as $entry) {
                $existing = MasterDeliverySchedule::where('customer_code', $entry['customer_code'])
                    ->where('item_code', $entry['item_code'])
                    ->where('tanggal', $entry['tanggal'])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'quantity' => $entry['quantity'],
                        'so_num' => $entry['so_num'] ?: $existing->so_num,
                    ]);
                    $updated++;
                } else {
                    MasterDeliverySchedule::create([
                        'customer_code' => $entry['customer_code'],
                        'item_code' => $entry['item_code'],
                        'tanggal' => $entry['tanggal'],
                        'quantity' => $entry['quantity'],
                        'so_num' => $entry['so_num'],
                    ]);
                    $inserted++;
                }
            }

            DB::commit();

            $total = $inserted + $updated;
            $totalQty = number_format($this->previewSummary['total_qty']);
            $msg = "✅ Berhasil menyimpan {$total} jadwal delivery ({$inserted} baru, {$updated} diperbarui, Total Qty: {$totalQty} pcs) dari file Excel!";
            session()->flash('success', $msg);

            $this->closeUploadModal();
            $this->generateScheduleTable();

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->isProcessingUpload = false;
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Helper normalisasi tanggal Excel / string
     */
    protected function normalizeDate($rawDate, $fallbackYear = null, $fallbackMonth = null)
    {
        if (empty($rawDate)) {
            return null;
        }

        if (is_numeric($rawDate)) {
            if ($rawDate > 1000 && $rawDate < 100000) {
                try {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $rawDate)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // fall through
                }
            }

            if ($fallbackYear && $fallbackMonth && $rawDate >= 1 && $rawDate <= 31) {
                try {
                    return Carbon::createFromDate((int) $fallbackYear, (int) $fallbackMonth, (int) $rawDate)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        $str = trim((string) $rawDate);
        $formatted = str_replace('/', '-', $str);
        try {
            return Carbon::parse($formatted)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function updatedCustomerSearch()
    {
        $this->showCustomerDropdown = ! empty($this->customerSearch);
    }

    public function updatedItemSearch()
    {
        $this->showItemDropdown = ! empty($this->itemSearch);
    }

    public function selectCustomer($customerId)
    {
        $customer = MasterCustomerDelivery::find($customerId);
        if ($customer) {
            $this->selectedCustomer = $customer;
            $this->customerSearch = $customer->customer_code . ' - ' . $customer->customer_name;
            $this->showCustomerDropdown = false;
        }
    }

    public function selectItem($itemId)
    {
        $item = MasterListItem::find($itemId);
        if ($item) {
            $this->selectedItem = $item;
            $this->itemSearch = $item->item_code . ' - ' . $item->item_name;
            $this->showItemDropdown = false;
        }
    }

    public function updatedSelectedMonth()
    {
        $this->generateScheduleTable();
    }

    public function updatedSelectedYear()
    {
        $this->generateScheduleTable();
    }

    public function generateScheduleTable()
    {
        if ($this->selectedMonth && $this->selectedYear) {
            $daysInMonth = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->daysInMonth;

            $this->schedules = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $this->schedules[$day] = [
                    'date' => $day,
                    'quantity' => 0,
                ];
            }
        }
    }

    public function submit()
    {
        $this->validate();

        if (empty($this->schedules)) {
            session()->flash('error', 'Silakan generate tabel schedule terlebih dahulu');

            return;
        }

        DB::beginTransaction();

        try {
            $inserted = 0;

            foreach ($this->schedules as $day => $schedule) {
                // Skip jika quantity 0 atau kosong
                if (! isset($schedule['quantity']) || $schedule['quantity'] <= 0) {
                    continue;
                }

                // Buat tanggal lengkap
                $tanggal = Carbon::createFromDate(
                    $this->selectedYear,
                    $this->selectedMonth,
                    $day
                )->format('Y-m-d');

                // Insert ke database - LANGSUNG TANPA CONTROLLER
                MasterDeliverySchedule::create([
                    'customer_code' => $this->selectedCustomer->customer_code,
                    'item_code' => $this->selectedItem->item_code,
                    'tanggal' => $tanggal,
                    'quantity' => $schedule['quantity'],
                    'so_num' => $this->soNum ?: null,
                ]);

                $inserted++;
            }

            DB::commit();

            session()->flash('success', "Berhasil menyimpan {$inserted} schedule delivery!");
            $this->reset(['customerSearch', 'selectedCustomer', 'itemSearch', 'selectedItem', 'soNum', 'schedules']);
            $this->mount();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getCustomersProperty()
    {
        if (empty($this->customerSearch)) {
            return collect();
        }

        return MasterCustomerDelivery::where('customer_code', 'like', '%' . $this->customerSearch . '%')
            ->orWhere('customer_name', 'like', '%' . $this->customerSearch . '%')
            ->limit(10)
            ->get();
    }

    public function getItemsProperty()
    {
        if (empty($this->itemSearch)) {
            return collect();
        }

        return MasterListItem::where('item_code', 'like', '%' . $this->itemSearch . '%')
            ->orWhere('item_name', 'like', '%' . $this->itemSearch . '%')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.delivery-schedule-form');
    }
}
