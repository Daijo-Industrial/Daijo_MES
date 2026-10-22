<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DeliveryScheduleSheet1 implements FromCollection, WithHeadings, WithTitle
{
    protected $selectedYear;

    protected $selectedMonth;

    protected $customerCode;

    protected $deliveries;

    public function __construct($selectedYear, $selectedMonth, $customerCode, $deliveries)
    {
        $this->selectedYear = $selectedYear;
        $this->selectedMonth = $selectedMonth;
        $this->customerCode = $customerCode;
        $this->deliveries = $deliveries;
    }

    public function collection()
    {
        $data = [];

        // Loop through the deliveries and prepare the data for export
        foreach ($this->deliveries as $delivery) {
            $day = Carbon::parse($delivery->delivery_date)->day;
            $data[] = [
                'item_code' => $delivery->item_code,
                'day' => $day,
                'total_delivery_quantity' => $delivery->total_delivery_quantity,
                'customer_code' => $delivery->customer_code,
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Tanggal',
            'Total Delivery Quantity',
            'Customer Code',
        ];
    }

    public function title(): string
    {
        $customerName = $this->customerCode ? ' - Customer: ' . $this->customerCode : '';
        $monthName = Carbon::create($this->selectedYear, $this->selectedMonth)->format('F');

        return "Delivery Schedule - {$monthName} {$this->selectedYear}{$customerName}";
    }
}
