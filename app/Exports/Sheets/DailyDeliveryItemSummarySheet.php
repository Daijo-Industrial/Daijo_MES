<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyDeliveryItemSummarySheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $items;
    protected string $dateLabel;
    protected int $rowNum = 1;

    public function __construct(array $items, string $dateLabel)
    {
        $this->items     = $items;
        $this->dateLabel = $dateLabel;
    }

    public function collection()
    {
        return collect($this->items);
    }

    public function headings(): array
    {
        return [
            'No',
            'Item Code (Part No)',
            'Nama Part / Model',
            'Customer',
            'Total Qty (Pcs)',
            'Total Box',
            'Jumlah Pallet',
            'Shift 1 (Pcs)',
            'Shift 2 (Pcs)',
            'Shift 3 (Pcs)',
            'Qty di Gudang (Pcs)',
            'Qty Keluar (Pcs)',
            'Daftar SPK',
            'Delivery / Pengirim',
        ];
    }

    public function map($row): array
    {
        $spkStr = !empty($row['spk_list']) ? implode(', ', $row['spk_list']) : '-';
        $delStr = !empty($row['delivery_names']) ? implode(', ', $row['delivery_names']) : '-';

        return [
            $this->rowNum++,
            $row['part_no'],
            $row['item_name'],
            $row['customer_name'],
            $row['total_qty'],
            $row['total_boxes'],
            $row['pallets_count'],
            $row['shift_breakdown'][1]['qty'] ?? 0,
            $row['shift_breakdown'][2]['qty'] ?? 0,
            $row['shift_breakdown'][3]['qty'] ?? 0,
            $row['qty_in_warehouse'] ?? $row['total_qty'],
            $row['qty_out'] ?? 0,
            $spkStr,
            $delStr,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E293B'], // Dark slate
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Rekap per Item Code';
    }
}
