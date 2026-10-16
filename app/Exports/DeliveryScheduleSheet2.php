<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DeliveryScheduleSheet2 implements FromCollection, WithHeadings, WithTitle
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
        $daysInMonth = Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth;
        $data = [];

        // Initialize the data structure for each item_code
        foreach ($this->deliveries as $delivery) {
            $day = Carbon::parse($delivery->delivery_date)->day;
            $itemCode = $delivery->item_code;

            // Set up rows for each item_code
            if (! isset($data[$itemCode])) {
                $data[$itemCode] = array_fill(1, $daysInMonth, 0); // Default to 0 for each day
            }

            // Populate the total delivery quantity for each day
            $data[$itemCode][$day] = $delivery->total_delivery_quantity;
        }

        // Convert to a collection for Excel export
        $result = [];
        foreach ($data as $itemCode => $days) {
            // Ensure that all days without data are set to 0
            $result[] = array_merge(['item_code' => $itemCode], $days);
        }

        return collect($result);
    }

    public function headings(): array
    {
        $days = range(1, Carbon::create($this->selectedYear, $this->selectedMonth)->daysInMonth);
        array_unshift($days, 'Item Code'); // Add 'Item Code' as the first column

        return $days;
    }

    public function title(): string
    {
        $customerName = $this->customerCode ? ' - Customer: ' . $this->customerCode : '';
        $monthName = Carbon::create($this->selectedYear, $this->selectedMonth)->format('F');

        return "Delivery Schedule (Item Code by Day) - {$monthName} {$this->selectedYear}{$customerName}";
    }
}
