<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MachineActiveHoursExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $data;

    protected $no = 1;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'No',
            'Machine Name',
            'Total Active Hours',
        ];
    }

    public function map($row): array
    {
        // $row is like ['hours' => 120, 'machine_name' => '0350F']
        // because of how we'll pass the data
        return [
            $this->no++,
            $row['machine_name'],
            $row['hours'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Bold the header row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
