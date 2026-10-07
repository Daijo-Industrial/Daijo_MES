<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyDeliveryBoxDetailSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $boxLogs;
    protected int $rowNum = 1;

    public function __construct(array $boxLogs)
    {
        $this->boxLogs = $boxLogs;
    }

    public function collection()
    {
        return collect($this->boxLogs);
    }

    public function headings(): array
    {
        return [
            'No',
            'Pallet ID',
            'Delivery Name',
            'Shift',
            'Lot No',
            'Slot Rak',
            'Item Code',
            'Nama Part',
            'Customer',
            'SPK No',
            'No Label',
            'Qty (Pcs)',
            'Waktu Scan Masuk',
            'Status',
            'Waktu Keluar (SO)',
        ];
    }

    public function map($row): array
    {
        return [
            $this->rowNum++,
            $row['pallet_id'],
            $row['delivery_name'],
            'Shift ' . $row['shift'],
            $row['lot_no'],
            $row['slot'],
            $row['part_no'],
            $row['item_name'],
            $row['customer_name'],
            $row['spk_no'],
            $row['label'],
            $row['qty'],
            $row['scan_time'],
            $row['status'] ?? ($row['is_out'] ? 'KELUAR' : 'DI GUDANG'),
            $row['out_time'] ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF047857'], // Emerald green
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Detail Box & Pallet';
    }
}
