<?php

//asakai report export asakai

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DailyReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $asakais;

    protected $date;

    public function __construct($asakais, $date)
    {
        $this->asakais = $asakais;
        $this->date = $date;
    }

    public function collection()
    {
        return $this->asakais;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Customer',
            'Part No',
            'Issue',
            'Quantity',
            'Lot Date',
            'Lot Shift',
            'Date Issue',
            'PIC',
            'Status',
        ];
    }

    public function map($asakai): array
    {
        return [
            $asakai->id,
            $asakai->customer,
            $asakai->part_no,
            $asakai->issue,
            $asakai->quantity,
            $asakai->lot_date->format('d/m/Y'),
            $asakai->lot_shift,
            $asakai->date_issue->format('d/m/Y'),
            $asakai->pic_names,
            ucfirst($asakai->status),
        ];
    }
}
