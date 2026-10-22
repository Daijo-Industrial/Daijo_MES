<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OutstandingReportExport implements FromCollection, WithHeadings
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        $startDate = Carbon::now()->subMonth()->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        return collect($this->rows)
            ->filter(function ($row) use ($startDate, $endDate) {
                return in_array($row->status, ['Danger', 'Warning']) &&
                    Carbon::parse($row->delivery_date)->between($startDate, $endDate);
            })
            ->map(function ($row) {
                return [
                    'Delivery Date' => $row->delivery_date,
                    'SO Number' => $row->so_number,
                    'Customer Code' => $row->customer_code,
                    'Customer Name' => $row->customer_name,
                    'Item Code' => $row->item_code,
                    'Item Name' => $row->item_name,
                    'Department' => $row->departement,
                    'Delivery Quantity' => $row->delivery_qty,
                    'Outstanding' => $row->outstanding,
                    'Status' => $row->status,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Delivery Date',
            'SO Number',
            'Customer Code',
            'Customer Name',
            'Item Code',
            'Item Name',
            'Department',
            'Delivery Quantity',
            'Outstanding',
            'Status',
        ];
    }
}
