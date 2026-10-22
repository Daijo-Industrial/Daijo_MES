<?php

namespace App\Livewire\Report;

use App\Models\Asakai;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class WeeklyReport extends Component
{
    public $weekStart;

    public $weekEnd;

    public $customerFilter = '';

    public $shiftFilter = '';

    public function mount()
    {
        $this->weekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
        $this->weekEnd = Carbon::now()->endOfWeek()->format('Y-m-d');
    }

    public function previousWeek()
    {
        $start = Carbon::parse($this->weekStart)->subWeek();
        $this->weekStart = $start->startOfWeek()->format('Y-m-d');
        $this->weekEnd = $start->endOfWeek()->format('Y-m-d');

        $this->loadChartData();
    }

    public function nextWeek()
    {
        $start = Carbon::parse($this->weekStart)->addWeek();
        $this->weekStart = $start->startOfWeek()->format('Y-m-d');
        $this->weekEnd = $start->endOfWeek()->format('Y-m-d');

        $this->loadChartData();
    }

    public function currentWeek()
    {
        $this->weekStart = Carbon::now()->startOfWeek()->format('Y-m-d');
        $this->weekEnd = Carbon::now()->endOfWeek()->format('Y-m-d');

        $this->loadChartData();
    }

    public function export($type = 'excel')
    {
        return redirect()->route('report.weekly.export', [
            'week_start' => $this->weekStart,
            'week_end' => $this->weekEnd,
            'customer' => $this->customerFilter,
            'shift' => $this->shiftFilter,
            'type' => $type,
        ]);
    }

    public function loadChartData()
    {
        $startDate = Carbon::parse($this->weekStart);
        $endDate = Carbon::parse($this->weekEnd);

        $asakais = Asakai::weekly($startDate, $endDate)
            ->when($this->customerFilter, fn ($q) => $q->where('customer', 'like', "%{$this->customerFilter}%"))
            ->when($this->shiftFilter, fn ($q) => $q->where('lot_shift', $this->shiftFilter))
            ->get();

        $groupedByDate = $asakais->groupBy(fn ($item) => $item->date_issue->format('Y-m-d'));

        $chartData = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $groupedByDate->get($dateStr)?->count() ?? 0;

            $chartData[] = [
                'dateShort' => $currentDate->format('D'),
                'count' => $count,
            ];

            $currentDate->addDay();
        }

        $byCustomerChart = $asakais->groupBy('customer')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(5);

        // 🔥 DISPATCH EVENT untuk update chart tanpa re-render
        $this->dispatch('updateWeeklyCharts', [
            'dailyLabels' => array_column($chartData, 'dateShort'),
            'dailyData' => array_column($chartData, 'count'),
            'customerLabels' => $byCustomerChart->keys()->values()->toArray(),
            'customerData' => $byCustomerChart->values()->values()->toArray(),
        ]);
    }

    public function updatedCustomerFilter()
    {
        $this->loadChartData();
    }

    public function updatedShiftFilter()
    {
        $this->loadChartData();
    }

    public function render()
    {
        $startDate = Carbon::parse($this->weekStart);
        $endDate = Carbon::parse($this->weekEnd);

        $asakais = Asakai::with(['pics', 'rcas', 'correctiveActions'])
            ->weekly($startDate, $endDate)
            ->when($this->customerFilter, fn ($q) => $q->where('customer', 'like', "%{$this->customerFilter}%"))
            ->when($this->shiftFilter, fn ($q) => $q->where('lot_shift', $this->shiftFilter))
            ->get();

        // Group by date
        $groupedByDate = $asakais->groupBy(fn ($item) => $item->date_issue->format('Y-m-d'));

        // Chart data for trend - IMPROVED
        $chartData = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $count = $groupedByDate->get($dateStr)?->count() ?? 0;
            $chartData[] = [
                'date' => $currentDate->format('D, d M'),
                'dateShort' => $currentDate->format('D'),
                'count' => $count,
            ];
            $currentDate->addDay();
        }

        // ← TAMBAH INI: Breakdown by customer untuk chart
        $byCustomerChart = $asakais->groupBy('customer')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(5);

        $customers = Asakai::distinct()->pluck('customer')->sort();

        return view('livewire.report.weekly-report', [
            'asakais' => $asakais,
            'customers' => $customers,
            'groupedByDate' => $groupedByDate,
            'chartData' => $chartData,
            'byCustomerChart' => $byCustomerChart, // ← TAMBAH INI
            'total' => $asakais->count(),
            'totalQuantity' => $asakais->sum('quantity'),
            'byCustomer' => $asakais->groupBy('customer'),
        ]);
    }
}
