<?php

//weekly report export asakai

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WeeklyReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $asakais;

    protected $weekStart;

    protected $weekEnd;

    public function __construct($asakais, $weekStart, $weekEnd)
    {
        $this->asakais = $asakais;
        $this->weekStart = $weekStart;
        $this->weekEnd = $weekEnd;
    }

    public function collection()
    {
        return $this->asakais;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date Issue',
            'Customer',
            'Part No',
            'Issue',
            'Quantity',
            'Shift',
            'PIC',
            'Status',
        ];
    }

    public function map($asakai): array
    {
        return [
            $asakai->id,
            $asakai->date_issue->format('d/m/Y'),
            $asakai->customer,
            $asakai->part_no,
            $asakai->issue,
            $asakai->quantity,
            $asakai->lot_shift,
            $asakai->pic_names,
            ucfirst($asakai->status),
        ];
    }
}
