<?php

namespace App\Livewire;

use App\Services\ProductionDashboardService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class ProductionDashboard extends Component
{
    public $viewType = 'monthly';

    public $plant = ''; // '', 'karawang', 'kbn'

    public $year;

    public $month;

    public $week = 1;

    public $selectedDate;

    public $itemCode = null;

    public $machineUserId = null;

    public bool $isHalfDay = false;

    public bool $isSaturdayHalfDay = false;

    public bool $isSundayHalfDay = false;

    // For searchable item code
    public $itemCodeSearch = '';

    public $showItemCodeDropdown = false;

    public array $chartData = [];

    public array $summary = [];

    protected ?array $cachedDashboardData = null;

    public array $years = [];

    public array $months = [];

    public array $weeks = [];

    public array $filteredItemCodes = [];

    public array $allMachines = [];

    public array $machines = [];

    protected ProductionDashboardService $productionService;

    public function boot(ProductionDashboardService $service)
    {
        $this->productionService = $service;
    }

    public function mount()
    {
        $plantService = app(\App\Services\PlantContextService::class);
        $effectiveBranch = $plantService->getEffectiveBranch();
        if ($effectiveBranch) {
            $this->plant = strtolower($effectiveBranch->code) === 'krw' ? 'karawang' : 'kbn';
        }

        $this->year = now()->year;
        $this->month = now()->month;
        $this->selectedDate = now()->format('Y-m-d');
        $this->isHalfDay = false;
        $this->isSaturdayHalfDay = false;
        $this->isSundayHalfDay = false;

        $this->populateFilterOptions();
        $this->loadData();
    }

    private function populateFilterOptions()
    {
        $currentYear = now()->year;

        for ($i = -3; $i <= 1; $i++) {
            $this->years[] = $currentYear + $i;
        }

        $this->months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        // ✅ Get users with IDs for machine mapping
        $machineUsers = \App\Models\User::where(function ($q) {
            $q->whereIn('name', [
                '0150E', '0150F', '0300A', '0300B', '0350F', '0360A', '0360B', '0360C', '0360D', '0450B',
                '0450F', '0450G', '0450H', '0450I', '0450J',
                '0550B', '0650D', '0650E', '0850D', '1050D', '1300B', '1300C',
                'K2800A', 'K2100A', 'K1400A', 'K1400B', 'K1400C',
                'K0900A', 'K0900B', 'K0650A', 'K0650B',
                'K0750A', 'K0750B', 'K0450A',
            ])
                ->orWhere('name', 'LIKE', 'K%')
                ->orWhere('name', 'LIKE', '0%')
                ->orWhere('name', 'LIKE', '1%')
                ->orWhere('name', 'LIKE', '2%')
                ->orWhere('name', 'LIKE', '3%')
                ->orWhere('name', 'LIKE', '4%')
                ->orWhere('name', 'LIKE', '5%')
                ->orWhere('name', 'LIKE', '6%')
                ->orWhere('name', 'LIKE', '7%')
                ->orWhere('name', 'LIKE', '8%')
                ->orWhere('name', 'LIKE', '9%');
        })->with('branch')->get(['id', 'name', 'branch_id']);

        $this->allMachines = $machineUsers->map(function ($user) {
            $isKarawang = ($user->branch && $user->branch->code === 'KRW') || str_starts_with(strtoupper($user->name), 'K');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'plant' => $isKarawang ? 'karawang' : 'kbn',
            ];
        })->sortBy('name')->values()->toArray();

        $this->updateFilteredMachines();
        $this->updateWeeks();
        $this->updateItemCodes();
    }

    private function updateFilteredMachines()
    {
        if ($this->plant === 'karawang') {
            $this->machines = array_values(array_filter($this->allMachines, function ($m) {
                return $m['plant'] === 'karawang';
            }));
        } elseif ($this->plant === 'kbn') {
            $this->machines = array_values(array_filter($this->allMachines, function ($m) {
                return $m['plant'] === 'kbn';
            }));
        } else {
            $this->machines = $this->allMachines;
        }

        // If selected machine does not belong to filtered machines, reset it
        if ($this->machineUserId) {
            $machineExists = collect($this->machines)->contains('id', (int) $this->machineUserId);
            if (! $machineExists) {
                $this->machineUserId = null;
            }
        }
    }

    private function updateItemCodes()
    {
        $dateParam = ($this->viewType === 'daily') ? $this->selectedDate : null;
        $this->filteredItemCodes = $this->productionService->getItemCodes(
            (int) $this->year,
            (int) $this->month,
            $this->plant,
            $dateParam,
            $this->itemCodeSearch ?: null,
            50
        );
    }

    public function updatedPlant()
    {
        $this->machineUserId = null;
        $this->itemCode = null;
        $this->itemCodeSearch = '';
        $this->updateFilteredMachines();
        $this->updateItemCodes();
        $this->loadData();
    }

    public function updatedItemCodeSearch()
    {
        $this->updateItemCodes();
        $this->showItemCodeDropdown = ! empty($this->itemCodeSearch);
    }

    public function selectItemCode($code)
    {
        $this->itemCode = $code;
        $this->itemCodeSearch = $code;
        $this->showItemCodeDropdown = false;
        $this->loadData();
    }

    public function clearItemCode()
    {
        $this->itemCode = null;
        $this->itemCodeSearch = '';
        $this->updateItemCodes();
        $this->showItemCodeDropdown = false;
        $this->loadData();
    }

    public function updatedYear()
    {
        $this->updateWeeks();
        $this->updateItemCodes();
        $this->loadData();
    }

    public function updatedMonth()
    {
        $this->updateWeeks();
        $this->updateItemCodes();
        $this->loadData();
    }

    public function updatedWeek()
    {
        $this->loadData();
    }

    public function updatedSelectedDate()
    {
        $this->isHalfDay = false;
        $this->updateItemCodes();
        $this->loadData();
    }

    public function updatedViewType()
    {
        $this->isHalfDay = false;
        $this->isSaturdayHalfDay = false;
        $this->isSundayHalfDay = false;
        $this->updateWeeks();
        $this->updateItemCodes();
        $this->loadData();
    }

    public function updatedIsHalfDay()
    {
        $this->loadData();
    }

    public function updatedIsSaturdayHalfDay()
    {
        $this->loadData();
    }

    public function updatedIsSundayHalfDay()
    {
        $this->loadData();
    }

    public function updatedMachineUserId()
    {
        $this->loadData();
    }

    private function updateWeeks()
    {
        $this->weeks = $this->productionService->getWeeksInMonth((int) $this->year, (int) $this->month);

        if (empty($this->weeks)) {
            $this->week = 1;

            return;
        }

        if (! isset($this->weeks[$this->week - 1])) {
            $this->week = 1;
        }
    }

    public function loadData(bool $forceRefresh = false)
    {
        $this->cachedDashboardData = null;
        $data = $this->getDashboardData($forceRefresh);

        $this->dispatch('chartDataUpdated', chartData: $this->chartData);
        $this->dispatch('adjusterChartDataUpdated', adjusterChartData: $data['adjuster_ng_trend'] ?? []);
    }

    public function refreshData()
    {
        $this->loadData(forceRefresh: true);
    }

    private function getDashboardData(bool $forceRefresh = false): array
    {
        if ($this->cachedDashboardData !== null && ! $forceRefresh) {
            return $this->cachedDashboardData;
        }

        [$startDate, $endDate] = $this->getDateRange();

        if ($this->viewType === 'daily') {
            $halfDayParam = $this->isHalfDay;
        } elseif ($this->viewType === 'weekly') {
            $halfDayParam = [
                'saturday' => $this->isSaturdayHalfDay,
                'sunday' => $this->isSundayHalfDay,
            ];
        } else {
            $halfDayParam = null;
        }

        $data = $this->productionService->getAllDashboardData(
            $startDate,
            $endDate,
            $this->itemCode,
            $this->machineUserId,
            $this->plant,
            $halfDayParam,
            $forceRefresh
        );

        $this->summary = $data['summary'] ?? [];
        $this->chartData = $data['chart_data'] ?? [];
        $this->cachedDashboardData = $data;

        return $data;
    }

    private function getDateRange(): array
    {
        if ($this->viewType === 'daily') {
            $date = ! empty($this->selectedDate) ? Carbon::parse($this->selectedDate) : now();

            return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
        } elseif ($this->viewType === 'weekly' && ! empty($this->weeks)) {
            $weekIndex = max(0, $this->week - 1);
            $weekData = $this->weeks[$weekIndex] ?? null;

            if ($weekData) {
                return [
                    Carbon::parse($weekData['start'])->startOfDay(),
                    Carbon::parse($weekData['end'])->endOfDay(),
                ];
            }
        }

        $startOfMonth = Carbon::createFromDate($this->year, $this->month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        return [$startOfMonth, $endOfMonth];
    }

    public function resetFilters()
    {
        $this->viewType = 'monthly';
        $this->plant = '';
        $this->year = now()->year;
        $this->month = now()->month;
        $this->selectedDate = now()->format('Y-m-d');
        $this->week = 1;
        $this->itemCode = null;
        $this->itemCodeSearch = '';
        $this->machineUserId = null;
        $this->isHalfDay = false;
        $this->isSaturdayHalfDay = false;
        $this->isSundayHalfDay = false;

        $this->updateFilteredMachines();
        $this->updateWeeks();
        $this->updateItemCodes();
        $this->loadData();
    }

    public function exportPdf()
    {
        return redirect()->route('production-dashboard.pdf', [
            'viewType' => $this->viewType,
            'plant' => $this->plant,
            'year' => $this->year,
            'month' => $this->month,
            'week' => $this->week,
            'selectedDate' => $this->selectedDate,
            'itemCode' => $this->itemCode,
            'machineUserId' => $this->machineUserId,
        ]);
    }

    public function render()
    {
        $data = $this->getDashboardData();

        return view('livewire.Production-dashboard', array_merge([
            'summary' => $this->summary,
            'chartData' => $this->chartData,
            'purgingDetails' => $data['purging_details'] ?? [],
            'ngBreakdown' => $data['ng_breakdown'] ?? [],
            'downtimeAnalysis' => $data['downtime_analysis'] ?? [],
            'topRemarks' => $data['top_remarks'] ?? [],
            'machineWorkingHours' => $data['machine_working_hours'] ?? [],
            'shiftPersonnelAnalysis' => $data['shift_personnel_analysis'] ?? [],
            'adjusterNgTrend' => $data['adjuster_ng_trend'] ?? [],
        ]));
    }
}
