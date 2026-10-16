<?php

namespace App\Exports;

use App\Models\Delivery\DelschedFinal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DelschedFinalExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DelschedFinal::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Delivery Date',
            'SO Number',
            'Customer Code',
            'Customer Name',
            'Item Code',
            'Item Name',
            'Department',
            'Delivery Quantity',
            'Delivered',
            'Outstanding',
            'Stock',
            'Balance',
            'Outstanding Stock',
            'Packaging Code',
            'Standard Pack',
            'Packaging Quantity',
            'Document Status',
            'Status', // Assuming you have a 'status' column as the last field
        ];
    }
}
