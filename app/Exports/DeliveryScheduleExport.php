<?php

namespace App\Exports;

use App\Models\Delivery\DeliveryScheduleNew;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DeliveryScheduleExport implements WithMultipleSheets
{
    protected $selectedYear;

    protected $selectedMonth;

    protected $customerCode;

    protected $deliveries;

    public function __construct($selectedYear, $selectedMonth, $customerCode)
    {
        $this->selectedYear = $selectedYear;
        $this->selectedMonth = $selectedMonth;
        $this->customerCode = $customerCode;

        // Fetch the data based on the filters
        $this->deliveries = DeliveryScheduleNew::select('item_code', 'delivery_date', 'customer_code', DB::raw('SUM(delivery_quantity) as total_delivery_quantity'))
            ->whereYear('delivery_date', $this->selectedYear)
            ->whereMonth('delivery_date', $this->selectedMonth)
            ->when($this->customerCode, function ($query) {
                return $query->where('customer_code', $this->customerCode);
            })
            ->groupBy('item_code', 'delivery_date', 'customer_code')
            ->get();
    }

    public function sheets(): array
    {
        // Sheet 1 (Current layout)
        $sheet1 = new DeliveryScheduleSheet1($this->selectedYear, $this->selectedMonth, $this->customerCode, $this->deliveries);

        // Sheet 2 (Item code in rows, Day in columns)
        $sheet2 = new DeliveryScheduleSheet2($this->selectedYear, $this->selectedMonth, $this->customerCode, $this->deliveries);

        return [
            $sheet1,
            $sheet2,
        ];
    }
}
