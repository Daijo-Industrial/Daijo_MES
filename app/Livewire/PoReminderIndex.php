<?php

namespace App\Livewire;

use App\Services\PoReminderService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PoReminderIndex extends Component
{
    use WithPagination;

    // Filter tanggal delivery schedule
    public string $startDate = '';

    public string $endDate = '';

    // Active View Mode:
    // 'net'   => View 2: Include Stok Aktual FG (Total Delivery - Stok FG)
    // 'gross' => View 1: Abaikan Stok FG (Total Delivery Kotor)
    public string $activeView = 'net';

    // Mode Tampilan:
    // 'materials' => Rekap Ringkasan Kebutuhan Material (PO Reminder)
    // 'fgs'       => Rincian Jadwal Delivery per Finish Good (FG)
    public string $displayMode = 'materials';

    // Filter pencarian
    public string $search = '';

    // Filter kategori material
    public string $categoryFilter = 'ALL';

    // Pagination per page
    public int $perPage = 25;

    // Modal detail
    public ?array $activeMaterialDetail = null;

    public ?array $activeFgDetail = null;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'activeView' => ['except' => 'net'],
        'displayMode' => ['except' => 'materials'],
        'categoryFilter' => ['except' => 'ALL'],
    ];

    public function mount(): void
    {
        if (empty($this->startDate)) {
            $this->startDate = now('Asia/Jakarta')->startOfMonth()->toDateString();
        }
        if (empty($this->endDate)) {
            $this->endDate = now('Asia/Jakarta')->endOfMonth()->toDateString();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingActiveView(): void
    {
        $this->resetPage();
    }

    public function updatingDisplayMode(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function setPreset(string $preset): void
    {
        $now = now('Asia/Jakarta');
        switch ($preset) {
            case 'this_month':
                $this->startDate = $now->copy()->startOfMonth()->toDateString();
                $this->endDate = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'next_month':
                $this->startDate = $now->copy()->addMonth()->startOfMonth()->toDateString();
                $this->endDate = $now->copy()->addMonth()->endOfMonth()->toDateString();
                break;
            case '7_days':
                $this->startDate = $now->toDateString();
                $this->endDate = $now->copy()->addDays(7)->toDateString();
                break;
            case '14_days':
                $this->startDate = $now->toDateString();
                $this->endDate = $now->copy()->addDays(14)->toDateString();
                break;
            case '30_days':
                $this->startDate = $now->toDateString();
                $this->endDate = $now->copy()->addDays(30)->toDateString();
                break;
        }
        $this->resetPage();
    }

    public function showMaterialDetail(string $materialCode, PoReminderService $service): void
    {
        $data = $service->calculate($this->startDate, $this->endDate, null, null);
        $materialList = $this->activeView === 'net' ? $data['view2_materials'] : $data['view1_materials'];
        $found = collect($materialList)->firstWhere('item_code', $materialCode);

        $this->activeMaterialDetail = $found ?: null;
    }

    public function closeMaterialDetail(): void
    {
        $this->activeMaterialDetail = null;
    }

    public function showFgDetail(string $fgCode, PoReminderService $service): void
    {
        $data = $service->calculate($this->startDate, $this->endDate, null, null);
        $found = collect($data['fg_items'])->firstWhere('item_code', $fgCode);

        $this->activeFgDetail = $found ?: null;
    }

    public function closeFgDetail(): void
    {
        $this->activeFgDetail = null;
    }

    /**
     * Download Excel Rekap Material & Detail FG
     */
    public function exportExcel(PoReminderService $service): StreamedResponse
    {
        $result = $service->calculate($this->startDate, $this->endDate, $this->search, $this->categoryFilter);
        $viewName = $this->activeView === 'net' ? 'Net (Delivery - Stok FG)' : 'Gross (Total Delivery)';
        $materialList = $this->activeView === 'net' ? $result['view2_materials'] : $result['view1_materials'];

        $spreadsheet = new Spreadsheet();

        // ── SHEET 1: REKAP MATERIAL (PO REMINDER) ──
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('PO Reminder Material');

        // Header Informasi
        $sheet1->setCellValue('A1', 'DAIJO MES - PO REMINDER (KEBUTUHAN MATERIAL)');
        $sheet1->setCellValue('A2', "Periode Delivery: {$this->startDate} s/d {$this->endDate} | Mode: {$viewName}");
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet1->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));

        // Table Header
        $headers1 = ['No', 'Kode Material', 'Deskripsi Material', 'Kategori', 'Total Kebutuhan', 'UoM', 'Jumlah FG Terkait', 'Daftar Part / FG yang Membutuhkan'];
        $col1 = 'A';
        foreach ($headers1 as $h) {
            $sheet1->setCellValue("{$col1}4", $h);
            $col1++;
        }

        $headerRange1 = 'A4:H4';
        $sheet1->getStyle($headerRange1)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet1->getStyle($headerRange1)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1E293B');
        $sheet1->getStyle($headerRange1)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $rowNum1 = 5;
        $no1 = 1;
        foreach ($materialList as $mat) {
            $fgsSummary = collect($mat['used_by_fgs'] ?? [])->map(function ($f) {
                return $f['fg_code'] . ' (' . number_format($f['material_qty'], 2) . ')';
            })->implode(', ');

            $sheet1->setCellValue("A{$rowNum1}", $no1++);
            $sheet1->setCellValue("B{$rowNum1}", $mat['item_code']);
            $sheet1->setCellValue("C{$rowNum1}", $mat['description']);
            $sheet1->setCellValue("D{$rowNum1}", $mat['category']['label'] ?? 'Material');
            $sheet1->setCellValue("E{$rowNum1}", $mat['total_needed_qty']);
            $sheet1->setCellValue("F{$rowNum1}", $mat['uom']);
            $sheet1->setCellValue("G{$rowNum1}", $mat['fgs_count'] ?? 0);
            $sheet1->setCellValue("H{$rowNum1}", $fgsSummary);

            $sheet1->getStyle("E{$rowNum1}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum1++;
        }

        foreach (range('A', 'H') as $c) {
            $sheet1->getColumnDimension($c)->setAutoSize(true);
        }

        // ── SHEET 2: DETAIL FG DELIVERY & BOM ──
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rincian FG Delivery');

        $sheet2->setCellValue('A1', 'RINCIAN SCHEDULE FINISH GOOD & STOK AKTUAL');
        $sheet2->setCellValue('A2', "Periode Delivery: {$this->startDate} s/d {$this->endDate}");
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));

        $headers2 = ['No', 'Kode FG / Part No', 'Nama Part / Deskripsi', 'Customer', 'Total Delivery Qty', 'Stok Aktual FG', 'Kekurangan FG (Net)', 'Status Stok', 'Status BOM'];
        $col2 = 'A';
        foreach ($headers2 as $h) {
            $sheet2->setCellValue("{$col2}4", $h);
            $col2++;
        }

        $headerRange2 = 'A4:I4';
        $sheet2->getStyle($headerRange2)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet2->getStyle($headerRange2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0F766E');
        $sheet2->getStyle($headerRange2)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $rowNum2 = 5;
        $no2 = 1;
        foreach ($result['fg_items'] as $fg) {
            $stockStatus = $fg['net_deficit'] > 0 ? "Kurang " . number_format($fg['net_deficit']) : 'Cukup (Stok Aman)';
            $bomStatus = $fg['has_bom'] ? 'Tersedia' : 'BOM Belum Ada';

            $sheet2->setCellValue("A{$rowNum2}", $no2++);
            $sheet2->setCellValue("B{$rowNum2}", $fg['item_code']);
            $sheet2->setCellValue("C{$rowNum2}", $fg['item_name']);
            $sheet2->setCellValue("D{$rowNum2}", $fg['customer_name']);
            $sheet2->setCellValue("E{$rowNum2}", $fg['delivery_qty']);
            $sheet2->setCellValue("F{$rowNum2}", $fg['stock_actual']);
            $sheet2->setCellValue("G{$rowNum2}", $fg['net_deficit']);
            $sheet2->setCellValue("H{$rowNum2}", $stockStatus);
            $sheet2->setCellValue("I{$rowNum2}", $bomStatus);

            $sheet2->getStyle("E{$rowNum2}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet2->getStyle("F{$rowNum2}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet2->getStyle("G{$rowNum2}")->getNumberFormat()->setFormatCode('#,##0');
            $rowNum2++;
        }

        foreach (range('A', 'I') as $c) {
            $sheet2->getColumnDimension($c)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'PO_Reminder_Material_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function render(PoReminderService $service)
    {
        $data = $service->calculate(
            $this->startDate,
            $this->endDate,
            $this->search,
            $this->categoryFilter
        );

        $kpi = $data['kpi'];
        $fgItems = $data['fg_items'];
        $rawMaterials = $this->activeView === 'net' ? $data['view2_materials'] : $data['view1_materials'];

        // Pagination Manual untuk Material List
        $totalMaterials = count($rawMaterials);
        $page = $this->getPage();
        $offset = ($page - 1) * $this->perPage;
        $pagedMaterials = array_slice($rawMaterials, $offset, $this->perPage);

        $paginatedMaterials = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedMaterials,
            $totalMaterials,
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Pagination Manual untuk FG List
        $totalFgs = count($fgItems);
        $pagedFgs = array_slice($fgItems, $offset, $this->perPage);

        $paginatedFgs = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedFgs,
            $totalFgs,
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.po-reminder-index', [
            'kpi' => $kpi,
            'paginatedMaterials' => $paginatedMaterials,
            'paginatedFgs' => $paginatedFgs,
            'totalMaterialsCount' => $totalMaterials,
            'totalFgsCount' => $totalFgs,
        ]);
    }
}

