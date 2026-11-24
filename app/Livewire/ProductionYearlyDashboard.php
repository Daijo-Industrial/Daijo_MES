<?php

namespace App\Livewire;

use App\Models\MasterMachine;
use App\Models\User;
use App\Services\PlantContextService;
use App\Services\ProductionDashboardService;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class ProductionYearlyDashboard extends Component
{
    public int $year;

    public string $plant = ''; // '', 'karawang', 'kbn'

    public ?int $machineUserId = null;

    public array $years = [];

    public array $allMachines = [];

    public array $machines = [];

    protected ProductionDashboardService $productionService;

    public function boot(ProductionDashboardService $service)
    {
        $this->productionService = $service;
    }

    public function mount()
    {
        $plantService = app(PlantContextService::class);
        $effectiveBranch = $plantService->getEffectiveBranch();
        if ($effectiveBranch) {
            $this->plant = strtolower($effectiveBranch->code) === 'krw' ? 'karawang' : 'kbn';
        }

        $this->year = now()->year;

        $this->populateFilterOptions();
    }

    private function populateFilterOptions(): void
    {
        $currentYear = now()->year;

        $this->years = [];
        for ($i = -4; $i <= 1; $i++) {
            $this->years[] = $currentYear + $i;
        }

        // Prioritaskan MasterMachine jika tabel tersedia
        $masterMachines = collect();
        if (Schema::hasTable('master_machines')) {
            $masterMachines = MasterMachine::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        if ($masterMachines->isNotEmpty()) {
            $machineNames = $masterMachines->pluck('name')->toArray();
            $usersByName = User::whereIn('name', $machineNames)
                ->with('branch')
                ->get()
                ->keyBy('name');

            $this->allMachines = $masterMachines->map(function ($mm) use ($usersByName) {
                $user = $usersByName->get($mm->name);
                $isKarawang = strtoupper($mm->plant) === 'KARAWANG'
                    || ($user && $user->branch && $user->branch->code === 'KRW')
                    || preg_match('/^K\d{3,4}[A-Z]?$/i', $mm->name);

                return [
                    'id' => $user ? $user->id : null,
                    'name' => $mm->name,
                    'plant' => $isKarawang ? 'karawang' : 'kbn',
                ];
            })->filter(fn ($m) => $m['id'] !== null)->values()->toArray();
        } else {
            // Fallback ke tabel users dengan role operator dan format mesin
            $machineUsers = User::whereHas('role', function ($q) {
                $q->where('name', 'OPERATOR');
            })->where(function ($q) {
                $q->where('name', 'REGEXP', '^K[0-9]{3,4}[A-Za-z]?$')
                    ->orWhere(function ($sub) {
                        $sub->where('name', 'NOT LIKE', 'K%')
                            ->where(function ($numQuery) {
                                $numQuery->where('name', 'LIKE', 'MC%')
                                    ->orWhere('name', 'LIKE', '1%')
                                    ->orWhere('name', 'LIKE', '2%')
                                    ->orWhere('name', 'LIKE', '3%')
                                    ->orWhere('name', 'LIKE', '4%')
                                    ->orWhere('name', 'LIKE', '5%')
                                    ->orWhere('name', 'LIKE', '6%')
                                    ->orWhere('name', 'LIKE', '7%')
                                    ->orWhere('name', 'LIKE', '8%')
                                    ->orWhere('name', 'LIKE', '9%');
                            });
                    });
            })->with('branch')->get(['id', 'name', 'branch_id']);

            $this->allMachines = $machineUsers->map(function ($user) {
                $isKarawang = ($user->branch && $user->branch->code === 'KRW')
                    || preg_match('/^K\d{3,4}[A-Z]?$/i', $user->name);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'plant' => $isKarawang ? 'karawang' : 'kbn',
                ];
            })->sortBy('name')->values()->toArray();
        }

        $this->updateFilteredMachines();
    }

    private function updateFilteredMachines(): void
    {
        if ($this->plant === 'karawang') {
            $this->machines = array_values(array_filter($this->allMachines, fn ($m) => $m['plant'] === 'karawang'));
        } elseif ($this->plant === 'kbn') {
            $this->machines = array_values(array_filter($this->allMachines, fn ($m) => $m['plant'] === 'kbn'));
        } else {
            $this->machines = $this->allMachines;
        }

        if ($this->machineUserId) {
            $machineExists = collect($this->machines)->contains('id', (int) $this->machineUserId);
            if (! $machineExists) {
                $this->machineUserId = null;
            }
        }
    }

    public function updatedPlant(): void
    {
        $this->machineUserId = null;
        $this->updateFilteredMachines();
    }

    public function updatedYear(): void
    {
        // Livewire auto re-renders
    }

    public function updatedMachineUserId(): void
    {
        // Livewire auto re-renders
    }

    public function refreshData(): void
    {
        app(ProductionDashboardService::class)->getYearlyDashboardData(
            (int) $this->year,
            $this->plant ?: null,
            $this->machineUserId,
            true
        );
    }

    public function resetFilters(): void
    {
        $this->year = now()->year;
        $this->plant = '';
        $this->machineUserId = null;
        $this->updateFilteredMachines();
    }

    public function render()
    {
        $data = $this->productionService->getYearlyDashboardData(
            (int) $this->year,
            $this->plant ?: null,
            $this->machineUserId
        );

        return view('livewire.production-yearly-dashboard', [
            'summary' => $data['summary'],
            'monthlyMetrics' => $data['monthly_metrics'],
            'machineMetrics' => $data['machine_metrics'],
            'topMachines' => $data['top_machines'],
            'attentionMachines' => $data['attention_machines'],
            'topDefects' => $data['top_defects'],
        ]);
    }
}

