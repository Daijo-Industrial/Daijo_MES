<?php

namespace App\Livewire\Report;

use App\Models\Asakai;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class MonthlyReport extends Component
{
    public $year;

    public $month;

    public $customerFilter = '';

    public function mount()
    {
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;

        $this->loadChartData();
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;

        $this->loadChartData();
    }

    public function currentMonth()
    {
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;

        $this->loadChartData();
    }

    public function export($type = 'excel')
    {
        return redirect()->route('report.monthly.export', [
            'year' => $this->year,
            'month' => $this->month,
            'customer' => $this->customerFilter,
            'type' => $type,
        ]);
    }

    public function loadChartData()
    {
        $asakais = Asakai::monthly($this->year, $this->month)
            ->when($this->customerFilter, fn ($q) => $q->where('customer', 'like', "%{$this->customerFilter}%"))
            ->get();

        // Daily trend
        $dailyTrend = $asakais->groupBy(fn ($item) => $item->date_issue->format('d'))
            ->map(fn ($group) => $group->count())
            ->sortKeys();

        // Customer trend (Top 5)
        $byCustomer = $asakais->groupBy('customer')->map(fn ($group) => [
            'count' => $group->count(),
            'quantity' => $group->sum('quantity'),
        ]);
        $customerTrend = $byCustomer->sortByDesc('count')->take(5);

        // Dispatch event
        $this->dispatch('updateMonthlyCharts', [
            'dailyLabels' => $dailyTrend->keys()->map(fn ($d) => 'Day ' . $d)->values()->toArray(),
            'dailyData' => $dailyTrend->values()->values()->toArray(),
            'customerLabels' => $customerTrend->keys()->values()->toArray(),
            'customerData' => $customerTrend->pluck('count')->values()->toArray(),
        ]);
    }

    public function updatedCustomerFilter()
    {
        $this->loadChartData();
    }

    public function render()
    {
        $asakais = Asakai::with(['pics', 'rcas', 'correctiveActions'])
            ->monthly($this->year, $this->month)
            ->when($this->customerFilter, fn ($q) => $q->where('customer', 'like', "%{$this->customerFilter}%"))
            ->get();

        // Analytics
        $byCustomer = $asakais->groupBy('customer')->map(fn ($group) => [
            'count' => $group->count(),
            'quantity' => $group->sum('quantity'),
        ]);

        $byShift = $asakais->groupBy('lot_shift')->map(fn ($group) => [
            'count' => $group->count(),
            'quantity' => $group->sum('quantity'),
        ]);

        // Top 10 Issues (by frequency)
        $topIssues = $asakais->groupBy('issue')
            ->map(fn ($group) => [
                'issue' => $group->first()->issue,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(10);

        // Performance Metrics - FIX ERROR INI
        $avgReplyDays = $asakais
            ->filter(fn ($a) => $a->reply_date && $a->date_issue)
            ->map(fn ($a) => $a->date_issue->diffInDays($a->reply_date))
            ->avg();

        $overdueCount = $asakais->filter(fn ($a) => $a->is_overdue)->count();

        // ← TAMBAH INI: Data untuk Chart (Daily trend dalam bulan)
        $dailyTrend = $asakais->groupBy(fn ($item) => $item->date_issue->format('d'))
            ->map(fn ($group) => $group->count())
            ->sortKeys();

        // ← TAMBAH INI: Customer trend (Top 5)
        $customerTrend = $byCustomer->sortByDesc('count')->take(5);

        $customers = Asakai::distinct()->pluck('customer')->sort();

        $monthName = Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');

        return view('livewire.report.monthly-report', [
            'asakais' => $asakais,
            'customers' => $customers,
            'monthName' => $monthName,
            'total' => $asakais->count(),
            'totalQuantity' => $asakais->sum('quantity'),
            'byCustomer' => $byCustomer,
            'byShift' => $byShift,
            'topIssues' => $topIssues,
            'avgReplyDays' => round($avgReplyDays ?? 0, 1),
            'overdueCount' => $overdueCount,
            'dailyTrend' => $dailyTrend, // ← TAMBAH INI
            'customerTrend' => $customerTrend, // ← TAMBAH INI
        ]);
    }
}
