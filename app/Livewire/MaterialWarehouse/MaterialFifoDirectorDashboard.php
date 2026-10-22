<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MwhWarehouse;
use App\Services\MaterialFifoService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class MaterialFifoDirectorDashboard extends Component
{
    #[Url]
    public $whse_id = 'ALL';

    #[Url]
    public string $preset = '30_days'; // today, 7_days, 30_days, this_month, this_year, custom

    #[Url]
    public ?string $fromDate = null;

    #[Url]
    public ?string $toDate = null;

    public string $search = '';

    public int $autoRefreshInterval = 30; // in seconds, 0 = disabled

    public string $activeTab = 'overview'; // overview, deviations, queue, aging

    public array $warehouses = [];

    public function mount(): void
    {
        $this->warehouses = MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        if (empty($this->warehouses)) {
            MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
            MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);
            $this->warehouses = MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        }

        $this->applyPresetDates();
    }

    public function updatedPreset(): void
    {
        $this->applyPresetDates();
    }

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
        $this->applyPresetDates();
    }

    public function setWarehouse($whseId): void
    {
        $this->whse_id = $whseId;
    }

    public function setAutoRefresh(int $seconds): void
    {
        $this->autoRefreshInterval = $seconds;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    private function applyPresetDates(): void
    {
        $now = now();
        switch ($this->preset) {
            case 'today':
                $this->fromDate = $now->format('Y-m-d');
                $this->toDate = $now->format('Y-m-d');
                break;
            case '7_days':
                $this->fromDate = $now->copy()->subDays(6)->format('Y-m-d');
                $this->toDate = $now->format('Y-m-d');
                break;
            case '30_days':
                $this->fromDate = $now->copy()->subDays(29)->format('Y-m-d');
                $this->toDate = $now->format('Y-m-d');
                break;
            case 'this_month':
                $this->fromDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->toDate = $now->format('Y-m-d');
                break;
            case 'this_year':
                $this->fromDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->toDate = $now->format('Y-m-d');
                break;
            case 'custom':
                // retain fromDate and toDate
                if (! $this->fromDate) {
                    $this->fromDate = $now->copy()->subDays(30)->format('Y-m-d');
                }
                if (! $this->toDate) {
                    $this->toDate = $now->format('Y-m-d');
                }
                break;
        }
    }

    public function render(MaterialFifoService $fifoService)
    {
        $selectedWhseId = ($this->whse_id && $this->whse_id !== 'ALL') ? (int) $this->whse_id : null;

        $deviations = $fifoService->detectFifoDeviations($selectedWhseId, $this->fromDate, $this->toDate, 50);
        $kpis = $fifoService->getFifoKpis($selectedWhseId, $this->fromDate, $this->toDate, $deviations);
        $agingSummary = $fifoService->getInventoryAgingSummary($selectedWhseId);
        $priorityQueue = ($this->activeTab === 'queue')
            ? $fifoService->getFifoPriorityQueue($selectedWhseId, $this->search, 25)
            : [];
        $throughputTrend = $fifoService->getThroughputTrend($selectedWhseId, $this->fromDate, $this->toDate);

        // Get warehouse label for header display
        $whseLabel = 'Semua Gudang (KBN & Karawang)';
        if ($selectedWhseId) {
            $found = collect($this->warehouses)->firstWhere('id', $selectedWhseId);
            $whseLabel = $found ? ($found['whse_name'] . ' (' . $found['whse_code'] . ')') : 'Gudang Material';
        }

        return view('livewire.material-warehouse.material-fifo-director-dashboard', [
            'kpis' => $kpis,
            'deviations' => $deviations,
            'agingSummary' => $agingSummary,
            'priorityQueue' => $priorityQueue,
            'throughputTrend' => $throughputTrend,
            'whseLabel' => $whseLabel,
        ]);
    }
}
