<?php

//monthly report export asakai

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonthlyReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $asakais;

    protected $year;

    protected $month;

    public function __construct($asakais, $year, $month)
    {
        $this->asakais = $asakais;
        $this->year = $year;
        $this->month = $month;
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
            'RCA Count',
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
            $asakai->rcas->count(),
            ucfirst($asakai->status),
        ];
    }
}
