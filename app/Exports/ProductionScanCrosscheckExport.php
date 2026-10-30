<?php

namespace App\Exports;

use App\Models\ProductionScannedData;
use App\Services\StoreCrosscheckService;
use App\Services\WmsDeliveryRecapService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionScanCrosscheckExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected string $selectedDate;
    protected string $plant;
    protected string $shift;
    protected string $status;
    protected string $search;
    protected int $rowNum = 1;

    public function __construct(
        string $selectedDate,
        string $plant = 'ALL',
        string $shift = 'ALL',
        string $status = 'ALL',
        string $search = ''
    ) {
        $this->selectedDate = $selectedDate;
        $this->plant = $plant;
        $this->shift = $shift;
        $this->status = $status;
        $this->search = $search;
    }

    public function collection()
    {
        $service = app(StoreCrosscheckService::class);
        return $service->getBaseQuery(
            $this->selectedDate,
            $this->plant,
            $this->shift,
            $this->status,
            $this->search
        )->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Plant',
            'Tanggal Produksi',
            'Waktu Scan Produksi',
            'Shift',
            'Mesin',
            'Operator Produksi',
            'No SPK',
            'Part Number',
            'Nama Barang',
            'Customer',
            'No Label / Box Barcode',
            'Qty (Pcs)',
            'Status Store',
            'Dicek Oleh (Store)',
            'Waktu Verifikasi Store',
            'Catatan Store',
        ];
    }

    /**
     * @param ProductionScannedData $row
     */
    public function map($row): array
    {
        $shiftVal = $row->dailyItemCode?->shift
            ?: WmsDeliveryRecapService::determineShiftFromTime($row->created_at);

        $machineName = $row->dailyItemCode?->user?->name ?: '-';
        $customerName = $row->masterItem?->customer?->customer_name
            ?: ($row->dailyItemCode?->masterItem?->customer?->customer_name ?: ($row->masterItem?->customer_code ?: 'N/A'));

        $itemName = $row->masterItem?->item_name
            ?: ($row->masterItem?->description ?: ($row->dailyItemCode?->masterItem?->item_name ?: '-'));

        $storeStatus = match ($row->store_check_status) {
            'FOUND' => 'ADA DI STORE (DITEMUKAN)',
            'NOT_FOUND' => 'TIDAK ADA (HILANG / BELUM TERIMA)',
            default => 'BELUM DICEK (PENDING)',
        };

        return [
            $this->rowNum++,
            $row->plant,
            $this->selectedDate,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
            'Shift ' . $shiftVal,
            $machineName,
            $row->user ?: '-',
            $row->spk_code ?: '-',
            $row->item_code,
            $itemName,
            $customerName,
            $row->label,
            $row->quantity,
            $storeStatus,
            $row->store_checked_by_name ?: '-',
            $row->store_checked_at ? $row->store_checked_at->format('Y-m-d H:i:s') : '-',
            $row->store_notes ?: '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E293B'], // Dark slate
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        $plantTitle = $this->plant === 'ALL' ? 'KBN & KRW' : $this->plant;
        return 'Crosscheck Store ' . $plantTitle . ' ' . $this->selectedDate;
    }
}
